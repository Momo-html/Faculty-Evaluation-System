<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountCreation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'login_id',
        'first_name',
        'last_name',
        'full_name',
        'sortable_name',
        'short_name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
