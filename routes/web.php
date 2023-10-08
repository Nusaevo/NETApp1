<?php

use App\Http\Controllers\Account\SettingsController;
use App\Http\Controllers\Auth\SocialiteLoginController;
use App\Http\Controllers\Documentation\ReferencesController;
use App\Http\Controllers\Logs\AuditLogsController;
use App\Http\Controllers\Logs\SystemLogsController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\UsersController;

use App\Http\Livewire\Settings\Users\Index as UsersIndex;
use App\Http\Livewire\Settings\Users\Detail as UsersDetail;

use App\Http\Livewire\Settings\ConfigGroups\Index as ConfigGroupIndex;
use App\Http\Livewire\Settings\ConfigGroups\Detail as ConfigGroupDetail;
use App\Http\Livewire\Settings\ConfigMenus\Index as ConfigMenuIndex;
use App\Http\Livewire\Settings\ConfigMenus\Detail as ConfigMenuDetail;

// use App\Http\Livewire\Masters\Suppliers\Index as SupplierIndex;
// use App\Http\Livewire\Masters\Customers\Index as CustomerIndex;

// use App\Http\Livewire\Masters\Items\Index as ItemIndex;
// use App\Http\Livewire\Masters\ItemPrices\Index as ItemPriceIndex;
// use App\Http\Livewire\Masters\Items\Detail as ItemDetail;

// use App\Http\Livewire\Masters\Payments\Index as PaymentIndex;
// use App\Http\Livewire\Masters\CategoryItems\Index as CategoryItemIndex;
// use App\Http\Livewire\Masters\VariantCategories\Index as VariantCategoryIndex;

// use App\Http\Livewire\Masters\Stores\Index as StoreIndex;
// use App\Http\Livewire\Masters\Stores\Transfer as WarehouseTransfer;

// use App\Http\Livewire\Inventory\StockOpname\Index as StockOpnameIndex;
// use App\Http\Controllers\CustomerSearchController;
// use App\Http\Controllers\ItemSearchController;

use Illuminate\Support\Facades\Route;

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

// Documentations pages
Route::prefix('documentation')->group(function () {
    Route::get('getting-started/references', [ReferencesController::class, 'index']);
    Route::get('getting-started/changelog', [PagesController::class, 'index']);
});

Route::middleware('auth')->group(function () {
    // Account pages
    // Route::prefix('account')->group(function () {
    //     Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    //     Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    //     Route::put('settings/email', [SettingsController::class, 'changeEmail'])->name('settings.changeEmail');
    //     Route::put('settings/password', [SettingsController::class, 'changePassword'])->name('settings.changePassword');
    // });

    // Logs pages
    Route::prefix('log')->name('log.')->group(function () {
        Route::resource('system', SystemLogsController::class)->only(['index', 'destroy']);
        Route::resource('audit', AuditLogsController::class)->only(['index', 'destroy']);
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', UsersIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', UsersDetail::class)->name('detail');
    });

    Route::prefix('config_groups')->name('config_groups.')->group(function () {
        Route::get('/', ConfigGroupIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', ConfigGroupDetail::class)->name('detail');
    });

    Route::prefix('config_menus')->name('config_menus.')->group(function () {
        Route::get('/', ConfigMenuIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', ConfigMenuDetail::class)->name('detail');
    });
});

// Route::resource('users', UsersController::class);

// /**
//  * Socialite login using Google service
//  * https://laravel.com/docs/8.x/socialite
//  */
// Route::get('/auth/redirect/{provider}', [SocialiteLoginController::class, 'redirect']);
// Route::get('search-customer', [CustomerSearchController::class, 'selectSearch']);
// Route::get('search-item', [ItemSearchController::class, 'selectSearch']);
require __DIR__ . '/auth.php';
