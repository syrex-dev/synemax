<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class StyleguideController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(app()->isLocal(), 404);

        return view('styleguide');
    }
}
