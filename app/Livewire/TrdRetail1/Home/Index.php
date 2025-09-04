<?php
namespace App\Livewire\TrdRetail1\Home;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Transaction\{OrderHdr, OrderDtl};
use App\Models\TrdRetail1\Master\{Material, Category, Stock};
use App\Enums\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;

class Index extends BaseComponent
{
    public $topProducts = [];
    public $stockByCategory = [];
    public $salesSummary = [];
    public $monthlyTrends = [];
    public $recentOrders = [];
    protected $connection;

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->bypassPermissions = true;
        $this->connection = Session::get('app_code');
        $this->loadDashboardData();
    }

    private function loadDashboardData()
    {
        $this->loadTopProducts();
        $this->loadStockByCategory();
        $this->loadSalesSummary();
        $this->loadMonthlyTrends();
        $this->loadRecentOrders();
    }

    private function loadTopProducts()
    {
        $this->topProducts = OrderDtl::on($this->connection)
            ->select('matl_code', 'matl_descr')
            ->selectRaw('SUM(qty) as total_qty, SUM(amt) as amt, COUNT(*) as order_count')
            ->join('order_hdrs', 'order_hdrs.id', '=', 'order_dtls.trhdr_id')
            ->where('order_hdrs.tr_type', 'SO')
            ->where('order_hdrs.status_code', Status::OPEN)
            ->whereDate('order_hdrs.tr_date', '>=', Carbon::now()->subDays(30))
            ->groupBy('matl_code', 'matl_descr')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'matl_code' => $item->matl_code,
                    'matl_descr' => $item->matl_descr,
                    'total_qty' => $item->total_qty,
                    'order_count' => $item->order_count,
                ];
            });
    }

    private function loadStockByCategory()
    {
        $this->stockByCategory = DB::connection($this->connection)
            ->table('materials')
            ->join('matl_uoms', 'matl_uoms.matl_code', '=', 'materials.code')
            ->join('ivt_bals', function($join) {
                $join->on('ivt_bals.matl_code', '=', 'matl_uoms.matl_code')
                     ->on('ivt_bals.matl_uom', '=', 'matl_uoms.matl_uom');
            })
            ->select('materials.category as category_name')
            ->selectRaw('SUM(ivt_bals.qty_oh) as total_qty')
            ->selectRaw('COUNT(DISTINCT CONCAT(ivt_bals.matl_code, \'-\', ivt_bals.matl_uom)) as item_count')
            ->whereNull('materials.deleted_at')
            ->whereNull('matl_uoms.deleted_at')
            ->whereNotNull('materials.category')
            ->groupBy('materials.category')
            ->orderBy('materials.category')
            ->get()
            ->map(function ($item) {
                return [
                    'category_name' => $item->category_name,
                    'item_count' => $item->item_count ?: 0,
                ];
            });
    }

    private function loadSalesSummary()
    {
        $today = Carbon::now();
        $currentMonth = Carbon::now();

        // Today's orders count
        $todayOrders = OrderHdr::on($this->connection)
            ->where('tr_type', 'SO')
            ->whereDate('tr_date', $today->toDateString())
            ->count();

        // Today's revenue
        $todayRevenue = OrderDtl::on($this->connection)
            ->join('order_hdrs', 'order_hdrs.id', '=', 'order_dtls.trhdr_id')
            ->where('order_hdrs.tr_type', 'SO')
            ->whereDate('order_hdrs.tr_date', $today->toDateString())
            ->sum('order_dtls.amt');

        // This month's orders count
        $monthOrders = OrderHdr::on($this->connection)
            ->where('tr_type', 'SO')
            ->whereYear('tr_date', $currentMonth->year)
            ->whereMonth('tr_date', $currentMonth->month)
            ->count();

        // This month's revenue
        $monthRevenue = OrderDtl::on($this->connection)
            ->join('order_hdrs', 'order_hdrs.id', '=', 'order_dtls.trhdr_id')
            ->where('order_hdrs.tr_type', 'SO')
            ->whereYear('order_hdrs.tr_date', $currentMonth->year)
            ->whereMonth('order_hdrs.tr_date', $currentMonth->month)
            ->sum('order_dtls.amt');

        $this->salesSummary = [
            'today_orders' => $todayOrders,
            'today_revenue' => (float) $todayRevenue,
            'month_orders' => $monthOrders,
            'month_revenue' => (float) $monthRevenue,
        ];
    }

    private function loadMonthlyTrends()
    {
        $this->monthlyTrends = OrderDtl::on($this->connection)
            ->join('order_hdrs', 'order_hdrs.id', '=', 'order_dtls.trhdr_id')
            ->select(
                DB::raw('TO_CHAR(order_hdrs.tr_date, \'YYYY-MM\') as month'),
                DB::raw('COUNT(DISTINCT order_hdrs.id) as order_count'),
                DB::raw('SUM(order_dtls.amt) as total_revenue')
            )
            ->where('order_hdrs.tr_type', 'SO')
            ->where('order_hdrs.tr_date', '>=', Carbon::now()->subMonths(6))
            ->groupBy(DB::raw('TO_CHAR(order_hdrs.tr_date, \'YYYY-MM\')'))
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::createFromFormat('Y-m', $item->month)->format('M Y'),
                    'order_count' => $item->order_count,
                    'total_revenue' => $item->total_revenue,
                ];
            });
    }

    private function loadRecentOrders()
    {
        $this->recentOrders = OrderHdr::on($this->connection)
            ->with('Partner')
            ->where('tr_type', 'SO')
            ->where('status_code', Status::OPEN)
            ->orderBy('tr_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                // Calculate total amount from order details
                $totalAmt = OrderDtl::on($this->connection)
                    ->where('trhdr_id', $order->id)
                    ->sum('amt');

                return [
                    'id' => $order->id,
                    'tr_id' => $order->tr_id,
                    'tr_date' => $order->tr_date,
                    'customer' => $order->Partner ? $order->Partner->name : 'N/A',
                    'total_amt' => $totalAmt,
                    'status' => Status::getStatusString($order->status_code),
                ];
            });
    }

   public function render()
    {
        return view('livewire.trd-retail1.home.index');
    }

    #endregion

    #region Component Events
    public function refreshData()
    {
        $this->loadDashboardData();
        $this->dispatch('success', 'Dashboard data refreshed successfully!');
    }
    #endregion
}
