<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PredictionResult extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'predicted_completion_date' => 'date',
            'generated_at' => 'datetime',
        ];
    }
}
