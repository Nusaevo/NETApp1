<?php

use App\Http\Controllers\Account\SettingsController;
use App\Http\Controllers\Auth\SocialiteLoginController;
use App\Http\Controllers\Documentation\ReferencesController;
use App\Http\Controllers\Logs\AuditLogsController;
use App\Http\Controllers\Logs\SystemLogsController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\UsersController;

use App\Http\Livewire\Settings\ConfigUsers\Index as ConfigUsersIndex;
use App\Http\Livewire\Settings\ConfigUsers\Detail as ConfigUsersDetail;
use App\Http\Livewire\Settings\ConfigGroups\Index as ConfigGroupIndex;
use App\Http\Livewire\Settings\ConfigGroups\Detail as ConfigGroupDetail;
use App\Http\Livewire\Settings\ConfigMenus\Index as ConfigMenuIndex;
use App\Http\Livewire\Settings\ConfigMenus\Detail as ConfigMenuDetail;
use App\Http\Livewire\Settings\ConfigApplications\Index as ConfigApplicationIndex;
use App\Http\Livewire\Settings\ConfigApplications\Detail as ConfigApplicationDetail;
use App\Http\Livewire\Settings\ConfigRights\Index as ConfigRightIndex;
use App\Http\Livewire\Settings\ConfigRights\Detail as ConfigRightDetail;


use App\Http\Livewire\Masters\Customers\Index as CustomerIndex;
use App\Http\Livewire\Masters\Customers\Detail as CustomerDetail;

use App\Http\Livewire\Masters\CategoryItems\Index as CategoryItemIndex;
use App\Http\Livewire\Masters\CategoryItems\Detail as CategoryItemDetail;

use App\Http\Livewire\Masters\Suppliers\Index as SupplierIndex;
use App\Http\Livewire\Masters\Suppliers\Detail as SupplierDetail;

use App\Http\Livewire\Masters\Units\Index as UnitIndex;
use App\Http\Livewire\Masters\Units\Detail as UnitDetail;

use App\Http\Livewire\Masters\PriceCategories\Index as PriceCategoryIndex;
use App\Http\Livewire\Masters\PriceCategories\Detail as PriceCategoryDetail;

use App\Http\Livewire\Transactions\PurchasesOrders\Index as PurchasesOrderIndex;
use App\Http\Livewire\Transactions\PurchasesOrders\Detail as PurchasesOrderDetail;

use App\Http\Livewire\Transactions\PurchasesDeliveries\Index as PurchasesDeliveryIndex;
use App\Http\Livewire\Transactions\PurchasesDeliveries\Detail as PurchasesDeliveryDetail;

use App\Http\Livewire\Masters\Items\Index as ItemIndex;
use App\Http\Livewire\Masters\Items\Detail as ItemDetail;

use App\Http\Livewire\Masters\ItemPrices\Index as ItemPriceIndex;
use App\Http\Livewire\Masters\ItemPriceLogs\Index as ItemPriceLogIndex;

use App\Http\Livewire\Masters\Items\Details\PrintBarcode as ItemPrintBarcode;


use App\Http\Livewire\Masters\Payments\Index as PaymentIndex;
use App\Http\Livewire\Masters\Payments\Detail as PaymentDetail;

use App\Http\Livewire\Masters\Warehouses\Index as WarehouseIndex;
use App\Http\Livewire\Masters\Warehouses\Detail as WarehousesDetail;
use App\Http\Livewire\Transactions\Transfers\Index as WarehouseTransfer;
use App\Http\Livewire\Inventory\StockOpname\Index as StockOpnameIndex;
use App\Http\Livewire\Inventory\StockOpnameLog\Index as StockOpnameLogIndex;
use App\Http\Livewire\Transactions\Sales\Orders\Index as SalesOrderIndex;
use App\Http\Livewire\Transactions\Sales\Orders\Create as SalesOrderCreate;
use App\Http\Livewire\Transactions\Sales\Orders\Detail as SalesOrderDetail;
use App\Http\Livewire\Transactions\Sales\Orders\PrintPdf as SalesOrderPrintPdf;

use App\Http\Livewire\Transactions\Sales\WarehouseOrders\Index as WareHouseOrderIndex;
use App\Http\Livewire\Transactions\Sales\WarehouseOrders\Create as WareHouseOrderCreate;
use App\Http\Livewire\Transactions\Sales\WarehouseOrders\PrintPdf as WareHouseOrderPrintPdf;

