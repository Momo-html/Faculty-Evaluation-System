<?php

namespace App\Http\Controllers;

use App\Support\FrontendDemoData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

abstract class PageController extends Controller
{
    protected string $view;

    public function __invoke(Request $request): View
    {
        return view($this->view, FrontendDemoData::for($this->view));
    }
}
