<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Prospeccao\Models\ContactRestriction;
use Webkul\Prospeccao\Models\Conversation;
use Webkul\Prospeccao\Models\Property;

uses(DatabaseTransactions::class);

beforeEach(function () {
    securityTestPipeline();
});

function restrictionTestProperty(): Property
{
    return Property::firstOrFail();
}

it('associates contact restrictions with the property owner', function () {
    $property = restrictionTestProperty();

    $restriction = $property->contactRestrictions()->create([
        'channel' => ContactRestriction::CHANNEL_ALL,
        'reason' => 'Pedido de não contacto',
        'blocked_at' => now(),
    ]);

    expect($restriction->person_id)->toBe($property->person_id)
        ->and($restriction->person->id)->toBe($property->person_id)
        ->and($property->fresh()->contactRestrictions)->toHaveCount(1);
});

it('applies global restrictions to every communication channel', function () {
    $property = restrictionTestProperty();

    $property->contactRestrictions()->create([
        'channel' => ContactRestriction::CHANNEL_ALL,
        'blocked_at' => now(),
        'is_active' => true,
    ]);

    $whatsappRestrictions = ContactRestriction::query()
        ->active()
        ->forChannel(Conversation::CHANNEL_WHATSAPP)
        ->get();

    $emailRestrictions = ContactRestriction::query()
        ->active()
        ->forChannel(Conversation::CHANNEL_EMAIL)
        ->get();

    expect($whatsappRestrictions)->toHaveCount(1)
        ->and($emailRestrictions)->toHaveCount(1);
});

it('applies channel restrictions only to the selected channel', function () {
    $property = restrictionTestProperty();

    $property->contactRestrictions()->create([
        'channel' => ContactRestriction::CHANNEL_WHATSAPP,
        'blocked_at' => now(),
        'is_active' => true,
    ]);

    $whatsappRestrictions = ContactRestriction::query()
        ->active()
        ->forChannel(Conversation::CHANNEL_WHATSAPP)
        ->get();

    $emailRestrictions = ContactRestriction::query()
        ->active()
        ->forChannel(Conversation::CHANNEL_EMAIL)
        ->get();

    expect($whatsappRestrictions)->toHaveCount(1)
        ->and($emailRestrictions)->toBeEmpty();
});

it('excludes inactive restrictions from active queries', function () {
    $property = restrictionTestProperty();

    $restriction = $property->contactRestrictions()->create([
        'channel' => ContactRestriction::CHANNEL_ALL,
        'blocked_at' => now(),
        'is_active' => false,
    ]);

    expect(
        ContactRestriction::query()
            ->whereKey($restriction->id)
            ->active()
            ->exists()
    )->toBeFalse();
});

it('persists conversation overrides and pause dates', function () {
    $property = restrictionTestProperty();

    $conversation = $property->conversations()->firstOrFail();

    $conversation->update([
        'mode_override' => Conversation::MODE_MANUAL,
        'approval_required_override' => false,
        'min_interval_hours_override' => 12,
        'follow_up_interval_days_override' => 3,
        'max_follow_ups_override' => 2,
        'start_time_override' => '10:00',
        'end_time_override' => '18:00',
        'status' => Conversation::STATUS_PAUSED,
        'paused_at' => now(),
        'resume_at' => now()->addDays(2),
        'pause_reason' => 'Aguardar decisão do proprietário',
    ]);

    $saved = $conversation->fresh();

    expect($saved->mode_override)->toBe(Conversation::MODE_MANUAL)
        ->and($saved->approval_required_override)->toBeFalse()
        ->and($saved->min_interval_hours_override)->toBe(12)
        ->and($saved->follow_up_interval_days_override)->toBe(3)
        ->and($saved->max_follow_ups_override)->toBe(2)
        ->and($saved->paused_at)->not->toBeNull()
        ->and($saved->resume_at)->not->toBeNull()
        ->and($saved->status)->toBe(Conversation::STATUS_PAUSED);
});
