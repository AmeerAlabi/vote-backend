<?php

use App\Models\VoterSession;
use Illuminate\Support\Facades\Schedule;

// Voter sessions expire an hour after the code is sent; sweep them regularly.
Schedule::command('model:prune', ['--model' => [VoterSession::class]])->hourly();

// Sanctum tokens carry their own expiry; delete the stale rows once a day.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
