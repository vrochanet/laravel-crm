<?php

namespace Webkul\FSBO\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CommunicationTemplate extends Model
{
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_BOTH = 'both';

    protected $table = 'fsbo_communication_templates';

    protected $fillable = [
        'name',
        'stage',
        'channel',
        'subject',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to templates available for a given channel.
     */
    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->where(function (Builder $q) use ($channel) {
            $q->where('channel', $channel)
                ->orWhere('channel', self::CHANNEL_BOTH);
        });
    }

    /**
     * Whether this template can be used for WhatsApp.
     */
    public function supportsWhatsApp(): bool
    {
        return in_array($this->channel, [self::CHANNEL_WHATSAPP, self::CHANNEL_BOTH], true);
    }

    /**
     * Whether this template can be used for email.
     */
    public function supportsEmail(): bool
    {
        return in_array($this->channel, [self::CHANNEL_EMAIL, self::CHANNEL_BOTH], true);
    }
}
