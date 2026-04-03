<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
        RateLimiter::for('bookings', function (Request $request) {
            return Limit::perMinute(5)->by(
                // are they logged in limit by their user name if not limit by their Ip Address
                $request->user()?->id ?: $request->ip()
            );
        });
        RateLimiter::for('signup', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });
        RateLimiter::for('login', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });
    }



}
