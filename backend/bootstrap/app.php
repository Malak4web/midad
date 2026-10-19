<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // One error shape for the SPA (BE-14): {message, errors?}, in Arabic.
        $json = fn (string $message, int $status, array $errors = []) => response()->json(
            array_filter(['message' => $message, 'errors' => $errors ?: null]),
            $status,
        );
        $exceptions->render(function (ValidationException $e, Request $r) use ($json) {
            return $r->is('api/*') ? $json('راجع البيانات المكتوبة.', 422, $e->errors()) : null;
        });
        $exceptions->render(function (AuthenticationException $e, Request $r) use ($json) {
            return $r->is('api/*') ? $json('سجّل الدخول أولاً.', 401) : null;
        });
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $r) use ($json) {
            return $r->is('api/*') ? $json('ليس لديك صلاحية لهذا الإجراء.', 403) : null;
        });
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $r) use ($json) {
            return $r->is('api/*') ? $json('غير موجود.', 404) : null;
        });
        // expired session (CSRF) and tampered or expired signed links, in Arabic like the rest
        $exceptions->render(function (TokenMismatchException $e, Request $r) use ($json) {
            return $r->is('api/*') ? $json('انتهت الجلسة. حدّث الصفحة وحاول مرة أخرى.', 419) : null;
        });
        $exceptions->render(function (InvalidSignatureException $e, Request $r) use ($json) {
            return $r->is('api/*') ? $json('هذا الرابط غير صالح أو انتهت صلاحيته.', 403) : null;
        });
        $exceptions->render(function (HttpException $e, Request $r) use ($json) {
            if (! $r->is('api/*')) {
                return null;
            }

            return match ($e->getStatusCode()) {
                419 => $json('انتهت الجلسة. حدّث الصفحة وحاول مرة أخرى.', 419),
                403 => $json('ليس لديك صلاحية لهذا الإجراء.', 403),
                default => null,
            };
        });
        $exceptions->render(function (ThrottleRequestsException $e, Request $r) use ($json) {
            return $r->is('api/*') ? $json('محاولات كثيرة. انتظر قليلاً ثم حاول مرة أخرى.', 429) : null;
        });
    })->create();
