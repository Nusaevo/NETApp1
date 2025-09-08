<div class="modern-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="dashboard-title">
                        <i class="fas fa-gem me-3"></i>Dashboard
                    </h1>
                </div>

            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-section">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stats-card stats-card-primary">
                        <div class="stats-card-body">
                            <div class="stats-info">
                                <div class="stats-value">{{ $salesSummary['today_orders'] ?? 0 }}</div>
                                <div class="stats-label">Total Order Hari Ini</div>
                                <div class="stats-growth positive">
                                    <i class="fas fa-shopping-bag"></i>
                                    Transaksi
                                </div>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="stats-sparkline"></div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stats-card stats-card-success">
                        <div class="stats-card-body">
                            <div class="stats-info">
                                <div class="stats-value">{{ rupiah($salesSummary['today_revenue'] ?? 0) }}</div>
                                <div class="stats-label">Total Penjualan Hari Ini</div>
                                <div class="stats-growth positive">
                                    <i class="fas fa-info-circle"></i>
                                    <small>*Belum termasuk retur & tukar barang</small>
                                </div>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="stats-sparkline"></div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stats-card stats-card-info">
                        <div class="stats-card-body">
                            <div class="stats-info">
                                <div class="stats-value">{{ $salesSummary['month_orders'] ?? 0 }}</div>
                                <div class="stats-label">Order Bulan Ini</div>
                                <div class="stats-growth positive">
                                    <i class="fas fa-calendar"></i>
                                    30 Hari
                                </div>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                        </div>
                        <div class="stats-sparkline"></div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stats-card stats-card-warning">
                        <div class="stats-card-body">
                            <div class="stats-info">
                                <div class="stats-value">{{ rupiah($salesSummary['month_revenue'] ?? 0) }}</div>
                                <div class="stats-label">Total Penjualan Bulan Ini</div>
                                <div class="stats-growth positive">
                                    <i class="fas fa-info-circle"></i>
                                    <small>*Belum termasuk retur & tukar barang</small>
                                </div>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-chart-area"></i>
                            </div>
                        </div>
                        <div class="stats-sparkline"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="content-section">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-xxl-6">
                    <!-- Currency Rate Widget -->
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="card-title">
                                <i class="fas fa-dollar-sign text-primary me-2"></i>
                                Nilai Tukar Harian
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="chart-info mb-3">
                                <div class="text-muted fs-7 fw-bold">Hari Ini: {{ $todayCurrencyRate ?? 'Tidak ada data untuk hari ini' }}</div>
                            </div>
                            <div class="trd-jewel1-chart card-rounded-bottom"
                                 id="currencyRateChart"
                                 data-kt-chart-title="Currency Rate"
                                 data-kt-chart-color="primary"
                                 data-kt-chart-data="{{ json_encode(array_column($currencyRates, 'curr_rate')) }}"
                                 data-kt-chart-categories="{{ json_encode(array_column($currencyRates, 'log_date')) }}"
                                 style="height: 300px;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-6">
                    <!-- Gold Price Widget -->
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="card-title">
                                <i class="fas fa-coins text-warning me-2"></i>
                                Harga Emas Harian (Mata Uang Dasar)
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="chart-info mb-3">
                                <div class="text-muted fs-7 fw-bold">Hari Ini: {{ $todayGoldPrice ?? 'Tidak ada data untuk hari ini' }}</div>
                            </div>
                            <div class="trd-jewel1-chart card-rounded-bottom"
                                 id="goldPriceChart"
                                 data-kt-chart-title="Gold Price"
                                 data-kt-chart-color="warning"
                                 data-kt-chart-data="{{ json_encode(array_column($goldPrices, 'goldprice_basecurr')) }}"
                                 data-kt-chart-categories="{{ json_encode(array_column($goldPrices, 'log_date')) }}"
                                 style="height: 300px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Row -->
            <div class="row g-4 mt-2">
                <!-- Top Products Chart -->
                <div class="col-xl-8 col-lg-12">
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="card-title">
                                <i class="fas fa-trophy text-warning me-2"></i>
                                Produk Perhiasan Terpopuler
                            </div>
                            <div class="card-actions">
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            @if(count($topProducts) > 0)
                                <div class="modern-table-container">
                                    <table class="modern-table">
                                        <thead>
                                            <tr>
                                                <th>Peringkat</th>
                                                <th>Produk</th>
                                                <th>Qty Terjual</th>
                                                <th>Performa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topProducts as $index => $product)
                                            <tr class="table-row-modern">
                                                <td>
                                                    <div class="rank-badge rank-{{ $index < 3 ? $index + 1 : 'other' }}">
                                                        #{{ $index + 1 }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="product-info">
                                                        <div class="product-code">{{ $product['matl_code'] }} - {{ $product['matl_descr'] }}</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="quantity-info">
                                                        <div class="qty-number">{{ $product['total_qty'] }}</div>
                                                        <div class="qty-unit">pcs</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="performance-bar">
                                                        <div class="progress-modern">
                                                            <div class="progress-bar-modern"
                                                                 style="width: {{ $product['total_qty'] > 0 ? min(100, ($product['total_qty'] / max(collect($topProducts)->max('total_qty'), 1)) * 100) : 0 }}%">
                                                            </div>
                                                        </div>
                                                        <span class="performance-text">{{ $product['order_count'] ?? 0 }} order</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <h5>Tidak Ada Data Penjualan</h5>
                                    <p>Mulai berjualan untuk melihat produk terpopuler di sini.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div class="col-xl-4 col-lg-12">
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="card-title">
                                <i class="fas fa-chart-line text-primary me-2"></i>
                                Tren Bulanan
                            </div>
                        </div>
                        <div class="card-body-modern">
                            @if(count($monthlyTrends) > 0)
                                <div class="trends-container">
                                    @foreach($monthlyTrends as $trend)
                                    <div class="trend-item-modern">
                                        <div class="trend-header">
                                            <span class="trend-month">{{ $trend['month'] }}</span>
                                            <span class="trend-value">{{ rupiah($trend['total_revenue']) }}</span>
                                        </div>
                                        <div class="trend-progress">
                                            <div class="progress-track">
                                                <div class="progress-fill"
                                                     style="width: {{ $trend['order_count'] > 0 ? min(100, ($trend['order_count'] / max(collect($monthlyTrends)->max('order_count'), 1)) * 100) : 0 }}%">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="trend-details">
                                            <span class="orders-count">{{ $trend['order_count'] }} order</span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <h5>Tidak Ada Data Tren</h5>
                                    <p>Tren bulanan akan muncul di sini.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row -->
            <div class="row g-4 mt-2">
                <!-- Stock by Category -->
                <div class="col-xl-6 col-lg-12">
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="card-title">
                                <i class="fas fa-layer-group text-info me-2"></i>
                                Kategori Inventori
                            </div>
                        </div>
                        <div class="card-body-modern">
                            @if(count($stockByCategory) > 0)
                                <div class="categories-grid">
                                    @foreach($stockByCategory as $category)
                                    <div class="category-card-modern">
                                        <div class="category-icon">
                                            <i class="fas fa-gem"></i>
                                        </div>
                                        <div class="category-info">
                                            <h6 class="category-name">{{ $category['category_name'] }}</h6>
                                            <p class="category-count">{{ $category['item_count'] }} barang</p>
                                        </div>
                                        <div class="category-indicator">
                                            <div class="indicator-dot"></div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <h5>Tidak Ada Kategori</h5>
                                    <p>Kategori produk akan muncul di sini.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="col-xl-6 col-lg-12">
                    <div class="modern-card">
                        <div class="card-header-modern">
                            <div class="card-title">
                                <i class="fas fa-clock text-success me-2"></i>
                                Aktivitas Terbaru
                            </div>
                        </div>
                        <div class="card-body-modern">
                            @if(count($recentOrders) > 0)
                                <div class="orders-timeline">
                                    @foreach($recentOrders as $order)
                                    <div class="timeline-item">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <div class="order-header">
                                                <span class="order-id">#{{ $order['tr_id'] }}</span>
                                                <span class="order-amount">{{ rupiah($order['total_amt']) }}</span>
                                            </div>
                                            <div class="order-details">
                                                <span class="customer-name">{{ $order['customer'] }}</span>
                                                <span class="order-date">{{ Carbon\Carbon::parse($order['tr_date'])->diffForHumans() }}</span>
                                            </div>
                                            <div class="order-status">
                                                <span class="status-badge status-{{ strtolower($order['status']) }}">
                                                    {{ $order['status'] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <h5>Tidak Ada Order Terbaru</h5>
                                    <p>Order terbaru akan muncul di sini.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* Modern Dashboard Styles for Jewel */
    .modern-dashboard {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    /* Dashboard Header */
    .dashboard-header {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        padding: 2rem 0;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .dashboard-title {
        font-size: 2.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #ffd700 0%, #ffb347 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 0;
    }

    .dashboard-subtitle {
        color: #6c757d;
        font-size: 1.1rem;
        margin: 0.5rem 0 0 0;
        font-weight: 400;
    }

    .btn-modern-primary {
        background: linear-gradient(135deg, #ffd700 0%, #ffb347 100%);
        border: none;
        color: #2d3748;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
    }

    .btn-modern-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        color: #2d3748;
    }

    /* Stats Section */
    .stats-section {
        margin-bottom: 3rem;
    }

    .stats-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
    }

    .stats-card-body {
        padding: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        z-index: 2;
    }

    .stats-value {
        font-size: 2.5rem;
        font-weight: 800;
        color: #2d3748;
        margin-bottom: 0.5rem;
    }

    .stats-label {
        color: #718096;
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 0.75rem;
    }

    .stats-growth {
        font-size: 0.875rem;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
    }

    .stats-growth small {
        font-size: 0.7rem;
        font-weight: 400;
        opacity: 0.8;
        text-align: center;
        line-height: 1.2;
    }

    .stats-growth.positive {
        background: rgba(255, 215, 0, 0.1);
        color: #b7791f;
    }

    .stats-growth.negative {
        background: rgba(245, 101, 101, 0.1);
        color: #e53e3e;
    }

    .stats-growth.neutral {
        background: rgba(113, 128, 150, 0.1);
        color: #718096;
    }

    .stats-icon {
        font-size: 3rem;
        opacity: 0.3;
    }

    .stats-card-primary { border-left: 4px solid #ffd700; }
    .stats-card-success { border-left: 4px solid #48bb78; }
    .stats-card-info { border-left: 4px solid #4299e1; }
    .stats-card-warning { border-left: 4px solid #ed8936; }

    .stats-sparkline {
        height: 4px;
        background: linear-gradient(90deg, rgba(255, 215, 0, 0.2), rgba(255, 215, 0, 0.8));
    }

    /* Modern Cards */
    .modern-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .modern-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
    }

    .card-header-modern {
        padding: 1.5rem 2rem 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #2d3748;
        margin: 0;
    }

    .card-body-modern {
        padding: 1.5rem 2rem 2rem;
    }

    .chart-info {
        text-align: center;
    }

    /* Modern Table */
    .modern-table-container {
        overflow-x: auto;
    }

    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .modern-table th {
        background: rgba(255, 215, 0, 0.1);
        color: #4a5568;
        font-weight: 600;
        padding: 1rem;
        text-align: left;
        border: none;
        font-size: 0.875rem;
    }

    .modern-table th:first-child { border-radius: 10px 0 0 10px; }
    .modern-table th:last-child { border-radius: 0 10px 10px 0; }

    .table-row-modern {
        transition: all 0.2s ease;
    }

    .table-row-modern:hover {
        background: rgba(255, 215, 0, 0.05);
    }

    .modern-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        vertical-align: middle;
    }

    .rank-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        font-weight: 700;
        font-size: 0.875rem;
    }

    .rank-1 { background: linear-gradient(135deg, #ffd700, #ffed4e); color: #744210; }
    .rank-2 { background: linear-gradient(135deg, #c0c0c0, #e2e8f0); color: #2d3748; }
    .rank-3 { background: linear-gradient(135deg, #cd7f32, #ed8936); color: white; }
    .rank-other { background: rgba(113, 128, 150, 0.1); color: #718096; }

    .product-info {
        display: flex;
        flex-direction: column;
    }

    .product-code {
        font-weight: 700;
        color: #2d3748;
        font-size: 0.875rem;
    }

    .product-name {
        color: #718096;
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }

    .quantity-info {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .qty-number {
        font-weight: 700;
        color: #2d3748;
        font-size: 1.1rem;
    }

    .qty-unit {
        color: #718096;
        font-size: 0.75rem;
    }

    .performance-bar {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .progress-modern {
        background: rgba(113, 128, 150, 0.1);
        border-radius: 10px;
        height: 8px;
        overflow: hidden;
    }

    .progress-bar-modern {
        background: linear-gradient(90deg, #ffd700, #ffb347);
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s ease;
    }

    .performance-text {
        font-size: 0.75rem;
        color: #718096;
    }

    /* Trends Container */
    .trends-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .trend-item-modern {
        padding: 1rem;
        border-radius: 12px;
        background: rgba(255, 215, 0, 0.05);
        transition: all 0.2s ease;
    }

    .trend-item-modern:hover {
        background: rgba(255, 215, 0, 0.1);
        transform: translateX(5px);
    }

    .trend-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .trend-month {
        font-weight: 600;
        color: #2d3748;
    }

    .trend-value {
        font-weight: 700;
        color: #b7791f;
        font-size: 0.875rem;
    }

    .trend-progress {
        margin-bottom: 0.5rem;
    }

    .progress-track {
        background: rgba(113, 128, 150, 0.2);
        border-radius: 4px;
        height: 6px;
        overflow: hidden;
    }

    .progress-fill {
        background: linear-gradient(90deg, #ffd700, #ffb347);
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .orders-count {
        font-size: 0.75rem;
        color: #718096;
    }

    /* Categories Grid */
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
    }

    .category-card-modern {
        display: flex;
        align-items: center;
        padding: 1.5rem;
        background: rgba(255, 215, 0, 0.05);
        border-radius: 12px;
        transition: all 0.2s ease;
        border: 1px solid rgba(255, 215, 0, 0.1);
    }

    .category-card-modern:hover {
        background: rgba(255, 215, 0, 0.1);
        transform: translateY(-2px);
    }

    .category-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, #ffd700, #ffb347);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2d3748;
        font-size: 1.25rem;
        margin-right: 1rem;
    }

    .category-info {
        flex: 1;
    }

    .category-name {
        font-weight: 600;
        color: #2d3748;
        margin: 0 0 0.25rem 0;
        font-size: 1rem;
    }

    .category-count {
        color: #718096;
        margin: 0;
        font-size: 0.875rem;
    }

    .category-indicator {
        margin-left: 1rem;
    }

    .indicator-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #ffd700;
    }

    /* Orders Timeline */
    .orders-timeline {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .timeline-item {
        display: flex;
        align-items: flex-start;
        padding: 1rem;
        border-radius: 12px;
        background: rgba(255, 215, 0, 0.05);
        transition: all 0.2s ease;
        border-left: 3px solid #ffd700;
    }

    .timeline-item:hover {
        background: rgba(255, 215, 0, 0.1);
        transform: translateX(5px);
    }

    .timeline-marker {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #ffd700;
        margin-right: 1rem;
        margin-top: 0.25rem;
        flex-shrink: 0;
    }

    .timeline-content {
        flex: 1;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .order-id {
        font-weight: 700;
        color: #2d3748;
        font-size: 1rem;
    }

    .order-amount {
        font-weight: 700;
        color: #b7791f;
        font-size: 0.875rem;
    }

    .order-details {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .customer-name {
        color: #4a5568;
        font-size: 0.875rem;
    }

    .order-date {
        color: #718096;
        font-size: 0.75rem;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-open {
        background: rgba(255, 215, 0, 0.1);
        color: #b7791f;
    }

    /* Empty States */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }

    .empty-icon {
        font-size: 4rem;
        color: rgba(113, 128, 150, 0.3);
        margin-bottom: 1rem;
    }

    .empty-state h5 {
        color: #4a5568;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: #718096;
        font-size: 0.875rem;
        margin: 0;
    }

    /* Content Section */
    .content-section {
        padding-bottom: 3rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .dashboard-title {
            font-size: 2rem;
        }

        .dashboard-header {
            padding: 1rem 0;
        }

        .stats-card-body {
            padding: 1.5rem;
        }

        .stats-value {
            font-size: 2rem;
        }

        .categories-grid {
            grid-template-columns: 1fr;
        }

        .card-header-modern,
        .card-body-modern {
            padding: 1rem;
        }
    }

    @media (max-width: 576px) {
        .dashboard-title {
            font-size: 1.75rem;
        }

        .stats-section {
            margin-bottom: 2rem;
        }

        .modern-table th,
        .modern-table td {
            padding: 0.75rem 0.5rem;
        }
    }
    </style>
</div>

@push('scripts')
<script>
console.log('TrdJewel1 script loaded!');

function initTrdJewel1Charts() {
    console.log('=== Initializing TrdJewel1 charts ===');

    // Check if ApexCharts is available
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts library not found!');
        return;
    } else {
        console.log('ApexCharts is available');
    }

    var charts = document.querySelectorAll('.trd-jewel1-chart');
    console.log('Found charts elements:', charts.length);

    if (charts.length === 0) {
        console.warn('No chart elements found with class .trd-jewel1-chart');
        return;
    }

    [].slice.call(charts).forEach(function(element, index) {
        console.log('Processing chart', index + 1, 'with ID:', element.id);

        try {
            // Get chart configuration from data attributes
            var chartDataAttr = element.getAttribute('data-kt-chart-data');
            var chartCategoriesAttr = element.getAttribute('data-kt-chart-categories');
            var chartTitle = element.getAttribute('data-kt-chart-title') || 'Chart';
            var color = element.getAttribute('data-kt-chart-color') || 'primary';

            console.log('Raw chart data attribute:', chartDataAttr);
            console.log('Raw categories attribute:', chartCategoriesAttr);

            if (!chartDataAttr || !chartCategoriesAttr) {
                console.error('Missing chart data or categories for:', chartTitle);
                element.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><div class="text-muted">Data tidak tersedia</div></div>';
                return;
            }

            var chartData, chartCategories;

            try {
                chartData = JSON.parse(chartDataAttr);
                chartCategories = JSON.parse(chartCategoriesAttr);
            } catch (parseError) {
                console.error('Error parsing JSON data:', parseError);
                element.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><div class="text-muted">Error parsing data</div></div>';
                return;
            }

            console.log('Parsed chart data for ' + chartTitle + ':', chartData);
            console.log('Parsed categories:', chartCategories);

            if (!Array.isArray(chartData) || chartData.length === 0) {
                console.warn('Empty or invalid chart data for:', chartTitle);
                element.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><div class="text-muted">Tidak ada data untuk ditampilkan</div></div>';
                return;
            }

            // Color scheme for jewel dashboard
            var colorScheme = {
                primary: '#007bff',
                warning: '#ffc107',
                success: '#28a745',
                info: '#17a2b8',
                gold: '#ffd700'
            };

            var chartColor = colorScheme[color] || colorScheme.primary;

            var options = {
                series: [{
                    name: chartTitle,
                    data: chartData.map(function(val) { return parseFloat(val) || 0; })
                }],
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                colors: [chartColor],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: chartCategories,
                    labels: {
                        style: {
                            colors: '#718096',
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#718096',
                            fontSize: '12px'
                        },
                        formatter: function(val) {
                            return new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(val);
                        }
                    }
                },
                grid: {
                    borderColor: '#e0e6ed',
                    strokeDashArray: 5
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function(val) {
                            return new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(val);
                        }
                    }
                }
            };

            console.log('Creating chart with options:', options);

            // Clear any existing content
            element.innerHTML = '';

            // Create and render chart
            var chart = new ApexCharts(element, options);
            console.log('Chart instance created, attempting render...');

            chart.render().then(function() {
                console.log('Chart rendered successfully for:', chartTitle);
            }).catch(function(error) {
                console.error('Error rendering chart:', error);
                element.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><div class="text-muted">Error rendering chart: ' + error.message + '</div></div>';
            });

        } catch (error) {
            console.error('Error initializing chart:', error);
            element.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><div class="text-muted">Error initializing chart: ' + error.message + '</div></div>';
        }
    });
}

console.log('Document readyState:', document.readyState);

// Multiple initialization attempts
setTimeout(function() {
    console.log('Timeout 1000ms: Attempting to init charts');
    initTrdJewel1Charts();
}, 1000);

setTimeout(function() {
    console.log('Timeout 2000ms: Attempting to init charts');
    initTrdJewel1Charts();
}, 2000);

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing TrdJewel1 charts...');
        setTimeout(initTrdJewel1Charts, 100);
    });
} else {
    console.log('DOM already loaded, initializing TrdJewel1 charts...');
    setTimeout(initTrdJewel1Charts, 100);
}

// Reinitialize charts after Livewire updates
document.addEventListener('livewire:navigated', function() {
    console.log('Livewire navigated, reinitializing TrdJewel1 charts...');
    setTimeout(initTrdJewel1Charts, 200);
});

// Also try to initialize on window load as backup
window.addEventListener('load', function() {
    console.log('Window loaded, checking charts...');
    setTimeout(function() {
        var charts = document.querySelectorAll('.trd-jewel1-chart');
        console.log('Window load check - found charts:', charts.length);
        var hasEmptyCharts = false;
        charts.forEach(function(chart) {
            if (!chart.innerHTML.trim() || chart.innerHTML.includes('Error') || chart.innerHTML.includes('No data')) {
                hasEmptyCharts = true;
            }
        });

        if (hasEmptyCharts) {
            console.log('Found empty charts on window load, reinitializing...');
            initTrdJewel1Charts();
        }
    }, 500);
});
</script>
@endpush
