<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class EvaluationAnswer extends Model
{
    protected $guarded = [];

    public function response(): BelongsTo
    {
        return $this->belongsTo(EvaluationResponse::class, 'evaluation_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(FormQuestion::class, 'form_question_id');
    }
}
