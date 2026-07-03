<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $guarded = [];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function faculty(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->department_name;
    }
}
