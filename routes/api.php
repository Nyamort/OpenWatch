<?php

use App\Http\Controllers\Api\AgentAuthController;
use App\Http\Controllers\Api\IngestController;
use Illuminate\Support\Facades\Route;

Route::post('agent-auth', [AgentAuthController::class, 'store']);
Route::post('ingest', [IngestController::class, 'store']);
