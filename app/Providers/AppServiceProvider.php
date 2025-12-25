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
        // Explicitly using the default MySQL connection; shown here to illustrate how different connections can be passed from database.php
        $this->app->singleton(\App\Library\Db\DB::class, function ($app) {
            $db_config = [
                'connection' => 'mysql',
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
