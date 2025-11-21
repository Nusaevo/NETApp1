<?php

use App\Http\Controllers\Apps\PermissionManagementController;
use App\Http\Controllers\Apps\RoleManagementController;
use App\Http\Controllers\Apps\UserManagementController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Utils\PasswordEncryptionController;
use App\Http\Controllers\DeviceCheckController;

use App\Http\Controllers\DropdownSearchController;
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

// Device Check Routes - publicly accessible
// Route::get('/checkdevice', [DeviceCheckController::class, 'index'])->name('device.check');
// Route::get('/checkdevice/status', [DeviceCheckController::class, 'checkDevice'])->name('device.check.status');
// Route::post('/checkdevice/register', [DeviceCheckController::class, 'registerDevice'])->name('device.register');

Route::middleware('auth')->group(function () {
    // Dropdown Search Route
    //Route::get('/search-dropdown', [DropdownSearchController::class, 'search'])->name('dropdown.search');

    $livewireComponents = [];
    $livewireDirectory = app_path('Livewire');
    $namespaceBase = 'App\Livewire';
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
            Route::get("/{$componentPath}/Detail/{action}/{objectId?}/{additionalParam?}", $componentClass)->name("{$routeName}.Detail");
        }
        else if (Str::endsWith($componentName, 'PrintPdf')) {
            Route::get("/{$componentPath}/PrintPdf/{action}/{objectId?}/{additionalParam?}", $componentClass)->name("{$routeName}.PrintPdf");
        }
        else {
            Route::get("/{$componentPath}", $componentClass)->name("{$routeName}.{$componentName}");
        }

    }

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::name('user-management.')->group(function () {
        Route::resource('/user-management/users', UserManagementController::class);
        Route::resource('/user-management/roles', RoleManagementController::class);
        Route::resource('/user-management/permissions', PermissionManagementController::class);
    });


    // Route::get('/', [DashboardController::class, 'index']);
    Route::get('/', function () {
        $app_code = Session::get('app_code');
        if (empty($app_code)) {
            return redirect('/SysConfig1/Error');
        }
        return redirect('/' . $app_code . '/Home');
    });

    // Additional non-standard routes go here
});


Route::get('/password-encryption', [PasswordEncryptionController::class, 'showEncryptionForm'])->name('password.encryption.form');

Route::get('/error', function () {
    abort(500);
});

Route::get('/auth/redirect/{provider}', [SocialiteController::class, 'redirect']);

require __DIR__ . '/auth.php';
