<?php

namespace App\Providers;

use App\Support\SettingsSupport;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        View::composer('*', function ($view): void {
            $view->with('portalSettings', SettingsSupport::all());
            $view->with('portalImage', fn (string $key, ?string $fallback = null): ?string => SettingsSupport::imageUrl($key, $fallback));
        });
    }
}
