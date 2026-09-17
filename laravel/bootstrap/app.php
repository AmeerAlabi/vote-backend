<?php

use App\Http\Middleware\MapLegacyTokenHeaders;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [MapLegacyTokenHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // The original API answered validation failures with 400 and a single
        // human-readable `message`; keep that contract for existing clients.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->validator->errors()->first(),
                    'errors' => $e->errors(),
                ], 400);
            }
        });

        // "Election not found" rather than "No query results for model [App\Models\Election]".
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $previous = $e->getPrevious();

            if ($previous instanceof ModelNotFoundException && ($request->is('api/*') || $request->expectsJson())) {
                $model = Str::headline(class_basename($previous->getModel()));

                return response()->json(['message' => "{$model} not found"], 404);
            }
        });
    })->create();
