<?php

namespace Webkul\Prospeccao\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Models\LeadProxy;

class Property extends Model
{
    protected $table = 'prospeccao_properties';

    protected $fillable = [
        'person_id',
        'lead_id',
        'property_type',
        'locality',
        'asking_price',
        'listing_url',
        'notes',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass());
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass());
    }

    public function contactLogs(): HasMany
    {
        return $this->hasMany(ContactLog::class)->latest();
    }
}

