<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Webkul\Prospeccao\Models\ContactRestriction;
use Webkul\Prospeccao\Models\Conversation;
use Webkul\Prospeccao\Models\Property;
use Webkul\Prospeccao\Services\CommunicationConfigService;
use Webkul\Prospeccao\Services\GlobalCommunicationConfigService;
use Webkul\Prospeccao\Services\CommunicationDecisionService;
use Webkul\Prospeccao\Models\Message;

uses(DatabaseTransactions::class);

beforeEach(function () {
    securityTestPipeline();

    Carbon::setTestNow('2026-11-11 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function decisionTestConversation(
    string $channel = Conversation::CHANNEL_WHATSAPP
): Conversation {
    $property = Property::firstOrFail();

    return Conversation::firstOrCreate(
        [
            'property_id' => $property->id,
            'channel' => $channel,
        ],
        [
            'status' => Conversation::STATUS_ACTIVE,
        ]
    );
}

function decisionTestService(
    Conversation $conversation,
    array $channelOverrides = [],
    array $globalOverrides = []
): CommunicationDecisionService {
    $channelDefaults = [
        'enabled' => true,
        'mode' => 'manual',
        'approval_required' => true,
        'min_interval_hours' => 24,
        'follow_up_interval_days' => 4,
        'max_follow_ups' => 3,
        'start_time' => '09:00',
        'end_time' => '23:59',
    ];

    $globalDefaults = [
        'max_first_contacts_per_day' => 5,
    ];

    $channelConfig = Mockery::mock(CommunicationConfigService::class);

    $channelConfig->shouldReceive('get')
        ->with($conversation->channel)
        ->andReturn(array_merge($channelDefaults, $channelOverrides));

    $globalConfig = Mockery::mock(GlobalCommunicationConfigService::class);

    $globalConfig->shouldReceive('get')
        ->andReturn(array_merge($globalDefaults, $globalOverrides));

    return new CommunicationDecisionService(
        $channelConfig,
        $globalConfig
    );
}

it('blocks a conversation when the contact has a global restriction', function () {
    $conversation = decisionTestConversation();

    $conversation->property->contactRestrictions()->create([
        'channel' => ContactRestriction::CHANNEL_ALL,
        'blocked_at' => now(),
        'is_active' => true,
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('blocked')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('contact_blocked')
        ->and($result['reasons'])->toContain('contact_blocked');
});

it('blocks only the channel covered by a contact restriction', function () {
    $conversation = decisionTestConversation(Conversation::CHANNEL_WHATSAPP);

    $conversation->property->contactRestrictions()->create([
        'channel' => ContactRestriction::CHANNEL_WHATSAPP,
        'blocked_at' => now(),
        'is_active' => true,
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('blocked')
        ->and($result['reason'])->toBe('contact_blocked');
});

it('does not block a conversation because of an inactive restriction', function () {
    $conversation = decisionTestConversation();

    $conversation->property->contactRestrictions()->create([
        'channel' => ContactRestriction::CHANNEL_ALL,
        'blocked_at' => now(),
        'is_active' => false,
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('allowed')
        ->and($result['allowed'])->toBeTrue();
});

it('blocks communication when the channel is disabled', function () {
    $conversation = decisionTestConversation();

    $result = decisionTestService(
        $conversation,
        ['enabled' => false]
    )->evaluate($conversation);

    expect($result['decision'])->toBe('blocked')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('channel_disabled');
});

it('blocks a closed conversation', function () {
    $conversation = decisionTestConversation();

    $conversation->update([
        'status' => Conversation::STATUS_CLOSED,
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('blocked')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('conversation_closed');
});

it('blocks a manually paused conversation', function () {
    $conversation = decisionTestConversation();

    $conversation->update([
        'status' => Conversation::STATUS_PAUSED,
        'paused_at' => now(),
        'resume_at' => null,
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('blocked')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('conversation_paused');
});

it('defers a scheduled pause until its resume date', function () {
    $conversation = decisionTestConversation();

    $resumeAt = now()->addHours(3);

    $conversation->update([
        'status' => Conversation::STATUS_PAUSED,
        'paused_at' => now(),
        'resume_at' => $resumeAt,
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('scheduled_pause')
        ->and($result['next_action_at']->timestamp)->toBe($resumeAt->timestamp);
});

it('blocks first contacts when the global daily limit is reached across channels', function () {
    $property = Property::firstOrFail();

    $whatsapp = decisionTestConversation(Conversation::CHANNEL_WHATSAPP);

    $email = Conversation::firstOrCreate(
        [
            'property_id' => $property->id,
            'channel' => Conversation::CHANNEL_EMAIL,
        ],
        ['status' => Conversation::STATUS_ACTIVE]
    );

    Message::create([
        'conversation_id' => $whatsapp->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Primeiro contacto WhatsApp',
        'sent_at' => now(),
    ]);

    $service = decisionTestService(
        $email,
        [],
        ['max_first_contacts_per_day' => 1]
    );

    $result = $service->evaluate($email);

    expect($result['decision'])->toBe('deferred')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('daily_limit_reached')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-12 09:00:00');
});

it('does not count drafts approved or failed messages towards the daily limit', function () {
    $conversation = decisionTestConversation();

    foreach ([
        Message::STATUS_DRAFT,
        Message::STATUS_APPROVED,
        Message::STATUS_FAILED,
    ] as $status) {
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => Message::DIRECTION_OUTGOING,
            'status' => $status,
            'source' => Message::SOURCE_MANUAL,
            'body' => 'Mensagem não enviada',
        ]);
    }

    $service = decisionTestService(
        $conversation,
        [],
        ['max_first_contacts_per_day' => 1]
    );

    $result = $service->evaluate($conversation);

    expect($result['decision'])->toBe('allowed')
        ->and($result['allowed'])->toBeTrue();
});

it('does not count a second outgoing message in the same conversation as a first contact', function () {
    $conversation = decisionTestConversation();

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Primeiro contacto',
        'sent_at' => now()->subHours(2),
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Seguimento',
        'sent_at' => now(),
    ]);

    $property = Property::firstOrFail();

    $email = Conversation::firstOrCreate(
        [
            'property_id' => $property->id,
            'channel' => Conversation::CHANNEL_EMAIL,
        ],
        ['status' => Conversation::STATUS_ACTIVE]
    );

    $service = decisionTestService(
        $email,
        [],
        ['max_first_contacts_per_day' => 1]
    );

    $result = $service->evaluate($email);

    expect($result['decision'])->toBe('deferred')
        ->and($result['reason'])->toBe('daily_limit_reached');
});

it('does not count first contacts sent on previous days', function () {
    $conversation = decisionTestConversation();

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Contacto de ontem',
        'sent_at' => now()->subDay(),
    ]);

    $property = Property::firstOrFail();

    $email = Conversation::firstOrCreate(
        [
            'property_id' => $property->id,
            'channel' => Conversation::CHANNEL_EMAIL,
        ],
        ['status' => Conversation::STATUS_ACTIVE]
    );

    $service = decisionTestService(
        $email,
        [],
        ['max_first_contacts_per_day' => 1]
    );

    $result = $service->evaluate($email);

    expect($result['decision'])->toBe('allowed')
        ->and($result['allowed'])->toBeTrue();
});

it('defers a follow-up when the minimum interval has not elapsed', function () {
    $conversation = decisionTestConversation();

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Mensagem anterior',
        'sent_at' => now()->subHours(12),
    ]);

    $result = decisionTestService($conversation, ['follow_up_interval_days' => 0])->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('minimum_interval_not_reached')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-11 22:00:00');
});

it('allows a follow-up when the minimum interval has elapsed', function () {
    $conversation = decisionTestConversation();

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Mensagem anterior',
        'sent_at' => now()->subHours(25),
    ]);

    $result = decisionTestService($conversation, ['follow_up_interval_days' => 1])->evaluate($conversation);

    expect($result['decision'])->toBe('allowed')
        ->and($result['allowed'])->toBeTrue();
});

it('uses the conversation minimum interval override', function () {
    $conversation = decisionTestConversation();

    $conversation->update([
        'min_interval_hours_override' => 48,
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Mensagem anterior',
        'sent_at' => now()->subHours(30),
    ]);

    $result = decisionTestService($conversation, ['follow_up_interval_days' => 0])->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['reason'])->toBe('minimum_interval_not_reached')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-12 09:00:00');
});

it('does not apply the WhatsApp minimum interval to an email conversation', function () {
    $property = Property::firstOrFail();

    $whatsapp = decisionTestConversation(Conversation::CHANNEL_WHATSAPP);

    Message::create([
        'conversation_id' => $whatsapp->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Mensagem WhatsApp',
        'sent_at' => now()->subHours(2),
    ]);

    $email = Conversation::firstOrCreate(
        [
            'property_id' => $property->id,
            'channel' => Conversation::CHANNEL_EMAIL,
        ],
        ['status' => Conversation::STATUS_ACTIVE]
    );

    $result = decisionTestService($email)->evaluate($email);

    expect($result['decision'])->toBe('allowed')
        ->and($result['allowed'])->toBeTrue();
});


it('blocks follow-ups when the maximum number has been reached', function () {
    $conversation = decisionTestConversation();

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Primeiro contacto',
        'sent_at' => now()->subDays(10),
    ]);

    foreach (range(1, 3) as $number) {
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => Message::DIRECTION_OUTGOING,
            'status' => Message::STATUS_SENT,
            'source' => Message::SOURCE_MANUAL,
            'body' => "Seguimento {$number}",
            'sent_at' => now()->subDays($number),
        ]);
    }

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('blocked')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('maximum_follow_ups_reached');
});

