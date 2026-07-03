<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    protected $table = 'faculty';

    protected $guarded = [];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subjectMappings(): HasMany
    {
        return $this->hasMany(SubjectMapping::class);
    }

    public function getNameAttribute(): string
    {
        return $this->faculty_name;
    }

    public function getDepartmentNameAttribute(): ?string
    {
        return $this->department?->department_name;
    }

    public function getDepartmentCodeAttribute(): ?string
    {
        return $this->department?->code;
    }
}
