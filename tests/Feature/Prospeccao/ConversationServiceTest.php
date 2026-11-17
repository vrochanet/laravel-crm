<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Prospeccao\Models\Conversation;
use Webkul\Prospeccao\Models\Message;
use Webkul\Prospeccao\Models\Property;
use Webkul\Prospeccao\Services\ConversationService;
use Webkul\Prospeccao\Models\CommunicationTemplate;

uses(DatabaseTransactions::class);

beforeEach(function () {
    securityTestPipeline();
});

function conversationTestProperty(): Property
{
    return Property::firstOrFail();
}

it('returns the same conversation for the same property and channel', function () {
    $service = app(ConversationService::class);
    $property = conversationTestProperty();

    $first = $service->getOrCreate($property, Conversation::CHANNEL_WHATSAPP);
    $second = $service->getOrCreate($property, Conversation::CHANNEL_WHATSAPP);

    expect($first->id)->toBe($second->id);
});

it('creates separate conversations for different channels', function () {
    $service = app(ConversationService::class);
    $property = conversationTestProperty();

    $whatsapp = $service->getOrCreate($property, Conversation::CHANNEL_WHATSAPP);
    $email = $service->getOrCreate($property, Conversation::CHANNEL_EMAIL);

    expect($whatsapp->id)->not->toBe($email->id)
        ->and($whatsapp->property_id)->toBe($email->property_id);
});

it('rejects an invalid conversation channel', function () {
    $service = app(ConversationService::class);
    $property = conversationTestProperty();

    expect(fn () => $service->getOrCreate($property, 'invalid'))
        ->toThrow(InvalidArgumentException::class);
});

it('records incoming messages and updates the last message date', function () {
    $service = app(ConversationService::class);
    $conversation = $service->getOrCreate(
        conversationTestProperty(),
        Conversation::CHANNEL_WHATSAPP
    );

    $message = $service->recordIncoming($conversation, 'Mensagem recebida');

    expect($message->direction)->toBe(Message::DIRECTION_INCOMING)
        ->and($message->status)->toBe(Message::STATUS_RECEIVED)
        ->and($message->source)->toBe(Message::SOURCE_MANUAL)
        ->and($message->received_at)->not->toBeNull()
        ->and($conversation->fresh()->last_message_at)->not->toBeNull();
});

it('creates AI messages pending review without updating the last message date', function () {
    $service = app(ConversationService::class);
    $conversation = $service->getOrCreate(
        conversationTestProperty(),
        Conversation::CHANNEL_WHATSAPP
    );

    $message = $service->createOutgoingDraft(
        $conversation,
        'Mensagem preparada pela IA',
        Message::SOURCE_AI
    );

    expect($message->direction)->toBe(Message::DIRECTION_OUTGOING)
        ->and($message->status)->toBe(Message::STATUS_PENDING_REVIEW)
        ->and($message->source)->toBe(Message::SOURCE_AI)
        ->and($conversation->fresh()->last_message_at)->toBeNull();
});

it('creates manual and template messages as drafts', function () {
    $service = app(ConversationService::class);
    $conversation = $service->getOrCreate(
        conversationTestProperty(),
        Conversation::CHANNEL_WHATSAPP
    );

    $manual = $service->createOutgoingDraft(
        $conversation,
        'Mensagem manual'
    );

    $template = $service->createOutgoingDraft(
        $conversation,
        'Mensagem de modelo',
        Message::SOURCE_TEMPLATE
    );

    expect($manual->status)->toBe(Message::STATUS_DRAFT)
        ->and($manual->source)->toBe(Message::SOURCE_MANUAL)
        ->and($template->status)->toBe(Message::STATUS_DRAFT)
        ->and($template->source)->toBe(Message::SOURCE_TEMPLATE);
});

it('rejects an invalid message source', function () {
    $service = app(ConversationService::class);
    $conversation = $service->getOrCreate(
        conversationTestProperty(),
        Conversation::CHANNEL_WHATSAPP
    );

    expect(fn () => $service->createOutgoingDraft(
        $conversation,
        'Mensagem inválida',
        'invalid'
    ))->toThrow(InvalidArgumentException::class);
});

it('approves outgoing draft messages', function () {
    $service = app(ConversationService::class);
    $conversation = $service->getOrCreate(
        conversationTestProperty(),
        Conversation::CHANNEL_WHATSAPP
    );

    $message = $service->createOutgoingDraft($conversation, 'Mensagem manual');

    $approved = $service->approve($message);

    expect($approved->status)->toBe(Message::STATUS_APPROVED)
        ->and($approved->approved_at)->not->toBeNull();
});

it('rejects approval of messages that are not awaiting approval', function () {
    $service = app(ConversationService::class);
    $conversation = $service->getOrCreate(
        conversationTestProperty(),
        Conversation::CHANNEL_WHATSAPP
    );

    $message = $service->recordIncoming($conversation, 'Mensagem recebida');

    expect(fn () => $service->approve($message))
        ->toThrow(LogicException::class);
});

it('confirms sent messages and updates the last message date', function () {
    $service = app(ConversationService::class);
    $conversation = $service->getOrCreate(
        conversationTestProperty(),
        Conversation::CHANNEL_WHATSAPP
    );

    $message = $service->createOutgoingDraft($conversation, 'Mensagem manual');
    $service->approve($message);
    $message->update(['opened_at' => now()]);

    $sent = $service->confirmSent($message);

    expect($sent->status)->toBe(Message::STATUS_SENT)
        ->and($sent->sent_at)->not->toBeNull()
        ->and($conversation->fresh()->last_message_at)->not->toBeNull();
});

it('rejects sending messages that have not been approved', function () {
    $service = app(ConversationService::class);
    $conversation = $service->getOrCreate(
        conversationTestProperty(),
        Conversation::CHANNEL_WHATSAPP
    );

    $message = $service->createOutgoingDraft($conversation, 'Mensagem manual');

    expect(fn () => $service->confirmSent($message))
        ->toThrow(LogicException::class);
});

it('associates an outgoing message with its communication template', function () {
    $service = app(ConversationService::class);

    $conversation = $service->getOrCreate(
        conversationTestProperty(),
        Conversation::CHANNEL_WHATSAPP
    );

    $template = CommunicationTemplate::create([
        'name' => 'Primeiro contacto',
        'stage' => 'initial',
        'channel' => CommunicationTemplate::CHANNEL_WHATSAPP,
        'subject' => null,
        'body' => 'Mensagem de teste',
        'is_active' => true,
    ]);

    $message = $service->createOutgoingDraft(
        $conversation,
        'Mensagem personalizada',
        Message::SOURCE_TEMPLATE,
        null,
        $template
    );

    expect($message->template_id)->toBe($template->id)
        ->and($message->template->name)->toBe('Primeiro contacto')
        ->and($message->body)->toBe('Mensagem personalizada');
});
