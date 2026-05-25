<?php

use App\Exceptions\LeadNotFoundException;
use App\Exceptions\ManagerNotFoundException;
use App\Models\Lead;
use App\Models\Manager;
use App\Support\ApiResponse;
use App\Support\BaseException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Domain exceptions render themselves via BaseException::render()
        $exceptions->renderable(function (BaseException $e, Request $request) {
            if ($request->expectsJson()) {
                return $e->render();
            }
        });

        $exceptions->renderable(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    'Validation failed.',
                    422,
                    $e->errors()
                );
            }
        });

        // Map Laravel's ModelNotFoundException → domain-specific exceptions
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                $previous = $e->getPrevious();

                if ($previous instanceof ModelNotFoundException) {
                    $modelId = intval(Arr::first($previous->getIds(), default: 0));

                    $exception = match ($previous->getModel()) {
                        Lead::class => new LeadNotFoundException($modelId),
                        Manager::class => new ManagerNotFoundException($modelId),
                        default => null,
                    };

                    if ($exception) {
                        return $exception->render();
                    }
                }

                return ApiResponse::error('Resource not found.', 404);
            }
        });
    })->create();
