<?php

namespace App\Providers;

use App\Services\CinemaSettings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $activeDb = (string) config('database.connections.'.config('database.default').'.database');
        $normalizedDb = str_replace('\\', '/', $activeDb);
        $isQa = str_ends_with($normalizedDb, 'qa.sqlite');
        $isTesting = $this->app->environment('testing') || $activeDb === ':memory:';

        DB::prohibitDestructiveCommands(! $isQa && ! $isTesting);

        Paginator::defaultView('pagination::tailwind');

        View::composer(['layouts.public', 'layouts.admin', 'public.*', 'admin.*'], function ($view) {
            $view->with('cinemaSettings', app(CinemaSettings::class));
        });
    }
}
