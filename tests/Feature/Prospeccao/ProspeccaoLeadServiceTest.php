<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Prospeccao\Services\ProspeccaoLeadService;

uses(DatabaseTransactions::class);

function prospeccaoRegressionData(array $overrides = []): array
{
    return array_merge([
        'owner_name' => 'Novo nome de teste',
        'phone' => '351910'.bin2hex(random_bytes(3)),
        'email' => 'novo-'.bin2hex(random_bytes(3)).'@example.invalid',
        'property_type' => 'Apartamento',
        'locality' => 'Viseu',
        'asking_price' => 150000,
        'listing_url' => null,
        'notes' => 'Teste de regressao',
    ], $overrides);
}

function prospeccaoRegressionPerson(array $overrides = [])
{
    return app(PersonRepository::class)->create(array_merge([
        'entity_type' => 'persons',
        'name' => 'Nome original',
        'user_id' => getDefaultAdmin()->id,
        'emails' => [
            ['value' => 'original-'.bin2hex(random_bytes(3)).'@example.invalid', 'label' => 'work'],
            ['value' => 'alternativo-'.bin2hex(random_bytes(3)).'@example.invalid', 'label' => 'home'],
        ],
        'contact_numbers' => [
            ['value' => '351920'.bin2hex(random_bytes(3)), 'label' => 'mobile'],
            ['value' => '351930'.bin2hex(random_bytes(3)), 'label' => 'home'],
        ],
    ], $overrides));
}

beforeEach(function () {
    securityTestPipeline();
});

it('does not overwrite an existing person when matching by phone', function () {
    $person = prospeccaoRegressionPerson();

    $originalName = $person->name;
    $originalEmails = $person->emails;
    $originalPhones = $person->contact_numbers;

    $data = prospeccaoRegressionData([
        'phone' => $originalPhones[0]['value'],
        'email' => 'different-'.bin2hex(random_bytes(3)).'@example.invalid',
        'owner_name' => 'Nome que nao deve substituir o original',
    ]);

    $result = app(ProspeccaoLeadService::class)->create($data);

    $person->refresh();

    expect($result['person']->id)->toBe($person->id)
        ->and($person->name)->toBe($originalName)
        ->and($person->emails)->toEqual($originalEmails)
        ->and($person->contact_numbers)->toEqual($originalPhones);
});

it('does not overwrite an existing person when matching by email', function () {
    $person = prospeccaoRegressionPerson();

    $originalName = $person->name;
    $originalEmails = $person->emails;
    $originalPhones = $person->contact_numbers;

    $data = prospeccaoRegressionData([
        'phone' => '351940'.bin2hex(random_bytes(3)),
        'email' => $originalEmails[0]['value'],
        'owner_name' => 'Outro nome que nao deve substituir o original',
    ]);

    $result = app(ProspeccaoLeadService::class)->create($data);

    $person->refresh();

    expect($result['person']->id)->toBe($person->id)
        ->and($person->name)->toBe($originalName)
        ->and($person->emails)->toEqual($originalEmails)
        ->and($person->contact_numbers)->toEqual($originalPhones);
});

it('creates a new person when no existing contact matches', function () {
    $data = prospeccaoRegressionData();

    $result = app(ProspeccaoLeadService::class)->create($data);

    expect($result['person']->name)->toBe($data['owner_name'])
        ->and($result['person']->id)->toBeGreaterThan(0)
        ->and($result['lead']->person_id)->toBe($result['person']->id)
        ->and($result['property']->person_id)->toBe($result['person']->id)
        ->and($result['property']->lead_id)->toBe($result['lead']->id);
});
