<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationResponse extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }
}
