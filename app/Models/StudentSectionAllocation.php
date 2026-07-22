<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSectionAllocation extends Model
{
    protected $fillable = ['user_id', 'section_id', 'changed_by', 'assigned_at', 'ended_at', 'reason'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
