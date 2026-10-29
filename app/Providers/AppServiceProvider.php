<?php

namespace App\Providers;

use App\Services\CinemaSettings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (file_exists(app_path('helpers.php'))) {
            require_once app_path('helpers.php');
        }

        $this->app->singleton(CinemaSettings::class, function () {
            return new CinemaSettings;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.public', 'layouts.admin', 'public.*', 'admin.*'], function ($view) {
            $view->with('cinemaSettings', app(CinemaSettings::class));
        });
    }
}
