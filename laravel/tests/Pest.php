<?php

use App\Models\Admin;
use App\Models\Election;
use App\Models\VoterSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/** Authenticate the next request as the given (or a fresh, verified) admin. */
function actingAsAdmin(?Admin $admin = null): Admin
{
    $admin ??= Admin::factory()->create();

    Sanctum::actingAs($admin, ['admin'], 'admin');

    return $admin;
}

/** Authenticate the next request as a verified voter session for the election. */
function actingAsVoter(Election $election, ?VoterSession $session = null): VoterSession
{
    $session ??= VoterSession::factory()->verified()->for($election)->create();

    Sanctum::actingAs($session, [VoterSession::voteAbility($election->id)], 'voter');

    return $session;
}
