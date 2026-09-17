<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\ElectionController;
use App\Http\Controllers\Api\VoteController;
use App\Http\Controllers\MonitorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin authentication — /api/admin/*
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware('throttle:auth')->group(function () {
    Route::post('signup', [AuthController::class, 'signup']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

/*
|--------------------------------------------------------------------------
| Elections & candidates — /api/elections/*
|--------------------------------------------------------------------------
*/
Route::prefix('elections')->scopeBindings()->group(function () {
    // Public reads — what a voter sees before voting.
    Route::get('{election}', [ElectionController::class, 'show']);
    Route::get('{election}/candidates', [CandidateController::class, 'indexForElection']);

    // Admin-only management. `candidates` is declared before `{election}` routes
    // that could otherwise swallow it, and the UUID constraint keeps them apart.
    Route::middleware('auth:admin')->group(function () {
        Route::get('/', [ElectionController::class, 'index']);
        Route::post('/', [ElectionController::class, 'store']);
        Route::get('candidates', [CandidateController::class, 'index']);

        Route::patch('{election}', [ElectionController::class, 'update']);
        Route::delete('{election}', [ElectionController::class, 'destroy']);
        Route::patch('{election}/status', [ElectionController::class, 'updateStatus']);
        Route::get('{election}/results', [ElectionController::class, 'results']);

        Route::post('{election}/candidates', [CandidateController::class, 'store']);
        Route::patch('{election}/candidates/{candidate}', [CandidateController::class, 'update']);
        Route::post('{election}/candidates/{candidate}', [CandidateController::class, 'update']); // multipart clients cannot PATCH files
        Route::delete('{election}/candidates/{candidate}', [CandidateController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Voting — /api/vote/*
|--------------------------------------------------------------------------
*/
Route::prefix('vote')->group(function () {
    Route::middleware('throttle:voting')->group(function () {
        Route::post('{election}/verify', [VoteController::class, 'verify']);
        Route::post('{election}/confirm', [VoteController::class, 'confirm']);
    });

    Route::post('{election}', [VoteController::class, 'cast'])->middleware('auth:voter');
});

/*
|--------------------------------------------------------------------------
| Live monitor — /api/monitor/*
|--------------------------------------------------------------------------
*/
Route::get('monitor/results/{election}', [MonitorController::class, 'results'])->name('monitor.results');
