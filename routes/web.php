<?php

use App\Http\Controllers\Alerts\AlertRuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Issues\IssueCommentController;
use App\Http\Controllers\Issues\IssueController;
use App\Http\Controllers\Issues\IssueDetailController;
use App\Http\Controllers\Organization\AuditController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Organization\OrganizationInvitationController;
use App\Http\Controllers\Organization\OrganizationMemberController;
use App\Http\Controllers\Organization\OrganizationSwitcherController;
use App\Http\Controllers\Project\EnvironmentSwitcherController;
use App\Http\Controllers\Project\ProjectSwitcherController;
use App\Http\Controllers\Projects\EnvironmentController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Projects\ProjectTokenController;
use App\Http\Controllers\WizardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Organizations
    Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::post('organizations/switch', [OrganizationSwitcherController::class, 'store'])->name('organizations.switch');
    Route::post('projects/switch', [ProjectSwitcherController::class, 'store'])->name('projects.switch');
    Route::post('environments/switch', [EnvironmentSwitcherController::class, 'store'])->name('environments.switch');
    Route::post('wizard/app', [WizardController::class, 'store'])->name('wizard.app');

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

        // Issues
        Route::prefix('projects/{project}/environments/{environment}/issues')
            ->name('issues.')
            ->group(function () {
                Route::get('/', [IssueController::class, 'index'])->name('index');
                Route::post('/', [IssueController::class, 'store'])->name('store');
                Route::post('/bulk', [IssueController::class, 'bulkUpdate'])->name('bulk-update');
                Route::patch('/{issue}', [IssueController::class, 'update'])->name('update');
                Route::get('/{issue}', [IssueDetailController::class, 'show'])->name('show');
                Route::post('/{issue}/comments', [IssueCommentController::class, 'store'])->name('comments.store');
                Route::patch('/{issue}/comments/{comment}', [IssueCommentController::class, 'update'])->name('comments.update');
                Route::delete('/{issue}/comments/{comment}', [IssueCommentController::class, 'destroy'])->name('comments.destroy');
            });

        // Audit Log
        Route::get('audit', [AuditController::class, 'index'])->name('audit');

        // Alert Rules
        Route::prefix('projects/{project}/environments/{environment}/alert-rules')
            ->name('alert-rules.')
            ->group(function () {
                Route::get('/', [AlertRuleController::class, 'index'])->name('index');
                Route::get('/create', [AlertRuleController::class, 'create'])->name('create');
                Route::post('/', [AlertRuleController::class, 'store'])->name('store');
                Route::get('/{alertRule}', [AlertRuleController::class, 'edit'])->name('edit');
                Route::patch('/{alertRule}', [AlertRuleController::class, 'update'])->name('update');
                Route::delete('/{alertRule}', [AlertRuleController::class, 'destroy'])->name('destroy');
                Route::patch('/{alertRule}/toggle', [AlertRuleController::class, 'toggle'])->name('toggle');
            });
    });

    // Accept invitation (no org membership required)
    Route::get('invitations/{token}/accept', [OrganizationInvitationController::class, 'show'])->name('invitations.show');
    Route::post('invitations/{token}/accept', [OrganizationInvitationController::class, 'accept'])->name('invitations.accept');
});

require __DIR__.'/settings.php';
