<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class EvaluationForm extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'open_at' => 'datetime',
            'close_at' => 'datetime',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(FormQuestion::class)->orderBy('order_number');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(EvaluationResponse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
