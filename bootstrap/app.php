<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(
        function (
            Middleware $middleware
        ): void {
            $middleware->alias([
                'admin' =>
                    \App\Http\Middleware\AdminMiddleware::class,
            ]);

            $middleware->web(
                append: [
                    \App\Http\Middleware\EnsureUserIsActive::class,

                    /*
                     * Le changement obligatoire
                     * de mot de passe reste prioritaire.
                     */
                    \App\Http\Middleware\ForcePasswordChange::class,

                    /*
                     * Détection générique des nouveautés.
                     *
                     * Fonctionne aussi avec une session
                     * restaurée par "Se souvenir de moi".
                     */
                    \App\Http\Middleware\ShowUnseenFeatures::class,

                    \App\Http\Middleware\BlockFutureJourneeResults::class,
                ]
            );
        }
    )
    ->withExceptions(
        function (
            Exceptions $exceptions
        ): void {
            $exceptions->shouldRenderJsonWhen(
                fn (Request $request) =>
                    $request->is('api/*'),
            );
        }
    )
    ->create();
