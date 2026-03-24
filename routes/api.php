<?php

use App\Http\Controllers\Api\AgentAuthController;
use App\Http\Controllers\Api\IngestController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('health', function () {
    try {
        DB::connection()->getPdo();
        $db = 'ok';
    } catch (\Throwable) {
        $db = 'error';
    }

    try {
        cache()->set('_health_check', 1, 5);
        $cache = 'ok';
    } catch (\Throwable) {
        $cache = 'error';
    }

    $status = ($db === 'ok' && $cache === 'ok') ? 200 : 503;

    return response()->json([
        'status' => $status === 200 ? 'ok' : 'degraded',
        'checks' => ['database' => $db, 'cache' => $cache],
        'timestamp' => now()->toIso8601String(),
    ], $status);
})->name('health');

Route::post('agent-auth', [AgentAuthController::class, 'store'])->middleware('throttle:60,1');
Route::post('ingest', [IngestController::class, 'store']);
