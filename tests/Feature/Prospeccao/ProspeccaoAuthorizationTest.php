<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Prospeccao\Models\Property;
use Webkul\Prospeccao\Models\Conversation;
use Webkul\Prospeccao\Models\Message;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function makeProspeccaoScopeUser(string $viewPermission): User
{
    $role = Role::create([
        'name' => 'Prospeccao Scope ' . bin2hex(random_bytes(6)),
        'description' => 'Prospeccao authorization tests.',
        'permission_type' => 'all',
    ]);

    return User::create([
        'name' => 'Prospeccao Scope User',
        'email' => 'prospeccao-scope-' . bin2hex(random_bytes(6)) . '@example.invalid',
        'password' => bcrypt('correct-horse-battery-staple'),
        'status' => 1,
        'role_id' => $role->id,
        'view_permission' => $viewPermission,
    ]);
}

function makeProspeccaoOwnedProperty(User $owner): Property
{
    $person = Person::create([
        'name' => 'Prospeccao Person ' . bin2hex(random_bytes(4)),
        'user_id' => $owner->id,
        'emails' => [
            ['value' => 'person-' . bin2hex(random_bytes(4)) . '@example.invalid', 'label' => 'work'],
        ],
        'contact_numbers' => [
            ['value' => '35191' . random_int(1000000, 9999999), 'label' => 'mobile'],
        ],
    ]);

    $pipeline = securityTestPipeline();

    $lead = Lead::create([
        'title' => 'Prospeccao Lead ' . bin2hex(random_bytes(4)),
        'user_id' => $owner->id,
        'person_id' => $person->id,
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $pipeline->stages()->value('id'),
    ]);

    return Property::create([
        'person_id' => $person->id,
        'lead_id' => $lead->id,
        'property_type' => 'Apartamento',
        'locality' => 'Localidade confidencial',
        'asking_price' => 150000,
        'notes' => 'Dados confidenciais',
    ]);
}

function makeProspeccaoMessage(
    Property $property,
    string $status = Message::STATUS_DRAFT,
    bool $opened = true
): Message {
    $conversation = Conversation::create([
        'property_id' => $property->id,
        'channel' => Conversation::CHANNEL_WHATSAPP,
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    return Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => $status,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Mensagem confidencial de teste.',
        'approved_at' => $status === Message::STATUS_APPROVED ? now() : null,
        'opened_at' => $status === Message::STATUS_APPROVED && $opened ? now() : null,
    ]);
}

it('hides another users properties from the listing', function () {
    $owner = makeProspeccaoScopeUser('global');
    $agent = makeProspeccaoScopeUser('individual');

    makeProspeccaoOwnedProperty($owner);

    $response = test()
        ->actingAs($agent, 'user')
        ->get(route('admin.prospeccao.index'));

    $response->assertOk()
        ->assertDontSee('Localidade confidencial');
});

it('refuses to show another users property', function () {
    $owner = makeProspeccaoScopeUser('global');
    $agent = makeProspeccaoScopeUser('individual');

    $property = makeProspeccaoOwnedProperty($owner);

    test()
        ->actingAs($agent, 'user')
        ->get(route('admin.prospeccao.show', $property->id))
        ->assertUnauthorized();
});

it('refuses to edit another users property', function () {
    $owner = makeProspeccaoScopeUser('global');
    $agent = makeProspeccaoScopeUser('individual');

    $property = makeProspeccaoOwnedProperty($owner);

    test()
        ->actingAs($agent, 'user')
        ->get(route('admin.prospeccao.edit', $property->id))
        ->assertUnauthorized();
});

it('refuses to update another users property', function () {
    $owner = makeProspeccaoScopeUser('global');
    $agent = makeProspeccaoScopeUser('individual');

    $property = makeProspeccaoOwnedProperty($owner);

    test()
        ->actingAs($agent, 'user')
        ->put(route('admin.prospeccao.update', $property->id), [
            'owner_name' => 'Nome alterado',
            'phone' => '351912345678',
            'property_type' => 'Moradia',
            'locality' => 'Outra localidade',
        ])
        ->assertUnauthorized();

    expect($property->fresh()->locality)->toBe('Localidade confidencial');
});

it('refuses to prepare communication for another users property', function () {
    $owner = makeProspeccaoScopeUser('global');
    $agent = makeProspeccaoScopeUser('individual');

    $property = makeProspeccaoOwnedProperty($owner);

    test()
        ->actingAs($agent, 'user')
        ->post(route('admin.prospeccao.communicate.prepare', $property->id))
        ->assertUnauthorized();
});

it('refuses to create communication for another users property', function () {
    $owner = makeProspeccaoScopeUser('global');
    $agent = makeProspeccaoScopeUser('individual');

    $property = makeProspeccaoOwnedProperty($owner);

    test()
        ->actingAs($agent, 'user')
        ->post(route('admin.prospeccao.communicate.send', $property->id))
        ->assertUnauthorized();
});

it('refuses to approve another users message', function () {
    $owner = makeProspeccaoScopeUser('global');
    $agent = makeProspeccaoScopeUser('individual');

    $property = makeProspeccaoOwnedProperty($owner);
    $message = makeProspeccaoMessage($property);

    test()
        ->actingAs($agent, 'user')
        ->post(route('admin.prospeccao.messages.approve', $message->id))
        ->assertUnauthorized();

    expect($message->fresh()->status)->toBe(Message::STATUS_DRAFT);
});

it('refuses to confirm another users message', function () {
    $owner = makeProspeccaoScopeUser('global');
    $agent = makeProspeccaoScopeUser('individual');

    $property = makeProspeccaoOwnedProperty($owner);
    $message = makeProspeccaoMessage($property, Message::STATUS_APPROVED);

    test()
        ->actingAs($agent, 'user')
        ->post(route('admin.prospeccao.messages.confirm-sent', $message->id))
        ->assertUnauthorized();
});

it('refuses to open another users message', function () {
    $owner = makeProspeccaoScopeUser('global');
    $agent = makeProspeccaoScopeUser('individual');

    $property = makeProspeccaoOwnedProperty($owner);
    $message = makeProspeccaoMessage($property, Message::STATUS_APPROVED, false);

    test()
        ->actingAs($agent, 'user')
        ->post(route('admin.prospeccao.messages.open', $message->id))
        ->assertUnauthorized();

    expect($message->fresh()->opened_at)->toBeNull();
});

it('allows a global user to access another users property', function () {
    $owner = makeProspeccaoScopeUser('individual');
    $administrator = makeProspeccaoScopeUser('global');

    $property = makeProspeccaoOwnedProperty($owner);

    test()
        ->actingAs($administrator, 'user')
        ->get(route('admin.prospeccao.show', $property->id))
        ->assertOk();
});
