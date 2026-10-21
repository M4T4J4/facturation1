<?php

namespace App\Providers;

use App\Auth\AdminUserProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        Auth::provider('eloquent_with_configured_admin', function (Application $app, array $config): AdminUserProvider {
            return new AdminUserProvider($app['hash']->driver(), $config['model']);
        });

        RateLimiter::for('login', function (Request $request) {
            $identifier = Str::transliterate(Str::lower($request->string('identifier')->toString()));

            return Limit::perMinute(5)->by($identifier.'|'.$request->ip());
        });
    }
}
