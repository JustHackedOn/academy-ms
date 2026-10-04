<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    /**
     * Send visitors to the dashboard or to the login page.
     */
    public function __invoke(): RedirectResponse
    {
        return auth()->check()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    }
}
