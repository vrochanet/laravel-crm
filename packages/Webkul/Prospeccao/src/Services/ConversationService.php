<?php

namespace Webkul\Prospeccao\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Webkul\Prospeccao\Models\Conversation;
use Webkul\Prospeccao\Models\Message;
use Webkul\Prospeccao\Models\Property;

class ConversationService
{
    /**
     * Get an existing conversation or create a new one.
     */
    public function getOrCreate(
        Property $property,
        string $channel
    ): Conversation {
        if (! in_array($channel, [
            Conversation::CHANNEL_WHATSAPP,
            Conversation::CHANNEL_EMAIL,
        ], true)) {
            throw new InvalidArgumentException('Invalid conversation channel.');
        }

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

    /**
     * Register an incoming message.
     */
    public function recordIncoming(
        Conversation $conversation,
        string $body,
        ?string $subject = null
    ): Message {
        return DB::transaction(function () use ($conversation, $body, $subject) {
            $message = $conversation->messages()->create([
                'user_id' => auth()->guard('user')->id(),
                'direction' => Message::DIRECTION_INCOMING,
                'status' => Message::STATUS_RECEIVED,
                'source' => Message::SOURCE_MANUAL,
                'subject' => $subject,
                'body' => $body,
                'received_at' => now(),
            ]);

            $conversation->update([
                'last_message_at' => $message->received_at,
            ]);

            return $message;
        });
    }

    /**
     * Create an outgoing message awaiting human review.
     */
    public function createOutgoingDraft(
        Conversation $conversation,
        string $body,
        string $source = Message::SOURCE_MANUAL,
        ?string $subject = null
    ): Message {
        if (! in_array($source, [
            Message::SOURCE_MANUAL,
            Message::SOURCE_AI,
            Message::SOURCE_TEMPLATE,
        ], true)) {
            throw new InvalidArgumentException('Invalid message source.');
        }

        return $conversation->messages()->create([
            'user_id' => auth()->guard('user')->id(),
            'direction' => Message::DIRECTION_OUTGOING,
            'status' => $source === Message::SOURCE_AI
                ? Message::STATUS_PENDING_REVIEW
                : Message::STATUS_DRAFT,
            'source' => $source,
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    /**
     * Approve an outgoing message.
     */
    public function approve(Message $message): Message
    {
        if (
            $message->direction !== Message::DIRECTION_OUTGOING
            || ! in_array($message->status, [
                Message::STATUS_DRAFT,
                Message::STATUS_PENDING_REVIEW,
            ], true)
        ) {
            throw new LogicException('This message cannot be approved.');
        }

        $message->update([
            'status' => Message::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        return $message->refresh();
    }

    /**
     * Confirm that an approved message was manually sent.
     */
    public function confirmSent(Message $message): Message
    {
        if (
            $message->direction !== Message::DIRECTION_OUTGOING
            || $message->status !== Message::STATUS_APPROVED
        ) {
            throw new LogicException('Only approved messages can be marked as sent.');
        }

        return DB::transaction(function () use ($message) {
            $sentAt = now();

            $message->update([
                'status' => Message::STATUS_SENT,
                'sent_at' => $sentAt,
            ]);

            $message->conversation->update([
                'last_message_at' => $sentAt,
            ]);

            return $message->refresh();
        });
    }
}
