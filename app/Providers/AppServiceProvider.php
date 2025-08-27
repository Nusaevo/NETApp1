<?php

namespace App\Providers;

use App\Core\KTBootstrap;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use App\Enums\Constant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        registerDynamicConnections();

        // Register Bootstrap theme
        $this->app->singleton(\App\Core\BootstrapTheme::class, function ($app) {
            return new \App\Core\BootstrapTheme();
        });
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

        // Initialize Bootstrap theme instead of Metronic
        if (class_exists(\App\Core\BootstrapTheme::class)) {
            // Theme initialization for Bootstrap
            $theme = app(\App\Core\BootstrapTheme::class);
            $theme->addHtmlClass('body', 'd-flex flex-column min-vh-100');
            $theme->addHtmlAttribute('body', 'style', 'font-family: "Inter", sans-serif;');
        }
    }
}