it('allows follow-ups when the maximum has not been reached', function () {
    $conversation = decisionTestConversation();

    foreach (range(1, 2) as $number) {
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => Message::DIRECTION_OUTGOING,
            'status' => Message::STATUS_SENT,
            'source' => Message::SOURCE_MANUAL,
            'body' => "Seguimento {$number}",
            'sent_at' => now()->subDays($number + 5),
        ]);
    }

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('allowed')
        ->and($result['allowed'])->toBeTrue();
});

it('resets the follow-up count after an incoming reply', function () {
    $conversation = decisionTestConversation();

    foreach (range(1, 3) as $number) {
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => Message::DIRECTION_OUTGOING,
            'status' => Message::STATUS_SENT,
            'source' => Message::SOURCE_MANUAL,
            'body' => "Seguimento {$number}",
            'sent_at' => now()->subDays(4 - $number),
        ]);
    }

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_INCOMING,
        'status' => Message::STATUS_RECEIVED,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Tenho interesse. Podemos conversar?',
        'received_at' => now()->subDay(),
    ]);

    $result = decisionTestService($conversation, ['follow_up_interval_days' => 1])->evaluate($conversation);

    expect($result['decision'])->toBe('allowed')
        ->and($result['allowed'])->toBeTrue();
});

it('does not count unsent messages towards the maximum follow-ups', function () {
    $conversation = decisionTestConversation();

    foreach ([
        Message::STATUS_DRAFT,
        Message::STATUS_APPROVED,
        Message::STATUS_FAILED,
    ] as $status) {
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => Message::DIRECTION_OUTGOING,
            'status' => $status,
            'source' => Message::SOURCE_MANUAL,
            'body' => 'Mensagem não enviada',
        ]);
    }

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('allowed')
        ->and($result['allowed'])->toBeTrue();
});

