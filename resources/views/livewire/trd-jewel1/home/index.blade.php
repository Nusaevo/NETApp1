<x-base-layout>
    <div class="row gy-5 g-xl-8">
        <div class="col-xxl-4">
            @php
            $chartColor = $chartColor ?? 'primary';
            $chartHeight = $chartHeight ?? '175px';
            @endphp

            <!-- Currency Rate Widget -->
            <div class="card card-xxl-stretch-50 mb-5 mb-xl-8">
                <div class="card card-body d-flex flex-column p-0">
                    <div class="flex-grow-1 card-p pb-0">
                        <div class="d-flex flex-stack flex-wrap">
                            <div class="me-2">
                                <a href="#" class="text-dark text-hover-primary fw-bolder fs-3">Daily Currency Rates</a>
                                <div class="text-muted fs-7 fw-bold"> {{ $todayCurrencyRate ?? 'No data for today' }}</div>
                            </div>
                            {{-- <div class="fw-bolder fs-3 text-{{ $chartColor }}">
                            {{ $todayCurrencyRate ?? 'No data for today' }}
                        </div> --}}
                    </div>
                </div>
                <div class="trd-jewel1-chart card-rounded-bottom" id="currencyRateChart" data-kt-chart-title="Currency Rate" data-kt-chart-color="primary" data-kt-chart-data="{{ json_encode(array_column($currencyRates, 'curr_rate')) }}" data-kt-chart-categories="{{ json_encode(array_column($currencyRates, 'log_date')) }}" style="height: {{ $chartHeight }}">
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-4">
        <!-- Gold Price Widget -->
        <div class="card card-xxl-stretch-50 mb-5 mb-xl-8">
            <div class="card card-body d-flex flex-column p-0">
                <div class="flex-grow-1 card-p pb-0">
                    <div class="d-flex flex-stack flex-wrap">
                        <div class="me-2">
                            <a href="#" class="text-dark text-hover-primary fw-bolder fs-3">Daily gold price base currency</a>
                            <div class="text-muted fs-7 fw-bold"> {{ $todayGoldPrice ?? 'No data for today' }}</div>
                        </div>
                        {{-- <div class="fw-bolder fs-3 text-{{ $chartColor }}">
                        {{ $todayGoldPrice ?? 'No data for today' }}
                    </div> --}}
                </div>
            </div>
            <div class="trd-jewel1-chart card-rounded-bottom" id="goldPriceChart" data-kt-chart-title="Gold Price" data-kt-chart-color="warning" data-kt-chart-data="{{ json_encode(array_column($goldPrices, 'goldprice_basecurr')) }}" data-kt-chart-categories="{{ json_encode(array_column($goldPrices, 'log_date')) }}" style="height: {{ $chartHeight }}">
            </div>
        </div>
    </div>
    </div>
    </div>

    <script>
        function initCharts() {
            var charts = document.querySelectorAll('.trd-jewel1-chart');

            [].slice.call(charts).forEach(function(element) {
                var height = parseInt(KTUtil.css(element, 'height'));

                var chartData = JSON.parse(element.getAttribute('data-kt-chart-data'));
                var chartCategories = JSON.parse(element.getAttribute('data-kt-chart-categories'));
                var chartTitle = element.getAttribute('data-kt-chart-title'); // Fetch the chart title
                var color = element.getAttribute('data-kt-chart-color');

                var labelColor = KTUtil.getCssVariableValue('--bs-gray-800');
                var baseColor = KTUtil.getCssVariableValue('--bs-' + color);
                var lightColor = KTUtil.getCssVariableValue('--bs-light-' + color);

                var options = {
                    series: [{
                        name: chartTitle, // Use the dynamic title
                        data: chartData
                    }]
                    , chart: {
                        fontFamily: 'inherit'
                        , type: 'area'
                        , height: height
                        , toolbar: {
                            show: false
                        }
                        , zoom: {
                            enabled: false
                        }
                        , sparkline: {
                            enabled: true
                        }
                    },
                    // Other chart configurations...
                    xaxis: {
                        categories: chartCategories
                        , labels: {
                            show: true
                            , rotate: -45
                            , rotateAlways: true
                            , formatter: function(val) {
                                return new Date(val).toLocaleDateString();
                            }
                            , style: {
                                colors: labelColor
                                , fontSize: '12px'
                            }
                        }
                    , },
                    // Other axis and tooltip configurations...
                };

                var chart = new ApexCharts(element, options);
                chart.render();
            });
        }
        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
        });

    </script>

</x-base-layout>

