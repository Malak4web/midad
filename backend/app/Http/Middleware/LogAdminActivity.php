<?php

namespace App\Http\Middleware;

use App\Models\AdminActivity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Records every successful admin write (BE-55). Secrets never reach the log. */
class LogAdminActivity
{
    private const HIDDEN = ['password', 'password_confirmation', 'current_password', 'image', 'token'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethodSafe() && $response->isSuccessful() && $request->user()) {
            $route = $request->route();
            $subject = collect($route?->parameters() ?? [])->first();
            AdminActivity::create([
                'user_id' => $request->user()->id,
                'action' => $request->method().' '.($route?->getName() ?? $request->path()),
                'subject_type' => is_object($subject) ? class_basename($subject) : null,
                'subject_id' => is_object($subject) ? $subject->getKey() : (is_numeric($subject) ? (int) $subject : null),
                'changes' => collect($request->except(self::HIDDEN))->map(fn ($v) => is_string($v) ? mb_substr($v, 0, 500) : $v)->all() ?: null,
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}
