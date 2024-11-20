<?php

use App\Models\SysConfig1\ConfigMenu;
use App\Models\SysConfig1\ConfigUser;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigRight;
use Illuminate\Support\Facades\Config;
use App\Models\SysConfig1\ConfigAppl;
use Illuminate\Support\Facades\Session;
use App\Enums\Constant;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

if (!function_exists('populateArrayFromModel')) {
    /**
     * Populate an array with all column values from a model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    function populateArrayFromModel($model)
    {
        $data = [];
        $attributes = $model->getAllColumns();

        foreach ($attributes as $attribute) {
            $value = $model->getAllColumnValues($attribute);
            $data[$attribute] = $value;
        }
        return $data;
    }
}

if (!function_exists('imagePath')) {
    /**
     * Get the path of an image file.
     *
     * @param string $imageName
     * @return string
     */
    function imagePath($imageName)
    {
        return asset('customs/images/' . $imageName);
    }
}

if (!function_exists('isNullOrEmptyString')) {
    /**
     * Get string
     *
     * @return void
     */
    function isNullOrEmptyString($str)
    {
        return !isset($str) || trim($str) === '';
    }
}

if (!function_exists('isNullOrEmptyNumber')) {
    /**
     * Check if a number is null, empty or zero
     *
     * @param mixed $num
     * @return bool
     */
    function isNullOrEmptyNumber($num)
    {
        return !isset($num) || $num === null || $num === '' || $num == 0;
    }
}

if (!function_exists('isNullOrEmptyDateTime')) {
    /**
     * Check if a date/time is null, empty, or invalid
     *
     * @param mixed $date
     * @return bool
     */
    function isNullOrEmptyDateTime($date)
    {
        if (!isset($date) || $date === null || $date === '') {
            return true;
        }

        // Use DateTime to validate the date format
        try {
            $dt = new DateTime($date);
            return false;
        } catch (Exception $e) {
            return true;
        }
    }
}

if (!function_exists('initDatabaseConnection')) {
    /**
     * Initialize the database connection dynamically based on session data.
     */
    function initDatabaseConnection()
    {
        $currentDatabase = config('database.connections.main.database');
        $sessionDatabase = Session::get('database');

        // Set the database connection if the session database value is different
        if ($sessionDatabase && $currentDatabase !== $sessionDatabase) {
            Config::set('database.connections.main.database', $sessionDatabase);
            Artisan::call('config:clear');
        }
    }
}

if (!function_exists('getViewPath')) {
    function getViewPath($namespace, $className)
    {
        // Remove 'App' prefix if it exists in the namespace
        $namespaceWithoutApp = preg_replace('/^App\\\\/', '', $namespace);

        // Format the namespace to a dot-separated path with class name
        $baseRoute = $namespaceWithoutApp . '/' . $className;
        $baseRoute = str_replace('\\', '/', $baseRoute);
        $baseRoute = str_replace('/', '.', $baseRoute);

        // Retrieve the route using ConfigMenu::getRoute
        return ConfigMenu::getRoute($baseRoute);
    }
}

if (!function_exists('registerDynamicConnections')) {
    /**
     * Register dynamic database connections.
     */
    function registerDynamicConnections()
    {
        // Define base connection manually to avoid reliance on Config::get
        $baseConnection = [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', ''), // Default database, may not be used for dynamic connections
            'username' => env('DB_ENCRYPTED_USERNAME') ? Crypt::decrypt(env('DB_ENCRYPTED_USERNAME')) : env('DB_USERNAME'),
            'password' => env('DB_ENCRYPTED_PASSWORD') ? Crypt::decrypt(env('DB_ENCRYPTED_PASSWORD')) : env('DB_PASSWORD'),
            'charset' => 'utf8',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ];

        // Set base connection credentials globally
        Config::set('database.connections.pgsql', $baseConnection);
        $configConnectionName = Constant::ConfigConn();

        try {
            // Fetch dynamic database configurations
            $configApps = DB::connection($configConnectionName)->select('SELECT code, db_name FROM config_appls');
        } catch (\Exception $e) {
            return; // Exit if fetching fails
        }
        // Add logging to track connections being registered

        foreach ($configApps as $app) {
            // Skip invalid configurations
            if (empty($app->db_name)) {
                continue;
            }

            // Create new connection by merging with the base connection
            $newConnection = array_merge($baseConnection, [
                'database' => $app->db_name, // Use dynamic database name
            ]);

            // Dynamically set the connection
            Config::set("database.connections.{$app->code}", $newConnection);

            // Log each registered connection
        }
    }
}
