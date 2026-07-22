<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectMapping extends Model
{
    protected $guarded = [];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_subjects')->withTimestamps();
    }

    public function evaluationResponses(): HasMany
    {
        return $this->hasMany(EvaluationResponse::class);
    }
}
