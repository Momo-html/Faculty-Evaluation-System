<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCreation extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'short_name',
        'long_name',
        'status',
        'term_id',
    ];
}
