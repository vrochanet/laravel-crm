<?php

namespace Webkul\FSBO\Services;

use Webkul\FSBO\Models\CommunicationTemplate;
use Webkul\FSBO\Models\Property;
use Webkul\User\Contracts\User as UserContract;

class TemplateParserService
{
    /**
     * Supported template variables and short descriptions.
     *
     * @return array<string, string>
     */
    public function availableVariables(): array
    {
        return [
            'owner_name' => 'Nome do proprietário / contacto',
            'property_type' => 'Tipo de imóvel',
            'locality' => 'Localidade',
            'asking_price' => 'Preço pedido',
            'listing_url' => 'URL do anúncio',
            'consultant_name' => 'Nome do consultor autenticado',
        ];
    }

    /**
     * Replace variables in a raw string using property (and optional consultant) data.
     */
    public function parse(string $content, Property $property, ?UserContract $consultant = null): string
    {
        $replacements = $this->buildReplacements($property, $consultant);

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );
    }

    /**
     * Parse subject and body from a template into a static snapshot.
     *
     * @return array{subject: string|null, body: string}
     */
    public function parseTemplate(
        CommunicationTemplate $template,
        Property $property,
        ?UserContract $consultant = null
    ): array {
        return [
            'subject' => $template->subject !== null
                ? $this->parse($template->subject, $property, $consultant)
                : null,
            'body' => $this->parse($template->body, $property, $consultant),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function buildReplacements(Property $property, ?UserContract $consultant = null): array
    {
        $property->loadMissing('person');

        $consultantName = $consultant?->name
            ?? auth()->guard('user')->user()?->name
            ?? '';

        return [
            '{{owner_name}}' => (string) ($property->person?->name ?? ''),
            '{{property_type}}' => (string) ($property->property_type ?? ''),
            '{{locality}}' => (string) ($property->locality ?? ''),
            '{{asking_price}}' => $this->formatAskingPrice($property->asking_price),
            '{{listing_url}}' => (string) ($property->listing_url ?? ''),
            '{{consultant_name}}' => (string) $consultantName,
        ];
    }

    protected function formatAskingPrice(mixed $price): string
    {
        if ($price === null || $price === '') {
            return '';
        }

        return number_format((float) $price, 2, ',', '.').' €';
    }
}
