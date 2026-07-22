<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CsvImportLog extends Model
{
    protected $guarded = [];

    public function errors(): HasMany
    {
        return $this->hasMany(CsvImportError::class);
    }
}
