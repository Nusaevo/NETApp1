<?php

use App\Models\SysConfig1\ConfigMenu;
use App\Models\SysConfig1\ConfigUser;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigRight;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use App\Models\SysConfig1\ConfigAppl;
use App\Enums\Constant;
if (!function_exists('addDynamicConnections')) {
    /**
     * Dynamically add database connections based on a hardcoded query.
     */
    function addDynamicConnections()
    {
        // Use ConfigConn() to fetch configurations from the correct database connection
        $configConnectionName = Constant::ConfigConn();

        // Execute a hardcoded query to fetch all ConfigAppl records
        $configApps = DB::connection($configConnectionName)->select('SELECT code, db_name FROM config_appls');

        foreach ($configApps as $app) {
            // Ensure the app_code and database name are valid
            if (empty($app->code) || empty($app->db_name)) {
                continue; // Skip if app_code or db_name is not provided
            }

            // Retrieve the base connection using AppConn
            $baseConnection = Config::get("database.connections." . Constant::ConfigConn());

            // Build a new connection array by overriding dynamic values
            $newConnection = array_merge($baseConnection, [
                'driver'   => $baseConnection['driver'] ?? 'pgsql',          // Default driver
                'database' => $app->db_name,                                 // Dynamic database name
                'host'     => $app->host ?? $baseConnection['host'],         // Optional: Override host
                'port'     => $app->port ?? $baseConnection['port'],         // Optional: Override port
                'username' => $app->username ?? $baseConnection['username'], // Optional: Override username
                'password' => $app->password ?? $baseConnection['password'], // Optional: Override password
                'charset'  => $baseConnection['charset'] ?? 'utf8',          // Character set
                'prefix'   => $baseConnection['prefix'] ?? '',               // Table prefix
                'schema'   => $baseConnection['schema'] ?? 'public',         // Schema for PostgreSQL
                'sslmode'  => $baseConnection['sslmode'] ?? 'prefer',        // SSL mode for PostgreSQL
            ]);

            // Use app_code as the connection name
            Config::set("database.connections.{$app->code}", $newConnection);
        }
    }
}

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
    function isNullOrEmptyString($str) {
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
    function isNullOrEmptyNumber($num) {
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
    function isNullOrEmptyDateTime($date) {
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
