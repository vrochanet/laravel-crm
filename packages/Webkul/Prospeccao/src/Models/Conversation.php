<?php

namespace Webkul\Prospeccao\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_EMAIL = 'email';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_WAITING_REPLY = 'waiting_reply';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'prospeccao_conversations';

    protected $fillable = [
        'property_id',
        'channel',
        'status',
        'subject',
        'last_message_at',
        'temperature_score',
        'temperature_label',
        'temperature_reason',
        'temperature_factors',
        'temperature_updated_at',
        'ai_summary',
        'ai_summary_updated_at',
    ];

    protected $casts = [
        'temperature_score' => 'integer',
        'temperature_factors' => 'array',
        'last_message_at' => 'datetime',
        'temperature_updated_at' => 'datetime',
        'ai_summary_updated_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }
}
