<?php

use App\Http\Controllers\Alerts\AlertRuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Issues\IssueCommentController;
use App\Http\Controllers\Issues\IssueController;
use App\Http\Controllers\Issues\IssueDetailController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Organization\OrganizationSwitcherController;
use App\Http\Controllers\Project\EnvironmentSwitcherController;
use App\Http\Controllers\Project\ProjectSwitcherController;
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
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::post('organizations/switch', [OrganizationSwitcherController::class, 'store'])->name('organizations.switch');
    Route::post('projects/switch', [ProjectSwitcherController::class, 'store'])->name('projects.switch');
    Route::post('environments/switch', [EnvironmentSwitcherController::class, 'store'])->name('environments.switch');
    Route::post('wizard/app', [WizardController::class, 'store'])->name('wizard.app');
    Route::patch('wizard/app/{project}', [WizardController::class, 'update'])->name('wizard.app.update');

    Route::middleware(['organization.member'])->prefix('organizations/{organization}')->name('organizations.')->group(function () {
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
});

require __DIR__.'/settings.php';
