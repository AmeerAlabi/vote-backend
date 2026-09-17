<?php

use App\Models\Admin;
use App\Models\VoterSession;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This is a pure API: there is no session login. The "admin" guard is
    | the default so `$request->user()` resolves to an Admin unless a route
    | explicitly asks for the "voter" guard.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'admin'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'admins'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Both guards are Sanctum token guards bound to different providers, so
    | an admin token is rejected on voter routes and vice-versa.
    |
    */

    'guards' => [
        'admin' => [
            'driver' => 'sanctum',
            'provider' => 'admins',
        ],
        'voter' => [
            'driver' => 'sanctum',
            'provider' => 'voters',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'admins' => [
            'driver' => 'eloquent',
            'model' => Admin::class,
        ],
        // Alias: tooling (e.g. Scramble) resolves `$request->user()` through
        // the conventional "users" provider; for this app that is an Admin.
        'users' => [
            'driver' => 'eloquent',
            'model' => Admin::class,
        ],
        'voters' => [
            'driver' => 'eloquent',
            'model' => VoterSession::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Password resets use emailed one-time codes stored on the admin row
    | (see Admin::issuePasswordResetCode), not the framework broker. This
    | entry only exists so the framework's default configuration resolves.
    |
    */

    'passwords' => [
        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
