<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function show(): Response|RedirectResponse
    {
        if (file_exists(storage_path('app/.installed'))) {
            return redirect()->route('home');
        }

        return Inertia::render('setup');
    }

    public function store(Request $request, CreateNewUser $createNewUser): RedirectResponse
    {
        $user = $createNewUser->create($request->all());

        file_put_contents(storage_path('app/.installed'), now()->toIso8601String());

        auth()->login($user);

        return redirect()->route('dashboard');
    }
}
