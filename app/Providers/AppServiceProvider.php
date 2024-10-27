<?php

namespace App\Providers;

use App\Core\KTBootstrap;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $dbUsername = Crypt::decrypt(env('DB_USERNAME'));
        $dbPassword = Crypt::decrypt(env('DB_PASSWORD'));

        // Set konfigurasi database secara manual
        Config::set('database.connections.pgsql.username', $dbUsername);
        Config::set('database.connections.pgsql.password', $dbPassword);
        Config::set('database.connections.main.username', $dbUsername);
        Config::set('database.connections.main.password', $dbPassword);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Update defaultStringLength
        Builder::defaultStringLength(191);

        KTBootstrap::init();
    }
}
