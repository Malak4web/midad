<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Catch lazy loading and silently discarded attributes while developing.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Roles (BE-22): staff runs orders and stock; admin also manages settings, coupons and people.
        Gate::define('staff', fn (User $u) => $u->isStaff());
        Gate::define('admin', fn (User $u) => $u->isAdmin());

        // Auth links land on the SPA, not on Laravel views.
        $front = rtrim(config('app.frontend_url'), '/');
        ResetPassword::createUrlUsing(fn ($user, string $token) => "{$front}/reset-password?token={$token}&email=".urlencode($user->email));
        VerifyEmail::createUrlUsing(function ($user) use ($front) {
            // relative signature: the SPA proxies /api, so the host differs from APP_URL
            $api = URL::temporarySignedRoute('verification.verify', now()->addHours(24), ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())], false);

            return "{$front}/verify-email?url=".urlencode($api);
        });

        // Rate limits (BE-21)
        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()),
            Limit::perMinute(20)->by($r->ip()),
        ]);
        RateLimiter::for('auth-mail', fn (Request $r) => Limit::perMinute(3)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
        RateLimiter::for('checkout', fn (Request $r) => Limit::perMinute((int) config('app.checkout_per_minute'))->by($r->user()?->id ?: $r->ip()));
    }
}
