<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdatePreferences;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PreferencesUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PreferencesController extends Controller
{
    /**
     * Show the user's preferences settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/preferences', [
            'timezone' => $request->user()->timezone,
            'locale' => $request->user()->locale,
            'display_preferences' => $request->user()->display_preferences ?? [],
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's preferences.
     */
    public function update(PreferencesUpdateRequest $request, UpdatePreferences $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('preferences.edit');
    }
}
