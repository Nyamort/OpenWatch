<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Show the account settings page.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('settings/account', [
            'user' => $request->user(),
        ]);
    }
}
