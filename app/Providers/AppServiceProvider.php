<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Database
        $this->app->singleton(\App\Library\Db\DB::class, function ($app) {
            $db_config = [
                'hostname' => env('DB_HOST'),
                'port' => env('DB_PORT'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'database' => env('DB_DATABASE'),
                'driver' => 'pdo_mysql',
                'charset' => 'utf8mb4',
            ];

            return new \App\Library\Db\DB($db_config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
