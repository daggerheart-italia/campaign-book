<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RuntimeException;

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
        $version = file_get_contents(base_path('VERSION'));

        if ($version === false) {
            throw new RuntimeException('Unable to read the application version from VERSION.');
        }

        config(['app.version' => trim($version)]);
    }
}
