<?php

namespace App\Providers;

use App\Models\Admin;
use App\Services\CloudinaryUploader;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CloudinaryUploader::class, fn () => CloudinaryUploader::fromConfig());
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

        // All models use UUID keys; rejecting malformed ids at the router
        // gives a clean 404 instead of a 405 from a sibling route.
        Route::pattern('election', '[0-9a-fA-F-]{36}');
        Route::pattern('candidate', '[0-9a-fA-F-]{36}');

        $this->configureApiDocs();
    }

    /**
     * OpenAPI docs are generated from the routes, form requests and
     * controller docblocks by Scramble and served at /docs/api.
     */
    private function configureApiDocs(): void
    {
        // The docs are public, as the previous Swagger UI was.
        Gate::define('viewApiDocs', fn (?Admin $admin = null) => true);

        Scramble::configure()
            ->routes(fn (RoutingRoute $route) => Str::startsWith($route->uri, 'api/'))
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(SecurityScheme::http('bearer')->setDescription(
                    'Admin token from /api/admin/login, or vote token from /api/vote/{election}/confirm. '.
                    'The legacy x-auth-token / x-vote-token headers are also accepted.'
                ));
            });
    }

    /**
     * Code-based flows are only as strong as their brute-force resistance,
     * so every endpoint that accepts or issues a 6-digit code is throttled
     * per IP and, where present, per email address.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(5)->by('email:'.strtolower((string) $request->input('email'))),
            ];
        });

        RateLimiter::for('voting', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(5)->by('email:'.strtolower((string) $request->input('email', $request->input('sessionId')))),
            ];
        });

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
