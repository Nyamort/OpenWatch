<?php

use App\Http\Controllers\Analytics\CacheEventController;
use App\Http\Controllers\Analytics\CommandController;
use App\Http\Controllers\Analytics\ExceptionController;
use App\Http\Controllers\Analytics\JobsController;
use App\Http\Controllers\Analytics\LogController;
use App\Http\Controllers\Analytics\MailController;
use App\Http\Controllers\Analytics\NotificationController;
use App\Http\Controllers\Analytics\OutgoingRequestController;
use App\Http\Controllers\Analytics\QueryController;
use App\Http\Controllers\Analytics\RequestController;
use App\Http\Controllers\Analytics\ScheduledTaskController;
use App\Http\Controllers\Analytics\UserAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization.member'])
    ->prefix('environments/{environment}/analytics')
    ->name('analytics.')
    ->group(function () {
        // Request analytics
        Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
        Route::get('requests/route', [RequestController::class, 'route'])->name('requests.route');
        Route::get('requests/{request}', [RequestController::class, 'show'])->name('requests.show');

        // Query analytics
        Route::get('queries', [QueryController::class, 'index'])->name('queries.index');
        Route::get('queries/{query}', [QueryController::class, 'show'])->name('queries.show');

        // Log analytics
        Route::get('logs', [LogController::class, 'index'])->name('logs.index');
        Route::get('logs/{log}', [LogController::class, 'show'])->name('logs.show');

        // Mail analytics
        Route::get('mail', [MailController::class, 'index'])->name('mail.index');
        Route::get('mail/{mail}', [MailController::class, 'show'])->name('mail.show');

        // Cache event analytics
        Route::get('cache-events', [CacheEventController::class, 'index'])->name('cache-events.index');

        // Command analytics
        Route::get('commands', [CommandController::class, 'index'])->name('commands.index');
        Route::get('commands/{command}', [CommandController::class, 'show'])->name('commands.show');

        // Jobs analytics
        Route::get('jobs', [JobsController::class, 'index'])->name('jobs.index');
        Route::get('jobs/{job}', [JobsController::class, 'type'])->name('jobs.type');
        Route::get('jobs/{job}/attempts/{attempt}', [JobsController::class, 'show'])->name('jobs.show');

        // Outgoing request analytics
        Route::get('outgoing-requests', [OutgoingRequestController::class, 'index'])->name('outgoing-requests.index');
        Route::get('outgoing-requests/host', [OutgoingRequestController::class, 'host'])->name('outgoing-requests.host');

        // Notification analytics
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');

        // Scheduled task analytics
        Route::get('scheduled-tasks', [ScheduledTaskController::class, 'index'])->name('scheduled-tasks.index');
        Route::get('scheduled-tasks/{scheduledTask}', [ScheduledTaskController::class, 'show'])->name('scheduled-tasks.show');

        // Exception analytics
        Route::get('exceptions', [ExceptionController::class, 'index'])->name('exceptions.index');
        Route::get('exceptions/{group}', [ExceptionController::class, 'show'])->name('exceptions.show');

        // User analytics
        Route::get('users', [UserAnalyticsController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserAnalyticsController::class, 'show'])->name('users.show');
    });
