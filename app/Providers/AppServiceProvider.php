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
        // Define base connection manually to avoid reliance on Config::get
        $baseConnection = [
            'driver'   => 'pgsql',
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', ''), // Default database, may not be used for dynamic connections
            'username' => env('DB_ENCRYPTED_USERNAME')
                ? Crypt::decrypt(env('DB_ENCRYPTED_USERNAME'))
                : env('DB_USERNAME'),
            'password' => env('DB_ENCRYPTED_PASSWORD')
                ? Crypt::decrypt(env('DB_ENCRYPTED_PASSWORD'))
                : env('DB_PASSWORD'),
            'charset'  => 'utf8',
            'schema'   => 'public',
            'sslmode'  => 'prefer',
        ];

        // Set base connection credentials globally
        Config::set('database.connections.pgsql', $baseConnection);

        $configConnectionName = Constant::ConfigConn();

        try {
            // Fetch dynamic database configurations
            $configApps = DB::connection($configConnectionName)->select('SELECT code, db_name FROM config_appls');
        } catch (\Exception $e) {
            Log::error('Error Fetching Config Apps: ' . $e->getMessage());
            return; // Exit if fetching fails
        }

        // Add logging to track connections being registered
        Log::info('Base Connection:', $baseConnection);

        foreach ($configApps as $app) {
            // Skip invalid configurations
            if (empty($app->db_name)) {
                Log::warning("Skipping configuration for code {$app->code} due to missing db_name.");
                continue;
            }

            // Create new connection by merging with the base connection
            $newConnection = array_merge($baseConnection, [
                'database' => $app->db_name, // Use dynamic database name
            ]);

            // Dynamically set the connection
            Config::set("database.connections.{$app->code}", $newConnection);

            // Log each registered connection
            Log::info("Registered database connection for {$app->code}:", $newConnection);
        }

        // Log all registered connections at the end
        Log::info('All Registered Database Connections:', Config::get('database.connections'));
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
