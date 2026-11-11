<?php

namespace Webkul\Prospeccao\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Contact\Models\PersonProxy;

class ContactRestriction extends Model
{
    public const CHANNEL_ALL = 'all';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_EMAIL = 'email';

    protected $table = 'prospeccao_contact_restrictions';

    protected $fillable = [
        'person_id',
        'channel',
        'reason',
        'blocked_at',
        'blocked_by',
        'is_active',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->whereIn('channel', [
            self::CHANNEL_ALL,
            $channel,
        ]);
    }
}
