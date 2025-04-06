<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mining\MiningController;
use App\Http\Controllers\Mining\BoostController;
use App\Http\Controllers\Mining\TaskController;

Route::prefix('mining')->group(function () {
    // Mining Operations
    Route::post('start', [MiningController::class, 'startMining']);
    Route::post('stop', [MiningController::class, 'stopMining']);
    Route::get('status', [MiningController::class, 'getMiningStatus']);
    Route::get('earnings', [MiningController::class, 'getEarnings']);

    // Boost System
    Route::get('boosts', [BoostController::class, 'getAvailableBoosts']);
    Route::post('boosts/activate', [BoostController::class, 'activateBoost']);
    Route::get('boosts/active', [BoostController::class, 'getActiveBoosts']);

    // Task System
    Route::get('tasks', [TaskController::class, 'getAvailableTasks']);
    Route::post('tasks/complete', [TaskController::class, 'completeTask']);
    Route::get('tasks/completed', [TaskController::class, 'getCompletedTasks']);

    // Token Management
    Route::get('tokens/balance', [MiningController::class, 'getTokenBalance']);
    Route::get('tokens/history', [MiningController::class, 'getTokenHistory']);
});
