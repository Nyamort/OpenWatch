<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateNotificationPreferences;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\NotificationPreferencesUpdateRequest;
use App\Models\UserNotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferencesController extends Controller
{
    /**
     * Show the user's notification preferences page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $storedPreferences = $user->notificationPreferences()->pluck('enabled', 'category');

        $allCategories = [
            UserNotificationPreference::CATEGORY_ISSUE_UPDATES,
            UserNotificationPreference::CATEGORY_THRESHOLD_ALERTS,
            UserNotificationPreference::CATEGORY_SECURITY,
        ];

        $categories = collect($allCategories)->mapWithKeys(fn (string $category) => [
            $category => [
                'enabled' => (bool) $storedPreferences->get($category, true),
                'locked' => in_array($category, UserNotificationPreference::LOCKED_CATEGORIES, true),
            ],
        ])->all();

        return Inertia::render('settings/notifications', [
            'categories' => $categories,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's notification preferences.
     */
    public function update(NotificationPreferencesUpdateRequest $request, UpdateNotificationPreferences $action): RedirectResponse
    {
        $categories = collect($request->validated()['categories'])
            ->mapWithKeys(fn (array $item, string $category) => [$category => (bool) $item['enabled']])
            ->all();

        $action->handle($request->user(), $categories);

        return to_route('notifications.edit');
    }
}
