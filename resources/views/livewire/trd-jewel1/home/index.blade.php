<div>
    @php
    $chartColor = $chartColor ?? 'primary';
    $chartHeight = $chartHeight ?? '300px';
    @endphp

    <div class="row gy-5 g-xl-8">
        <div class="col-xxl-6">
            <!-- Currency Rate Widget -->
            <div class="card card-xxl-stretch mb-5 mb-xl-8">
                <div class="card-body d-flex flex-column p-0">
                    <div class="flex-grow-1 card-p pb-0">
                        <div class="d-flex flex-stack flex-wrap">
                            <div class="me-2">
                                <a href="#" class="text-dark text-hover-primary fw-bolder fs-3">Daily Currency Rates</a>
                                <div class="text-muted fs-7 fw-bold">Today: {{ $todayCurrencyRate ?? 'No data for today' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="trd-jewel1-chart card-rounded-bottom"
                         id="currencyRateChart"
                         data-kt-chart-title="Currency Rate"
                         data-kt-chart-color="primary"
                         data-kt-chart-data="{{ json_encode(array_column($currencyRates, 'curr_rate')) }}"
                         data-kt-chart-categories="{{ json_encode(array_column($currencyRates, 'log_date')) }}"
                         style="height: {{ $chartHeight }};">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-6">
            <!-- Gold Price Widget -->
            <div class="card card-xxl-stretch mb-5 mb-xl-8">
                <div class="card-body d-flex flex-column p-0">
                    <div class="flex-grow-1 card-p pb-0">
                        <div class="d-flex flex-stack flex-wrap">
                            <div class="me-2">
                                <a href="#" class="text-dark text-hover-primary fw-bolder fs-3">Daily Gold Price (Base Currency)</a>
                                <div class="text-muted fs-7 fw-bold">Today: {{ $todayGoldPrice ?? 'No data for today' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="trd-jewel1-chart card-rounded-bottom"
                         id="goldPriceChart"
                         data-kt-chart-title="Gold Price"
                         data-kt-chart-color="warning"
                         data-kt-chart-data="{{ json_encode(array_column($goldPrices, 'goldprice_basecurr')) }}"
                         data-kt-chart-categories="{{ json_encode(array_column($goldPrices, 'log_date')) }}"
                         style="height: {{ $chartHeight }};">
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                element.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><div class="text-muted">No data available</div></div>';
                return;
            }

            var chartData, chartCategories;

            try {
                chartData = JSON.parse(chartDataAttr);
                chartCategories = JSON.parse(chartCategoriesAttr);
            } catch (parseError) {
                console.error('Error parsing JSON data:', parseError);
                element.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><div class="text-muted">Data parsing error</div></div>';
                return;
            }

            console.log('Parsed chart data for ' + chartTitle + ':', chartData);
            console.log('Parsed categories:', chartCategories);

            if (!Array.isArray(chartData) || chartData.length === 0) {
                console.warn('Empty or invalid chart data for:', chartTitle);
                element.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><div class="text-muted">No data to display</div></div>';
                return;
            }

            // Simple test render first
            var options = {
                series: [{
                    name: chartTitle,
                    data: chartData.map(function(val) { return parseFloat(val) || 0; })
                }],
                chart: {
                    type: 'line',
                    height: 300
                },
                xaxis: {
                    categories: chartCategories
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
                element.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><div class="text-muted">Chart rendering error: ' + error.message + '</div></div>';
            });

        } catch (error) {
            console.error('Error initializing chart:', error);
            element.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><div class="text-muted">Chart initialization error: ' + error.message + '</div></div>';
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