it('uses the conversation maximum follow-ups override', function () {
    $conversation = decisionTestConversation();

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Primeiro contacto',
        'sent_at' => now()->subDays(10),
    ]);

    $conversation->update([
        'max_follow_ups_override' => 2,
    ]);

    foreach (range(1, 2) as $number) {
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => Message::DIRECTION_OUTGOING,
            'status' => Message::STATUS_SENT,
            'source' => Message::SOURCE_MANUAL,
            'body' => "Seguimento {$number}",
            'sent_at' => now()->subDays($number),
        ]);
    }

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('blocked')
        ->and($result['reason'])->toBe('maximum_follow_ups_reached');
});


it('defers a follow-up when the configured number of days has not elapsed', function () {
    $conversation = decisionTestConversation();

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Primeiro contacto',
        'sent_at' => now()->subDays(10),
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Seguimento',
        'sent_at' => now()->subDays(2),
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('follow_up_interval_not_reached')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-13 10:00:00');
});

it('allows a follow-up when the configured number of days has elapsed', function () {
    $conversation = decisionTestConversation();

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Primeiro contacto',
        'sent_at' => now()->subDays(10),
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Seguimento',
        'sent_at' => now()->subDays(5),
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('allowed')
        ->and($result['allowed'])->toBeTrue();
});

