<?php

use App\Http\Controllers\Settings\NotificationPreferencesController;
use App\Http\Controllers\Settings\OrganizationSettingsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SessionController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('settings/sessions/{sessionId}', [SessionController::class, 'destroy'])->name('sessions.destroy');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('settings/notifications', [NotificationPreferencesController::class, 'edit'])->name('notifications.edit');
    Route::patch('settings/notifications', [NotificationPreferencesController::class, 'update'])->name('notifications.update');
});

Route::middleware(['auth', 'verified', 'organization.member'])->prefix('settings/organizations/{organization}')->name('settings.organizations.')->group(function () {
    Route::get('general', [OrganizationSettingsController::class, 'general'])->name('general');
    Route::patch('/', [OrganizationSettingsController::class, 'update'])->name('update');
    Route::delete('/', [OrganizationSettingsController::class, 'destroy'])->name('destroy');
    Route::get('members', [OrganizationSettingsController::class, 'members'])->name('members');
    Route::post('members/invitations', [OrganizationSettingsController::class, 'storeInvitation'])->name('members.invitations.store');
    Route::delete('members/invitations/{invitation}', [OrganizationSettingsController::class, 'destroyInvitation'])->name('members.invitations.destroy');
    Route::patch('members/{member}', [OrganizationSettingsController::class, 'updateMemberRole'])->name('members.update-role');
    Route::delete('members/{member}', [OrganizationSettingsController::class, 'destroyMember'])->name('members.destroy');
    Route::get('audit', [OrganizationSettingsController::class, 'audit'])->name('audit');
    Route::get('applications', [OrganizationSettingsController::class, 'applications'])->name('applications');
    Route::post('applications', [OrganizationSettingsController::class, 'storeApplication'])->name('applications.store');
    Route::get('applications/{project}', [OrganizationSettingsController::class, 'editApplication'])->name('applications.edit');
    Route::patch('applications/{project}', [OrganizationSettingsController::class, 'updateApplication'])->name('applications.update');
    Route::delete('applications/{project}', [OrganizationSettingsController::class, 'destroyApplication'])->name('applications.destroy');
    Route::post('applications/{project}/environments', [OrganizationSettingsController::class, 'storeEnvironment'])->name('applications.environments.store');
    Route::patch('applications/{project}/environments/{environment}', [OrganizationSettingsController::class, 'updateEnvironment'])->name('applications.environments.update');
    Route::delete('applications/{project}/environments/{environment}', [OrganizationSettingsController::class, 'destroyEnvironment'])->name('applications.environments.destroy');
    Route::post('applications/{project}/environments/{environment}/rotate-token', [OrganizationSettingsController::class, 'rotateEnvironmentToken'])->name('applications.environments.rotate-token');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});
