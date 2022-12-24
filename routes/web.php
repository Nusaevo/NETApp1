<?php

use App\Http\Controllers\Account\SettingsController;
use App\Http\Controllers\Auth\SocialiteLoginController;
use App\Http\Controllers\Documentation\ReferencesController;
use App\Http\Controllers\Logs\AuditLogsController;
use App\Http\Controllers\Logs\SystemLogsController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\UsersController;

use App\Http\Livewire\Masters\Suppliers\Index as SupplierIndex;
use App\Http\Livewire\Masters\Customers\Index as CustomerIndex;

use App\Http\Livewire\Masters\Items\Index as ItemIndex;
use App\Http\Livewire\Masters\ItemPrices\Index as ItemPriceIndex;
use App\Http\Livewire\Masters\Items\Detail as ItemDetail;

use App\Http\Livewire\Masters\Payments\Index as PaymentIndex;
use App\Http\Livewire\Masters\Units\Index as UnitIndex;
use App\Http\Livewire\Masters\CategoryItems\Index as CategoryItemIndex;
use App\Http\Livewire\Masters\PriceCategories\Index as PriceCategoryIndex;

use App\Http\Livewire\Masters\Warehouses\Index as WarehouseIndex;
use App\Http\Livewire\Transactions\Transfers\Index as WarehouseTransfer;


use App\Http\Livewire\Transactions\Sales\Orders\Index as SalesOrderIndex;
use App\Http\Livewire\Transactions\Sales\Orders\Create as SalesOrderCreate;
use App\Http\Livewire\Transactions\Sales\Orders\Detail as SalesOrderDetail;
use App\Http\Livewire\Transactions\Sales\Orders\PrintPdf as SalesOrderPrintPdf;

use App\Http\Livewire\Inventory\StockOpname\Index as StockOpnameIndex;
use App\Http\Controllers\CustomerSearchController;
use App\Http\Controllers\ItemSearchController;

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
    Route::prefix('supplier')->name('supplier.')->group(function () {
        Route::get('/', SupplierIndex::class)->name('index');
    });
    Route::prefix('customer')->name('customer.')->group(function () {
        Route::get('/', CustomerIndex::class)->name('index');
    });

    Route::prefix('item')->name('item.')->group(function () {
        Route::get('/', ItemIndex::class)->name('index');
        Route::get('/detail/{id}', ItemDetail::class)->name('detail');
        Route::get('/price', ItemPriceIndex::class)->name('price');
    });
    Route::prefix('unit')->name('unit.')->group(function () {
        Route::get('/', UnitIndex::class)->name('index');
    });
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/', PaymentIndex::class)->name('index');
    });
    Route::prefix('item_category')->name('item_category.')->group(function () {
        Route::get('/', CategoryItemIndex::class)->name('index');
    });
    Route::prefix('price_category')->name('price_category.')->group(function () {
        Route::get('/', PriceCategoryIndex::class)->name('index');
    });
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', WarehouseIndex::class)->name('index');
        Route::get('/transfer', WarehouseTransfer::class)->name('index');
    });
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/stock_opname', StockOpnameIndex::class)->name('index');
    });
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::prefix('order')->name('order.')->group(function () {
            Route::get('/', SalesOrderIndex::class)->name('index');
            Route::get('/create', SalesOrderCreate::class)->name('create');
            Route::get('/detail/{id}', SalesOrderDetail::class)->name('detail');
            Route::get('/printpdf/{id}', SalesOrderPrintpdf::class)->name('printpdf');
        });
    });
});

Route::resource('users', UsersController::class);

/**
 * Socialite login using Google service
 * https://laravel.com/docs/8.x/socialite
 */
Route::get('/auth/redirect/{provider}', [SocialiteLoginController::class, 'redirect']);
Route::get('search-customer', [CustomerSearchController::class, 'selectSearch']);
Route::get('search-item', [ItemSearchController::class, 'selectSearch']);
require __DIR__ . '/auth.php';
