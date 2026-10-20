<?php

namespace Webkul\FSBO\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Owner extends Model
{
    protected $table = 'fsbo_owners';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'notes',
    ];

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
