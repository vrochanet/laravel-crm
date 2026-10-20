<?php

namespace Webkul\FSBO\Services;

use Webkul\Contact\Repositories\PersonRepository;
use Webkul\FSBO\Models\Property;
use Webkul\Lead\Repositories\LeadRepository;

class FSBOLeadService
{
    public function __construct(
        protected PersonRepository $personRepository,
        protected LeadRepository $leadRepository
    ) {
    }

    /**
     * Cria um proprietário, imóvel e lead FSBO.
     */
    public function create(array $data): array
    {
        $person = $this->findOrCreatePerson($data);

        $lead = $this->leadRepository->create([
            'entity_type' => 'leads',
            'title' => $this->buildLeadTitle($data),
            'person_id' => $person->id,
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
    }

    /**
     * Procura o proprietário pelo telefone ou cria uma nova pessoa.
     */
    protected function findOrCreatePerson(array $data)
    {
        $phone = $data['phone'] ?? null;

        if ($phone) {
            $person = $this->personRepository
                ->whereJsonContains('contact_numbers', [
                    ['value' => $phone],
                ])
                ->first();

            if ($person) {
                return $person;
            }
        }

        return $this->personRepository->create([
            'entity_type' => 'persons',
            'name' => $data['owner_name'],
            'emails' => [
                [
                    'value' => $data['email'] ?? null,
                    'label' => 'work',
                ],
            ],
            'contact_numbers' => [
                [
                    'value' => $phone,
                    'label' => 'mobile',
                ],
            ],
        ]);
    }

    /**
     * Cria o título do lead.
     */
    protected function buildLeadTitle(array $data): string
    {
        $propertyType = $data['property_type'] ?? 'Imóvel';
        $locality = $data['locality'] ?? '';

        return trim($propertyType . ' ' . $locality);
    }
}
