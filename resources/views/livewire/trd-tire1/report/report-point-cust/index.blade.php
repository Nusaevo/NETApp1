<div>
    @php
        use App\Services\TrdJewel1\Master\MasterService;
        $masterService = new MasterService();
    @endphp
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        {{-- Filter Frame --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="container mb-2 mt-2">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <x-ui-dropdown-select label="Code" model="category" :options="$codeSalesreward" action="Edit"
                                onChanged="onSrCodeChanged" />
                        </div>
                        <div class="col-md-3">
                            <x-ui-text-field label="Tanggal Awal:" model="startCode" type="date" action="Edit" />
                        </div>
                        <div class="col-md-3">
                            <x-ui-text-field label="Tanggal Akhir:" model="endCode" type="date" action="Edit" />
                        </div>
                        <div class="col-md-2">
                            <x-ui-button clickEvent="search" button-name="View" loading="true" action="Edit"
                                cssClass="btn-primary w-100 mb-2" />
                            <button type="button" class="btn btn-light text-capitalize border-0 w-100"
                                onclick="printReport()">
                                <i class="fas fa-print text-primary"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- End Filter Frame --}}

        <div id="print">
            <div>
                <br>
                <link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}">
                <style>
                    @media print {
                        body {
                            background: #fff !important;
                        }
                        #print .card {
                            box-shadow: none !important;
                            border: none !important;
                        }
                        #print .container {
                            margin: 0 auto !important;
                            padding: 0 !important;
                        }
                        #print table {
                            margin-left: auto !important;
                            margin-right: auto !important;
                        }
                        #print th, #print td {
                            padding: 2px 4px !important;
                            font-size: 11px !important;
                        }
                        #print th {
                            background: #f9f9f9 !important;
                        }
                        /* Padding top hanya di halaman pertama */
                        #print .print-page {
                            page-break-after: always;
                            padding-top: 30px;
                        }
                        #print .print-page:not(:first-child) {
                            padding-top: 0 !important;
                        }
                        /* Hilangkan margin/padding default print */
                        @page {
                            margin: 10mm 10mm 10mm 10mm;
                        }
                    }
                </style>
                <div class="card print-page">
                    <div class="card-body">
                        <div class="container mb-5 mt-3">
                            <div style="max-width:2480px; margin:auto; padding:20px;">
                                <h4>TOKO BAN CAHAYA TERANG - SURABAYA</h4>
                                <h3 style="text-decoration:underline; text-align:left;">
                                    DATA PENJUALAN GT RADIAL per Customer
                                </h3>
                                <p style="text-align:left; margin-bottom:0;">
                                    <strong>Nama Program : {{ $category }}</strong>
                                </p>
                                <p style="text-align:left; margin-bottom:20px;">
                                    Periode:
                                    {{ $startCode ? \Carbon\Carbon::parse($startCode)->format('d-M-Y') : '-' }}
                                    s/d {{ $endCode ? \Carbon\Carbon::parse($endCode)->format('d-M-Y') : '-' }}
                                </p>
                                @php
                                    // Ambil kolom dinamis dari hasil crosstab
                                    $columns = [];
                                    if (count($results)) {
                                        $columns = array_keys((array)$results[0]);
                                        // kolom pertama biasanya 'customer', sisanya adalah grup
                                        $groupColumns = array_filter($columns, fn($col) => $col !== 'customer');
                                    } else {
                                        $groupColumns = [];
                                    }
                                    // Hitung total per kolom
                                    // $totals = [];
                                    // foreach ($groupColumns as $col) {
                                    //     $totals[$col] = array_sum(array_map(fn($row) => (int)($row->$col ?? 0), $results));
                                    // }
                                    // $grandTotal = array_sum($totals);

                                    // Pisahkan nama dan kota customer
                                    function splitCustomer($customer) {
                                        // Jika customer kosong, return kosong
                                        // if (!$customer) return ['name' => '', 'city' => ''];
                                        // // Jika customer diawali dengan '_CUSTOMER', tampilkan di kolom nama saja
                                        // if (strpos($customer, '_CUSTOMER') === 0) {
                                        //     return ['name' => $customer, 'city' => ''];
                                        // }
                                        // // Debug: jika customer tidak ada ' - ', tampilkan semua di name
                                        // if (strpos($customer, ' - ') === false) {
                                        //     return ['name' => $customer, 'city' => ''];
                                        // }
                                        $parts = explode(' - ', $customer, 2);
                                        return [
                                            'name' => $parts[0] ?? $customer,
                                            'city' => $parts[1] ?? '',
                                        ];
                                    }
                                @endphp
                                {{-- Debug: tampilkan isi customer --}}
                                {{-- <pre>
                                    @foreach ($results as $row)
                                        {{ $row->customer ?? '' }}
                                    @endforeach
                                </pre> --}}
                                <table style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th rowspan="2" style="border: 1px solid #000; text-align: center;">Custommer</th>
                                            @foreach ($groupColumns as $col)
                                                <th style="text-align:center; padding:4px 8px; writing-mode:vertical-lr; transform:rotate(180deg); font-size:12px; min-width:40px; border: 1px solid #000" rowspan="2">
                                                    {{ $col }}
                                                </th>
                                            @endforeach
                                            <th rowspan="2" style="text-align:center; padding:4px 8px; writing-mode:vertical-lr; transform:rotate(180deg); font-size:12px; min-width:40px; border: 1px solid #000">Total</th>
                                        </tr>
                                        {{-- Baris kedua header kosong karena header customer sudah dipecah --}}
                                    </thead>
                                    <tbody>
                                        @php
                                            // Hitung total per kolom untuk footer (hanya point)
                                            $colTotals = [];
                                            $grandTotal = 0;
                                            foreach ($groupColumns as $col) {
                                                $colTotals[$col] = array_sum(array_map(function($row) use ($col) {
                                                    $val = $row->$col ?? '';
                                                    $parts = explode('|', $val);
                                                    return isset($parts[1]) ? (int)$parts[1] : 0;
                                                }, $results));
                                                $grandTotal += $colTotals[$col];
                                            }
                                        @endphp
                                        @foreach ($results as $row)
                                            @php
                                                $rowTotalQty = 0;
                                                $rowTotalPoint = 0;
                                                $customer = $row->customer ?? '';
                                            @endphp
                                            <tr>
                                                <td style="padding:4px 8px; border: 1px solid #000">{{ $customer }}</td>
                                                @foreach ($groupColumns as $col)
                                                    @php
                                                        $val = $row->$col ?? '';
                                                        $parts = explode('|', $val);
                                                        $qty = isset($parts[0]) ? (int)$parts[0] : 0;
                                                        $point = isset($parts[1]) ? (int)$parts[1] : 0;
                                                        $rowTotalQty += $qty;
                                                        $rowTotalPoint += $point;
                                                    @endphp
                                                    <td style="text-align:center; padding:4px 8px; border: 1px solid #000">
                                                        {{ $qty ? $qty : '' }}<br>
                                                        <span style="font-size:11px;color:#000;">{{ $point ? $point : '' }}</span>
                                                    </td>
                                                @endforeach
                                                <td style="text-align:center; padding:4px 8px; border: 1px solid #000; font-weight:bold;">
                                                    {{ $rowTotalQty ? $rowTotalQty : '' }}<br>
                                                    <span style="font-size:11px;color:#000;">{{ $rowTotalPoint ? $rowTotalPoint : '' }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @php
                                            // Hitung total qty per kolom dan grand total qty
                                            $colTotalsQty = [];
                                            $grandTotalQty = 0;
                                            foreach ($groupColumns as $col) {
                                                $colTotalsQty[$col] = array_sum(array_map(function($row) use ($col) {
                                                    $val = $row->$col ?? '';
                                                    $parts = explode('|', $val);
                                                    return isset($parts[0]) ? (int)$parts[0] : 0;
                                                }, $results));
                                                $grandTotalQty += $colTotalsQty[$col];
                                            }
                                        @endphp
                                        <tr>
                                            <td style="padding:4px 8px; border: 1px solid #000; font-weight:bold; background:#f2f2f2;">Total</td>
                                            @foreach ($groupColumns as $col)
                                                <td style="text-align:center; padding:4px 8px; border: 1px solid #000; font-weight:bold; background:#f2f2f2;">
                                                    {{ $colTotalsQty[$col] ? $colTotalsQty[$col] : '' }}<br>
                                                    <span style="font-size:11px;color:#000;">{{ $colTotals[$col] ? $colTotals[$col] : '' }}</span>
                                                </td>
                                            @endforeach
                                            <td style="text-align:center; padding:4px 8px; border: 1px solid #000; font-weight:bold; background:#f2f2f2;">
                                                {{ $grandTotalQty ? $grandTotalQty : '' }}<br>
                                                <span style="font-size:11px;color:#000;">{{ $grandTotal ? $grandTotal : '' }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-ui-page-card>
</div>