it('uses the conversation follow-up interval override', function () {
    $conversation = decisionTestConversation();

    $conversation->update([
        'follow_up_interval_days_override' => 7,
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Primeiro contacto',
        'sent_at' => now()->subDays(10),
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Seguimento',
        'sent_at' => now()->subDays(3),
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['reason'])->toBe('follow_up_interval_not_reached')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-16 09:00:00');
});

it('restarts the follow-up interval after an incoming reply', function () {
    $conversation = decisionTestConversation();

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Primeiro contacto',
        'sent_at' => now()->subDays(12),
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Seguimento anterior',
        'sent_at' => now()->subDays(8),
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_INCOMING,
        'status' => Message::STATUS_RECEIVED,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Tenho interesse.',
        'received_at' => now()->subDay(),
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['reason'])->toBe('follow_up_interval_not_reached')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-16 09:00:00');
});


it('defers communication before the allowed start time', function () {
    $conversation = decisionTestConversation();

    $result = decisionTestService(
        $conversation,
        ['start_time' => '11:00']
    )->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('outside_allowed_hours')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-11 11:00:00');
});

it('defers communication after the allowed end time until the next business day', function () {
    $conversation = decisionTestConversation();

    $result = decisionTestService(
        $conversation,
        ['end_time' => '09:30']
    )->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('outside_allowed_hours')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-12 09:00:00');
});

it('defers communication during the weekend until monday', function () {
    Carbon::setTestNow('2026-11-14 10:00:00');

    $conversation = decisionTestConversation();

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('outside_allowed_hours')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-16 09:00:00');
});

it('uses the conversation start time override', function () {
    $conversation = decisionTestConversation();

    $conversation->update([
        'start_time_override' => '11:00',
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['reason'])->toBe('outside_allowed_hours')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-11 11:00:00');
});

it('uses the conversation end time override', function () {
    $conversation = decisionTestConversation();

    $conversation->update([
        'end_time_override' => '09:30',
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['reason'])->toBe('outside_allowed_hours')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-12 09:00:00');
});

it('aggregates all temporary restrictions and uses the latest readiness time', function () {
    $conversation = decisionTestConversation();

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Mensagem anterior',
        'sent_at' => now()->subHours(12),
    ]);

    $result = decisionTestService($conversation)->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reasons'])->toBe([
                'minimum_interval_not_reached',
                'follow_up_interval_not_reached',
            ])
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-16 09:00:00');
});

it('uses the conversation start time override when the daily limit is reached', function () {
    $property = Property::firstOrFail();

    $whatsapp = decisionTestConversation();

    Message::create([
        'conversation_id' => $whatsapp->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Primeiro contacto',
        'sent_at' => now(),
    ]);

    $email = Conversation::firstOrCreate(
        [
            'property_id' => $property->id,
            'channel' => Conversation::CHANNEL_EMAIL,
        ],
        ['status' => Conversation::STATUS_ACTIVE]
    );

    $email->update([
        'start_time_override' => '11:00',
    ]);

    $result = decisionTestService(
        $email,
        [],
        ['max_first_contacts_per_day' => 1]
    )->evaluate($email);

    expect($result['decision'])->toBe('deferred')
        ->and($result['reason'])->toBe('daily_limit_reached')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-12 11:00:00');
});

it('normalizes the next action time to the next allowed business period', function () {
    Carbon::setTestNow('2026-11-13 10:00:00');

    $conversation = decisionTestConversation();

    Message::create([
        'conversation_id' => $conversation->id,
        'direction' => Message::DIRECTION_OUTGOING,
        'status' => Message::STATUS_SENT,
        'source' => Message::SOURCE_MANUAL,
        'body' => 'Mensagem anterior',
        'sent_at' => now()->subDays(),
    ]);

    $result = decisionTestService(
        $conversation,
        [
            'min_interval_hours' => 48,
            'follow_up_interval_days' => 0,
        ]
    )->evaluate($conversation);

    expect($result['decision'])->toBe('deferred')
        ->and($result['next_action_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-11-16 09:00:00');
});
