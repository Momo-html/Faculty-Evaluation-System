<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $guarded = [];

    public function faculty(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
