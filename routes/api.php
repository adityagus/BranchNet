<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\NetworkApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->prefix('agent')->group(function () {
    Route::post('/heartbeat', [AgentController::class, 'heartbeat']);
    Route::post('/report', [AgentController::class, 'report']);
    Route::get('/devices', [AgentController::class, 'devices']);
});
Route::get('/branches', [NetworkApiController::class, 'branches']);
Route::get('/branches/{branch}', [NetworkApiController::class, 'branch']);
Route::get('/branches/{branch}/devices', [NetworkApiController::class, 'devices']);
Route::get('/devices/{device}', [NetworkApiController::class, 'device']);
Route::post('/devices/{device}/register', [NetworkApiController::class, 'register']);
Route::get('/alerts', [NetworkApiController::class, 'alerts']);
