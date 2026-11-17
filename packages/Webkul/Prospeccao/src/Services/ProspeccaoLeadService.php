<?php

namespace Webkul\Prospeccao\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Prospeccao\Models\Property;

class ProspeccaoLeadService
{
    public function __construct(
        protected PersonRepository $personRepository,
        protected LeadRepository $leadRepository
    ) {}

    /**
     * Cria ou reutiliza uma pessoa e regista o imóvel e a lead.
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $userId = auth()->guard('user')->id();

            if (!$userId) {
                throw new \RuntimeException(
                    'É necessário um consultor autenticado para criar uma prospeção.'
                );
            }

            $person = $this->findOrCreatePerson($data, $userId);

            $lead = $this->leadRepository->create([
                'entity_type' => 'leads',
                'title' => $this->buildLeadTitle($data),
                'person_id' => $person->id,
                'user_id' => $userId,
                'description' => $data['notes'] ?? null,
            ]);

            $property = Property::create([
                'person_id' => $person->id,
                'lead_id' => $lead->id,
                'property_type' => $data['property_type'] ?? null,
                'locality' => $data['locality'] ?? null,
                'asking_price' => $data['asking_price'] ?? null,
                'listing_url' => $data['listing_url'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            return [
                'person' => $person,
                'property' => $property,
                'lead' => $lead,
            ];
        });
    }

    /**
     * Reutiliza uma pessoa existente do mesmo consultor.
     */
    protected function findOrCreatePerson(array $data, int $userId)
    {
        $phone = trim($data['phone'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));

        $query = $this->personRepository->where('user_id', $userId);

        if ($phone !== '') {
            $person = (clone $query)
                ->whereJsonContains('contact_numbers', [
                    ['value' => $phone],
                ])
                ->first();

            if ($person) {
                return $person;
            }
        }

        if ($email !== '') {
            $person = (clone $query)
                ->whereJsonContains('emails', [
                    ['value' => $email],
                ])
                ->first();

            if ($person) {
                return $person;
            }
        }

        return $this->personRepository->create([
            'entity_type' => 'persons',
            'name' => $data['owner_name'],
            'user_id' => $userId,
            'emails' => $email !== '' ? [
                [
                    'value' => $email,
                    'label' => 'work',
                ],
            ] : [],
            'contact_numbers' => $phone !== '' ? [
                [
                    'value' => $phone,
                    'label' => 'mobile',
                ],
            ] : [],
        ]);
    }

    /**
     * Cria o título da lead.
     */
    protected function buildLeadTitle(array $data): string
    {
        $propertyType = $data['property_type'] ?? 'Imóvel';
        $locality = $data['locality'] ?? '';

        return trim($propertyType . ' ' . $locality);
    }
}
