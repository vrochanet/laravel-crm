<?php

namespace Webkul\Prospeccao\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\UserProxy;

class Message extends Model
{
    public const DIRECTION_OUTGOING = 'outgoing';

    public const DIRECTION_INCOMING = 'incoming';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SENT = 'sent';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_AI = 'ai';

    public const SOURCE_TEMPLATE = 'template';

    protected $table = 'prospeccao_messages';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'direction',
        'status',
        'source',
        'subject',
        'body',
        'approved_at',
        'sent_at',
        'received_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass());
    }
}
