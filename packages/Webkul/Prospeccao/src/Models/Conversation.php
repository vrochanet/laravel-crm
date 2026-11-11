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

    public const MODE_MANUAL = 'manual';

    public const MODE_AUTOMATIC = 'automatic';

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
        'mode_override',
        'approval_required_override',
        'min_interval_hours_override',
        'follow_up_interval_days_override',
        'max_follow_ups_override',
        'start_time_override',
        'end_time_override',
        'paused_at',
        'resume_at',
        'pause_reason',
    ];

    protected $casts = [
        'temperature_score' => 'integer',
        'temperature_factors' => 'array',
        'last_message_at' => 'datetime',
        'temperature_updated_at' => 'datetime',
        'ai_summary_updated_at' => 'datetime',
        'approval_required_override' => 'boolean',
        'min_interval_hours_override' => 'integer',
        'follow_up_interval_days_override' => 'integer',
        'max_follow_ups_override' => 'integer',
        'paused_at' => 'datetime',
        'resume_at' => 'datetime',
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
