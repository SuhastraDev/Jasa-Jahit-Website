<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\Exceptions\PostTooLargeException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'user' => \App\Http\Middleware\IsUser::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\UpdateLastSeen::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->is('ukur-badan/analisis')) {
                return redirect()
                    ->route('user.measurement.index')
                    ->with('error', 'Ukuran total foto terlalu besar. Maksimal 5MB per foto atau sekitar 15MB untuk 3 foto. Kompres foto terlebih dahulu lalu upload ulang.');
            }

            return response('File yang dikirim terlalu besar.', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        });
    })->create();
