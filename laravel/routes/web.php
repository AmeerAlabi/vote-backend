<?php

use App\Http\Controllers\MonitorController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response('School Voting App API is running', 200, ['Content-Type' => 'text/plain']));

// Public live-results page; the share link handed out after voting points here.
Route::get('/monitor/election/{election}', [MonitorController::class, 'page'])->name('monitor.page');

// The Express app served Swagger UI here; the generated docs now live at /docs/api.
Route::redirect('/api-docs', '/docs/api');
