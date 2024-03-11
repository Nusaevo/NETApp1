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
    $configurations = [
        'ConfigUsers',
        'ConfigGroups',
        'ConfigMenus',
        'ConfigApplications',
        'ConfigConsts',
        'ConfigVars',
    ];

    $masters = [
        'Catalogues',
        'Customers',
        'Suppliers',
        'Materials',
    ];

    $transactions = [
        'PurchasesOrders',
        'PurchasesDeliveries',
        'CartOrders',
        'SalesDeliveries',
    ];

    $allGroups = [
        'Settings' => $configurations,
        'Masters' => $masters,
        'Transactions' => $transactions,
    ];

    foreach ($allGroups as $group => $items) {
        foreach ($items as $item) {
            $indexPath = "App\\Http\\Livewire\\{$group}\\{$item}\\Index";
            $detailPath = "App\\Http\\Livewire\\{$group}\\{$item}\\Detail";
            if (class_exists($indexPath)) {
                Route::get("/$item", $indexPath)->name("$item.Index");
            }
            if (class_exists($detailPath)) {
                Route::get("/$item/detail/{action}/{objectId?}", $detailPath)->name("$item.Detail");
            }
        }
    }
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
