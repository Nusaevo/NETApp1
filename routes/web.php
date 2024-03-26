<?php

use App\Http\Controllers\Account\SettingsController;
use App\Http\Controllers\Auth\SocialiteLoginController;
use App\Http\Controllers\Documentation\ReferencesController;
use App\Http\Controllers\Logs\AuditLogsController;
use App\Http\Controllers\Logs\SystemLogsController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CustomerSearchController;
use App\Http\Controllers\ItemSearchController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return redirect('index');
// });

$menu = theme()->getMenu();
array_walk($menu, function ($val) {
    if (isset($val['path'])) {
        $route = Route::get($val['path'], [PagesController::class, 'index']);

        // Exclude documentation from auth middleware
        if (!Str::contains($val['path'], 'documentation')) {
            $route->middleware('auth');
        }
    }
});

Route::middleware('auth')->group(function () {
    $livewireComponents = [];
    $livewireDirectory = app_path('Http/Livewire');
    $namespaceBase = 'App\Http\Livewire';
    $excludeDirectory = $livewireDirectory . DIRECTORY_SEPARATOR . 'Components';

    if (File::isDirectory($livewireDirectory)) {
        $componentFiles = File::allFiles($livewireDirectory, true);

        foreach ($componentFiles as $file) {

            if (Str::startsWith($file->getPath(), $excludeDirectory)) {
                continue;
            }

            $relativeClassPath = Str::after($file->getRealPath(), realpath($livewireDirectory) . DIRECTORY_SEPARATOR);
            $relativeClassPath = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativeClassPath);

            $class = $namespaceBase . '\\' . $relativeClassPath;

            if (class_exists($class)) {
                $livewireComponents[] = $class;
            }
        }
    }

    foreach ($livewireComponents as $componentClass) {
        if (Str::contains($componentClass, ['DataTable', 'Component'])) {
            continue;
        }

        $componentPath = Str::of($componentClass)->after("$namespaceBase\\")->replace('\\', '/');
        $componentParts = explode('/', $componentPath); // Split the path into parts

        if (count($componentParts) > 1) {
            array_pop($componentParts); // Remove the last part
            $componentPath = implode('/', $componentParts); // Re-join the remaining parts
        }

        $componentName = class_basename($componentClass);
        $routeName = Str::replace('/', '.', $componentPath);
        if (Str::endsWith($componentName, 'Index')) {
            Route::get("/{$componentPath}", $componentClass)->name("{$routeName}");
        }
        else if (Str::endsWith($componentName, 'Detail')) {
            Route::get("/{$componentPath}/Detail/{action}/{objectId?}", $componentClass)->name("{$routeName}.Detail");
        }
        else {
            Route::get("/{$componentPath}", $componentClass)->name("{$routeName}.{$componentName}");
        }

    }

    Route::get('/', function () {
        $app_code = Session::get('app_code');
        return redirect($app_code ? '/' . $app_code . '/Home' : '/');
    });
    // Additional non-standard routes go here
});
// Route::resource('users', UsersController::class);

// /**
//  * Socialite login using Google service
//  * https://laravel.com/docs/8.x/socialite
//  */
Route::get('/auth/redirect/{provider}', [SocialiteLoginController::class, 'redirect']);
Route::get('search-item', [ItemSearchController::class, 'selectSearch']);
Route::get('search-customer', [CustomerSearchController::class, 'selectSearch']);
require __DIR__ . '/auth.php';
