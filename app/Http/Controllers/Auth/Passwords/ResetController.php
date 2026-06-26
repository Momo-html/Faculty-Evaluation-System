<?php

namespace App\Http\Controllers\Auth\Passwords;

use App\Http\Controllers\PageController;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResetController extends PageController
{
    protected string $view = 'auth.passwords.reset';

    public function __invoke(Request $request, ?string $token = null): View
    {
        return view($this->view, [
            'token' => $token ?? $request->route('token'),
            'email' => $request->query('email'),
        ]);
    }
}
