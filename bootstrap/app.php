<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Session\TokenMismatchException;
use App\Models\ActivityLog;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'force.password' => \App\Http\Middleware\ForcePasswordChange::class,
        ]);
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Allow API calls and AJAX requests expecting JSON to maintain JSON response payloads
            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            // Allow standard redirects for form validation errors and guest login prompts
            if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
                return null;
            }

            // Resolve exact HTTP status code
            $status = 500;
            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
            } elseif ($e instanceof ModelNotFoundException) {
                $status = 404;
            } elseif ($e instanceof AuthorizationException) {
                $status = 403;
            } elseif ($e instanceof TokenMismatchException) {
                $status = 419;
            }

            // Actively record server exceptions (500+) to the ActivityLog audit database
            if ($status >= 500) {
                try {
                    $currentUser = auth()->check() ? auth()->user() : null;
                    $performerName = $currentUser ? $currentUser->name : 'GUEST (' . $request->ip() . ')';
                    $errorDetails = Str::limit(
                        get_class($e) . ': ' . $e->getMessage() . ' [Route: ' . $request->method() . ' ' . $request->path() . '] in ' . $e->getFile() . ':' . $e->getLine(),
                        480
                    );

                    ActivityLog::record(
                        'SYSTEM EXCEPTION',
                        $errorDetails,
                        $performerName
                    );
                } catch (\Throwable $logException) {
                    // Fail silently if database connection or schema is the cause of the exception
                }
            }

            // Render the singular universal Medscreen error view
            return response()->view('errors.error', [
                'exception' => $e,
                'status'    => $status,
            ], $status);
        });
    })->create();