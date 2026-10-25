<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Prospeccao\Models\Property;

uses(DatabaseTransactions::class);

beforeEach(function () {
    securityTestPipeline();
});

function deletionProtectionPerson(): Person
{
    return Person::create([
        'name' => 'Contacto teste ' . uniqid(),
        'emails' => [
            ['value' => uniqid() . '@example.invalid', 'label' => 'work'],
        ],
        'user_id' => getDefaultAdmin()->id,
    ]);
}

it('does not delete a person who has an associated property', function () {
    $person = deletionProtectionPerson();

    $property = Property::create([
        'person_id' => $person->id,
        'property_type' => 'Apartamento',
        'locality' => 'Viseu',
        'asking_price' => 150000,
        'notes' => 'Imóvel usado no teste de proteção',
    ]);

    $this->actingAs(getDefaultAdmin(), 'user')
        ->deleteJson(route('admin.contacts.persons.delete', $person->id))
        ->assertStatus(400);

    expect(Person::find($person->id))->not->toBeNull()
        ->and(Property::find($property->id))->not->toBeNull();
});

it('preserves a person and property during mass deletion', function () {
    $person = deletionProtectionPerson();

    $property = Property::create([
        'person_id' => $person->id,
        'property_type' => 'Moradia',
        'locality' => 'Viseu',
        'asking_price' => 250000,
        'notes' => 'Teste de eliminação em massa',
    ]);

    $this->actingAs(getDefaultAdmin(), 'user')
        ->postJson(route('admin.contacts.persons.mass_delete'), [
            'indices' => [$person->id],
        ])
        ->assertStatus(400);

    expect(Person::find($person->id))->not->toBeNull()
        ->and(Property::find($property->id))->not->toBeNull();
});

