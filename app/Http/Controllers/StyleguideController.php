<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class StyleguideController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(app()->isLocal() || (auth()->check() && (bool) (auth()->user()->is_admin ?? false)), 404);

        return view('styleguide');
    }
}
