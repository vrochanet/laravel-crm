<?php

namespace Webkul\Prospeccao\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\UserProxy;

class ContactLog extends Model
{
    protected $table = 'prospeccao_contact_logs';

    protected $fillable = [
        'property_id',
        'template_id',
        'user_id',
        'channel',
        'subject',
        'body',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass());
    }
}

