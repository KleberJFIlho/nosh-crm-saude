<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function __invoke(): View
    {
        return view('site.home');
    }
}
