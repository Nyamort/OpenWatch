<?php

use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Organization\OrganizationInvitationController;
use App\Http\Controllers\Organization\OrganizationMemberController;
use App\Http\Controllers\Organization\OrganizationSwitcherController;
use App\Http\Controllers\Projects\EnvironmentController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Projects\ProjectTokenController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Organizations
    Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::post('organizations/switch', [OrganizationSwitcherController::class, 'store'])->name('organizations.switch');

    // Organization-scoped routes (require membership)
    Route::middleware(['organization.member'])->prefix('organizations/{organization}')->name('organizations.')->group(function () {
        Route::get('/', [OrganizationController::class, 'show'])->name('show');
        Route::get('edit', [OrganizationController::class, 'edit'])->name('edit');
        Route::patch('/', [OrganizationController::class, 'update'])->name('update');
        Route::delete('/', [OrganizationController::class, 'destroy'])->name('destroy');

        Route::get('members', [OrganizationMemberController::class, 'index'])->name('members.index');
        Route::delete('members/{member}', [OrganizationMemberController::class, 'destroy'])->name('members.destroy');

        Route::post('invitations', [OrganizationInvitationController::class, 'store'])->name('invitations.store');
        Route::delete('invitations/{invitation}', [OrganizationInvitationController::class, 'destroy'])->name('invitations.destroy');

        // Projects
        Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

        // Environments
        Route::get('projects/{project}/environments', [EnvironmentController::class, 'index'])->name('projects.environments.index');
        Route::post('projects/{project}/environments', [EnvironmentController::class, 'store'])->name('projects.environments.store');
        Route::get('projects/{project}/environments/{environment}', [EnvironmentController::class, 'show'])->name('projects.environments.show');

        // Tokens
        Route::post('projects/{project}/environments/{environment}/tokens', [ProjectTokenController::class, 'store'])->name('projects.environments.tokens.store');
        Route::delete('projects/{project}/environments/{environment}/tokens/{token}', [ProjectTokenController::class, 'destroy'])->name('projects.environments.tokens.destroy');
    });

    // Accept invitation (no org membership required)
    Route::get('invitations/{token}/accept', [OrganizationInvitationController::class, 'show'])->name('invitations.show');
    Route::post('invitations/{token}/accept', [OrganizationInvitationController::class, 'accept'])->name('invitations.accept');
});

require __DIR__.'/settings.php';
