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
        $configConnectionName = Constant::ConfigConn();

        // Log the connection name
        Log::info('Config Connection Name:', [$configConnectionName]);

        $baseConnection = Config::get("database.connections.{$configConnectionName}");

        // Log the base connection
        Log::info('Base Connection:', $baseConnection);

        // Execute the query
        try {
            $configApps = DB::connection($configConnectionName)->select('SELECT code, db_name FROM config_appls');
            Log::info('Fetched Config Apps:', $configApps);
        } catch (\Exception $e) {
            Log::error('Error Fetching Config Apps:', ['error' => $e->getMessage()]);
            throw $e;
        }

        foreach ($configApps as $app) {
            // Log each app's config
            Log::info('App Config:', (array) $app);

            $newConnection = array_merge($baseConnection, [
                'driver'   => 'pgsql',
                'database' => $app->db_name,
                'host'     => $baseConnection['host'],
                'port'     => $baseConnection['port'],
                'username' => $baseConnection['username'],
                'password' => $baseConnection['password'],
            ]);

            // Log the new connection
            Log::info('New Connection:', $newConnection);

            // Dynamically set the connection
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