use App\Http\Livewire\Transactions\Sales\FinishedOrders\Index as FinishedOrderIndex;
use App\Http\Livewire\Transactions\Sales\FinishedOrders\Edit as FinishedOrderEdit;
use App\Http\Livewire\Transactions\Sales\FinishedOrders\PrintSmallPdf as FinishedOrderPrintSmallPdf;
use App\Http\Livewire\Transactions\Sales\FinishedOrders\PrintBigPdf as FinishedOrderPrintBigPdf;
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

    Route::prefix('config_users')->name('config_users.')->group(function () {
        Route::get('/', ConfigUsersIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', ConfigUsersDetail::class)->name('detail');
    });

    Route::prefix('config_groups')->name('config_groups.')->group(function () {
        Route::get('/', ConfigGroupIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', ConfigGroupDetail::class)->name('detail');
    });

    Route::prefix('config_menus')->name('config_menus.')->group(function () {
        Route::get('/', ConfigMenuIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', ConfigMenuDetail::class)->name('detail');
    });

    Route::prefix('config_applications')->name('config_applications.')->group(function () {
        Route::get('/', ConfigApplicationIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', ConfigApplicationDetail::class)->name('detail');
    });

    Route::prefix('config_rights')->name('config_rights.')->group(function () {
        Route::get('/', ConfigRightIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', ConfigRightDetail::class)->name('detail');
    });

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', CustomerIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', CustomerDetail::class)->name('detail');
    });

    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', SupplierIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', SupplierDetail::class)->name('detail');
    });

    Route::prefix('items')->name('items.')->group(function () {
        Route::get('/', ItemIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', ItemDetail::class)->name('detail');
        // Route::prefix('detail')->name('detail.')->group(function () {
        //     Route::get('/printbarcode/{id}', ItemPrintBarcode::class)->name('print_barcode');
        // });
    });

    Route::prefix('units')->name('units.')->group(function () {
        Route::get('/', UnitIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', UnitDetail::class)->name('detail');
    });

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', PaymentIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', PaymentDetail::class)->name('detail');
    });

    Route::prefix('item_category')->name('item_category.')->group(function () {
        Route::get('/', CategoryItemIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', CategoryItemDetail::class)->name('detail');
    });

    Route::prefix('price_category')->name('price_category.')->group(function () {
        Route::get('/', PriceCategoryIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', PriceCategoryDetail::class)->name('detail');
    });

    Route::prefix('warehouses')->name('warehouses.')->group(function () {
        Route::get('/', WarehouseIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', WarehousesDetail::class)->name('detail');
        // Route::get('/transfer', WarehouseTransfer::class)->name('index');
    });

    Route::prefix('inventory_stock_opname')->name('inventory_stock_opname.')->group(function () {
        Route::get('/', StockOpnameIndex::class)->name('index');
    });

    Route::prefix('inventory_stock_opname_log')->name('inventory_stock_opname_log.')->group(function () {
        Route::get('/', StockOpnameLogIndex::class)->name('index');
    });

    Route::prefix('item_price')->name('inventory_stock_opname.')->group(function () {
        Route::get('/', ItemPriceIndex::class)->name('index');
    });

    Route::prefix('item_price_log')->name('inventory_stock_opname_log.')->group(function () {
        Route::get('/', ItemPriceLogIndex::class)->name('index');
    });

    Route::prefix('purchases_orders')->name('purchases_orders.')->group(function () {
        Route::get('/', PurchasesOrderIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', PurchasesOrderDetail::class)->name('detail');
    });

    Route::prefix('purchases_deliveries')->name('purchases_deliveries.')->group(function () {
        Route::get('/', PurchasesDeliveryIndex::class)->name('index');
        Route::get('/detail/{action}/{objectId?}', PurchasesDeliveryDetail::class)->name('detail');
    });

    Route::prefix('sales_order')->name('sales_order.')->group(function () {
        Route::get('/', SalesOrderIndex::class)->name('index');
        Route::get('/create', SalesOrderCreate::class)->name('create');
        Route::get('/detail/{id}', SalesOrderDetail::class)->name('detail');
        Route::get('/printpdf/{id}', SalesOrderPrintpdf::class)->name('printpdf');
    });

    Route::prefix('sales_order_warehouse')->name('sales_order_warehouse.')->group(function () {
        Route::get('/', WareHouseOrderIndex::class)->name('index');
        Route::get('/create/{id}', WareHouseOrderCreate::class)->name('create');
        Route::get('/printpdf/{id}', WareHouseOrderPrintPdf::class)->name('printpdf');
    });

    Route::prefix('sales_order_final')->name('sales_order_final.')->group(function () {
        Route::get('/', FinishedOrderIndex::class)->name('index');
        Route::get('/printsmallpdf/{id}', FinishedOrderPrintSmallPdf::class)->name('printsmallpdf');
        Route::get('/printbigpdf/{id}', FinishedOrderPrintBigPdf::class)->name('printbigpdf');
        Route::get('/edit/{id}', FinishedOrderEdit::class)->name('edit');
    });
    // Route::prefix('supplier')->name('supplier.')->group(function () {
    //     Route::get('/', ConfigRightIndex::class)->name('index');
    //     Route::get('/detail/{action}/{objectId?}', ConfigRightDetail::class)->name('detail');
    // });
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
