<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\PageController;
use App\Support\FrontendDemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvalFormController extends PageController
{
    protected string $view = 'user.eval-form';

    public function show(Request $request, int $mapping): View
    {
        return view($this->view, FrontendDemoData::for($this->view));
    }

    public function submit(Request $request): RedirectResponse
    {
        return redirect()
            ->route('user.home')
            ->with('success', 'Evaluation submitted for preview.');
    }
}
