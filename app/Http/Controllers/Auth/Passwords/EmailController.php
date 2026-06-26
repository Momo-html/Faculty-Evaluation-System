<?php

namespace App\Http\Controllers\Auth\Passwords;

use App\Http\Controllers\PageController;

class EmailController extends PageController
{
    protected string $view = 'auth.passwords.email';
}
