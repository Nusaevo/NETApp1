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
                            <x-ui-dropdown-select label="Code" model="category" :options="$codeSalesreward" action="Edit" onChanged="onSrCodeChanged" />
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
                            <button type="button" class="btn btn-success text-capitalize border-0 w-100 mt-2"
                                wire:click="downloadExcel">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- End Filter Frame --}}

        <div id="print">
            <div>
                <style>
                    @media print {
                        body {
                            background: #fff !important;
                            font-family: 'Calibri', Arial, sans-serif !important;
                        }
                        #print .card {
                            box-shadow: none !important;
                            border: none !important;
                            background: transparent !important;
                        }
                        #print .card-body {
                            padding: 0 !important;
                        }
                        #print .container {
                            margin: 0 auto !important;
                            padding: 0 !important;
                            max-width: none !important;
                        }
                        /* Hilangkan margin bottom bawaan bootstrap saat print */
                        .mb-5 { margin-bottom: 0 !important; }
                        #print table {
                            margin-left: auto !important;
                            margin-right: auto !important;
                            border-collapse: collapse !important;
                            width: 100% !important;
                        }
                        #print th, #print td {
                            padding: 1px 2px !important;
                            font-size: 16px !important;
                            border: 1px solid #000 !important;
                            vertical-align: middle !important;
                            line-height: 1.1 !important;
                        }
                        #print th {
                            background: #f9f9f9 !important;
                            font-weight: bold !important;
                            text-align: center !important;
                            font-size: 16px !important;
                        }
                        #print h3, #print h4 {
                            margin: 4px 0 !important;
                            font-weight: bold !important;
                            font-size: 15px !important;
                        }
                        #print h3 {
                            font-size: 16px !important;
                        }
                        #print h4 {
                            font-size: 15px !important;
                        }
                        #print p {
                            margin: 2px 0 !important;
                            font-size: 12px !important;
                        }
                        /* Ukuran kertas F5: 165mm x 210mm (portrait) */
                        @page {
                            size: 165mm 210mm landscape;
                            margin: 1cm 0.3cm 1cm 0.3cm;
                        }
                        /* Sembunyikan elemen yang tidak perlu di print */
                        .btn, .card-header, .card-footer, .page-info {
                            display: none !important;
                        }
                        /* Pastikan konten tidak terpotong */
                        #print {
                            font-family: 'Calibri', Arial, sans-serif !important;
                            font-size: 12px !important;
                        }
                        /* Override font-size inline pada tabel saat print */
                        #print table th,
                        #print table td {
                            font-size: 16px !important;
                            padding: 1px 2px !important;
                        }
                        /* Header tabel */
                        #print table th {
                            font-size: 14px !important;
                        }
                        /* Font untuk header grup yang vertical */
                        #print table th[style*="writing-mode"] {
                            font-size: 14px !important;
                            min-width: 20px !important;
                            padding: 1px 2px !important;
                        }
                        /* Kompaktkan spacing untuk baris data */
                        #print table td br {
                            line-height: 1.0 !important;
                        }
                        /* Kurangi max-width container untuk F5 */
                        #print [style*="max-width"] {
                            max-width: 100% !important;
                        }
                    }
                </style>
                <div class="card print-page">
                    <div class="card-body">
                        <div class="container mb-5 mt-1">
                            <div style="max-width:100%; margin:auto;">
                                <h4>TOKO BAN CAHAYA TERANG - SURABAYA</h4>
                                <h3 style="text-decoration:underline; text-align:left;">
                                    DATA PENJUALAN per Customer
                                </h3>
                                <p style="text-align:left; margin-bottom:20px;">
                                    PROGRAM {{ $categoryStr ?? $category ?? '' }} Periode:
                                    {{ $startCode ? \Carbon\Carbon::parse($startCode)->format('d-M-Y') : '-' }}
                                    s/d {{ $endCode ? \Carbon\Carbon::parse($endCode)->format('d-M-Y') : '-' }}
                                </p>
                                @php
                                    // Cek apakah brand IRC berdasarkan category
                                    // Pastikan category adalah string
                                    $categoryStr = is_array($category) ? ($category['value'] ?? '') : ($category ?? '');
                                    $isIrcBrand = stripos($categoryStr, 'IRC') !== false;

                                    if (!$isIrcBrand) {
                                        // Ambil kolom dinamis dari hasil crosstab (untuk non-IRC)
                                        $columns = [];
                                        if (count($results)) {
                                            $columns = array_keys((array)$results[0]);
                                            // kolom pertama biasanya 'customer', sisanya adalah grup
                                            $allGroupColumns = array_filter($columns, fn($col) => $col !== 'customer');

                                            // Filter kolom yang memiliki data (qty, point, atau sisa tidak kosong)
                                            $groupColumns = array_filter($allGroupColumns, function($col) use ($results) {
                                                foreach ($results as $row) {
                                                    $val = $row->$col ?? '';
                                                    if (empty($val)) continue;

                                                    // Pastikan $val adalah string sebelum explode
                                                    if (!is_string($val)) {
                                                        continue;
                                                    }

                                                    $parts = explode('|', $val);
                                                    $qty = isset($parts[0]) ? (int)$parts[0] : 0;
                                                    $point = isset($parts[1]) ? (int)$parts[1] : 0;
                                                    $sisa = isset($parts[2]) ? (int)$parts[2] : 0;

                                                    // Jika ada setidaknya satu nilai yang tidak kosong, tampilkan kolom ini
                                                    if ($qty > 0 || $point > 0 || $sisa > 0) {
                                                        return true;
                                                    }
                                                }
                                                return false;
                                            });

                                            // Reset array keys untuk memastikan indexing benar
                                            $groupColumns = array_values($groupColumns);
                                        } else {
                                            $groupColumns = [];
                                        }
                                    }
                                    // Hitung total per kolom
                                    // $totals = [];
                                    // foreach ($groupColumns as $col) {
                                    //     $totals[$col] = array_sum(array_map(fn($row) => (int)($row->$col ?? 0), $results));
                                    // }
                                    // $grandTotal = array_sum($totals);

                                    // Pisahkan nama dan kota customer
                                    function splitCustomer($customer) {
                                        if (!is_string($customer) || trim($customer) === '') {
                                            return ['name' => '', 'address' => '', 'city' => ''];
                                        }

                                        $parts = array_map('trim', explode(' - ', $customer));
                                        $name = $parts[0] ?? '';
                                        $address = $parts[1] ?? '';
                                        $city = '';

                                        if (count($parts) > 2) {
                                            $city = implode(' - ', array_slice($parts, 2));
                                        }

                                        return [
                                            'name' => $name,
                                            'address' => $address,
                                            'city' => $city,
                                        ];
                                    }
                                @endphp
                                {{-- Debug: tampilkan isi customer --}}
                                {{-- <pre>
                                    @foreach ($results as $row)
                                        {{ $row->customer ?? '' }}
                                    @endforeach
                                </pre> --}}
                                <table style="width:100%; border-collapse:collapse;">
                                    <thead>
                                        @if($isIrcBrand)
                                        {{-- Format IRC: Fixed columns dengan header mendatar --}}
                                        @php
                                            // Ambil groups dari row pertama
                                            $ircGroups = !empty($results) && isset($results[0]->groups) ? $results[0]->groups : [];

                                            // Cek apakah ada sisa yang tidak kosong untuk setiap grup (per grup, bukan global)
                                            $hasSisaPerGroup = [];
                                            foreach($ircGroups as $group) {
                                                $grpName = $group->grp;
                                                $hasSisaPerGroup[$grpName] = false;
                                                foreach($results as $row) {
                                                    $rowArray = (array)$row;
                                                    $sisaProp = "{$grpName}_sisa";
                                                    $sisa = $rowArray[$sisaProp] ?? $row->$sisaProp ?? 0;
                                                    if ($sisa > 0) {
                                                        $hasSisaPerGroup[$grpName] = true;
                                                        break; // Break out of inner loop, continue to next group
                                                    }
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <th style="border: 1px solid #000; text-align: center; padding:1px 2px; background:#f9f9f9; font-weight:bold; font-size:14px;">Customer</th>
                                            <th style="border: 1px solid #000; text-align: center; padding:1px 2px; background:#f9f9f9; font-weight:bold; font-size:14px;">Alamat</th>
                                            <th style="border: 1px solid #000; text-align: center; padding:1px 2px; background:#f9f9f9; font-weight:bold; font-size:14px;">Kota</th>
                                            {{-- Semua kolom Qty dulu --}}
                                            @foreach($ircGroups as $group)
                                                <th style="border: 1px solid #000; text-align: center; padding:1px 2px; background:#f9f9f9; font-weight:bold; font-size:14px;">{{ $group->grp }}</th>
                                            @endforeach
                                            {{-- Semua kolom Point --}}
                                            @foreach($ircGroups as $group)
                                                <th style="border: 1px solid #000; text-align: center; padding:1px 2px; background:#f9f9f9; font-weight:bold; font-size:14px;">Point {{ $group->grp }}</th>
                                            @endforeach
                                            {{-- Semua kolom Sisa (jika ada) --}}
                                            @foreach($ircGroups as $group)
                                                @if($hasSisaPerGroup[$group->grp] ?? false)
                                                <th style="border: 1px solid #000; text-align: center; padding:1px 2px; background:#f9f9f9; font-weight:bold; font-size:14px;">Sisa {{ $group->grp }}</th>
                                                @endif
                                            @endforeach
                                            <th style="border: 1px solid #000; text-align: center; padding:1px 2px; background:#f9f9f9; font-weight:bold; font-size:14px;">TOTAL POINT</th>
                                        </tr>
                                        @else
                                        {{-- Format Non-IRC: Crosstab dengan header vertikal --}}
                                        <tr>
                                            <th rowspan="2" style="border: 1px solid #000; text-align: center; padding:1px 2px; background:#f9f9f9; font-weight:bold; font-size:14px;">Customer</th>
                                            <th rowspan="2" style="border: 1px solid #000; text-align: center; padding:1px 2px; background:#f9f9f9; font-weight:bold; font-size:14px;">Kota</th>
                                            @foreach ($groupColumns as $col)
                                                <th style="text-align:center; padding:1px 2px; writing-mode:vertical-lr; transform:rotate(180deg); font-size:14px; min-width:20px; border: 1px solid #000; background:#f9f9f9; font-weight:bold;" rowspan="2">
                                                    {{ $col }}
                                                </th>
                                            @endforeach
                                            <th rowspan="2" style="text-align:center; padding:1px 2px; writing-mode:vertical-lr; transform:rotate(180deg); font-size:14px; min-width:20px; border: 1px solid #000; background:#f9f9f9; font-weight:bold;">Total</th>
                                        </tr>
                                        @endif
                                    </thead>
                                    <tbody>
                                        @if($isIrcBrand)
                                        {{-- Format IRC: Fixed columns --}}
                                        @php
                                            // Ambil groups dari row pertama
                                            $ircGroups = !empty($results) && isset($results[0]->groups) ? $results[0]->groups : [];

                                            // Cek apakah ada sisa yang tidak kosong untuk setiap grup (per grup, bukan global)
                                            $hasSisaPerGroup = [];
                                            foreach($ircGroups as $group) {
                                                $grpName = $group->grp;
                                                $hasSisaPerGroup[$grpName] = false;
                                                foreach($results as $row) {
                                                    $rowArray = (array)$row;
                                                    $sisaProp = "{$grpName}_sisa";
                                                    $sisa = $rowArray[$sisaProp] ?? $row->$sisaProp ?? 0;
                                                    if ($sisa > 0) {
                                                        $hasSisaPerGroup[$grpName] = true;
                                                        break; // Break out of inner loop, continue to next group
                                                    }
                                                }
                                            }

                                            // Inisialisasi total untuk setiap grup
                                            $ircTotals = [];
                                            foreach($ircGroups as $group) {
                                                $ircTotals[$group->grp] = ['qty' => 0, 'point' => 0, 'sisa' => 0];
                                            }
                                            $grandTotalPoint = 0;
                                        @endphp
                                        @foreach ($results as $row)
                                            @php
                                                $customer = $row->customer ?? '';
                                                $customerParts = splitCustomer($customer);
                                                $totalPoint = $row->total_point ?? 0;
                                                $grandTotalPoint += $totalPoint;
                                            @endphp
                                            <tr>
                                                <td style="padding:1px 2px; border: 1px solid #000; font-size: 15px;">{{ $customerParts['name'] }}</td>
                                                <td style="padding:1px 2px; border: 1px solid #000; font-size: 15px;">{{ $customerParts['address'] }}</td>
                                                <td style="padding:1px 2px; border: 1px solid #000; font-size: 15px;">{{ $customerParts['city'] }}</td>
                                                @php
                                                    // Hitung total dulu
                                                    foreach($ircGroups as $group) {
                                                        $grpName = $group->grp;
                                                        $qtyProp = "{$grpName}_qty";
                                                        $pointProp = "{$grpName}_point";
                                                        $sisaProp = "{$grpName}_sisa";
                                                        $rowArray = (array)$row;
                                                        $qty = $rowArray[$qtyProp] ?? $row->$qtyProp ?? 0;
                                                        $point = $rowArray[$pointProp] ?? $row->$pointProp ?? 0;
                                                        $sisa = $rowArray[$sisaProp] ?? $row->$sisaProp ?? 0;
                                                        $ircTotals[$grpName]['qty'] += $qty;
                                                        $ircTotals[$grpName]['point'] += $point;
                                                        $ircTotals[$grpName]['sisa'] += $sisa;
                                                    }
                                                @endphp
                                                {{-- Semua kolom Qty dulu --}}
                                                @foreach($ircGroups as $group)
                                                    @php
                                                        $grpName = $group->grp;
                                                        $qtyProp = "{$grpName}_qty";
                                                        $rowArray = (array)$row;
                                                        $qty = $rowArray[$qtyProp] ?? $row->$qtyProp ?? 0;
                                                    @endphp
                                                    <td style="text-align:center; padding:1px 2px; border: 1px solid #000; font-size: 15px;">
                                                        @if($qty){{ $qty }}@endif
                                                    </td>
                                                @endforeach
                                                {{-- Semua kolom Point --}}
                                                @foreach($ircGroups as $group)
                                                    @php
                                                        $grpName = $group->grp;
                                                        $pointProp = "{$grpName}_point";
                                                        $rowArray = (array)$row;
                                                        $point = $rowArray[$pointProp] ?? $row->$pointProp ?? 0;
                                                    @endphp
                                                    <td style="text-align:center; padding:1px 2px; border: 1px solid #000; font-size: 15px;">
                                                        @if($point){{ $point }}@endif
                                                    </td>
                                                @endforeach
                                                {{-- Semua kolom Sisa (jika ada) --}}
                                                @foreach($ircGroups as $group)
                                                    @if($hasSisaPerGroup[$group->grp] ?? false)
                                                    @php
                                                        $grpName = $group->grp;
                                                        $sisaProp = "{$grpName}_sisa";
                                                        $rowArray = (array)$row;
                                                        $sisa = $rowArray[$sisaProp] ?? $row->$sisaProp ?? 0;
                                                    @endphp
                                                    <td style="text-align:center; padding:1px 2px; border: 1px solid #000; font-size: 15px;">
                                                        @if($sisa){{ $sisa }}@endif
                                                    </td>
                                                    @endif
                                                @endforeach
                                                <td style="text-align:center; padding:1px 2px; border: 1px solid #000; font-weight:bold; font-size: 15px;">
                                                    @if($totalPoint){{ $totalPoint }}@endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="3" style="padding:1px 2px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; font-size: 15px;">Total</td>
                                            {{-- Semua kolom Qty dulu --}}
                                            @foreach($ircGroups as $group)
                                                <td style="text-align:center; padding:1px 2px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; font-size: 15px;">
                                                    @if($ircTotals[$group->grp]['qty']){{ $ircTotals[$group->grp]['qty'] }}@endif
                                                </td>
                                            @endforeach
                                            {{-- Semua kolom Point --}}
                                            @foreach($ircGroups as $group)
                                                <td style="text-align:center; padding:1px 2px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; font-size: 15px;">
                                                    @if($ircTotals[$group->grp]['point']){{ $ircTotals[$group->grp]['point'] }}@endif
                                                </td>
                                            @endforeach
                                            {{-- Semua kolom Sisa (jika ada) --}}
                                            @foreach($ircGroups as $group)
                                                @if($hasSisaPerGroup[$group->grp] ?? false)
                                                <td style="text-align:center; padding:1px 2px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; font-size: 15px;">
                                                    @if($ircTotals[$group->grp]['sisa']){{ $ircTotals[$group->grp]['sisa'] }}@endif
                                                </td>
                                                @endif
                                            @endforeach
                                            <td style="text-align:center; padding:1px 2px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; font-size: 15px;">
                                                @if($grandTotalPoint){{ $grandTotalPoint }}@endif
                                            </td>
                                        </tr>
                                        @else
                                        {{-- Format Non-IRC: Crosstab --}}
                                        @php
                                            // Hitung total per kolom untuk footer (point dan sisa)
                                            $colTotals = [];
                                            $colTotalsSisa = [];
                                            $grandTotal = 0;
                                            $grandTotalSisa = 0;
                                            foreach ($groupColumns as $col) {
                                                $colTotals[$col] = array_sum(array_map(function($row) use ($col) {
                                                    $val = $row->$col ?? '';
                                                    if (!is_string($val)) {
                                                        return 0;
                                                    }
                                                    $parts = explode('|', $val);
                                                    return isset($parts[1]) ? (int)$parts[1] : 0;
                                                }, $results));
                                                $colTotalsSisa[$col] = array_sum(array_map(function($row) use ($col) {
                                                    $val = $row->$col ?? '';
                                                    if (!is_string($val)) {
                                                        return 0;
                                                    }
                                                    $parts = explode('|', $val);
                                                    return isset($parts[2]) ? (int)$parts[2] : 0;
                                                }, $results));
                                                $grandTotal += $colTotals[$col];
                                                $grandTotalSisa += $colTotalsSisa[$col];
                                            }
                                        @endphp
                                        @foreach ($results as $row)
                                            @php
                                                $rowTotalQty = 0;
                                                $rowTotalPoint = 0;
                                                $rowTotalSisa = 0;
                                                $customer = $row->customer ?? '';
                                            @endphp
                                            <tr>
                                                @php
                                                    $customerParts = splitCustomer($customer);
                                                @endphp
                                                <td style="padding:1px 2px; border: 1px solid #000; font-size: 15px;">{{ $customerParts['name'] }}</td>
                                                <td style="padding:1px 2px; border: 1px solid #000; font-size: 15px;">{{ $customerParts['city'] }}</td>
                                                @foreach ($groupColumns as $col)
                                                    @php
                                                        $val = $row->$col ?? '';
                                                        if (!is_string($val)) {
                                                            $val = '';
                                                        }
                                                        $parts = explode('|', $val);
                                                        $qty = isset($parts[0]) ? (int)$parts[0] : 0;
                                                        $point = isset($parts[1]) ? (int)$parts[1] : 0;
                                                        $sisa = isset($parts[2]) ? (int)$parts[2] : 0;
                                                        $rowTotalQty += $qty;
                                                        $rowTotalPoint += $point;
                                                        $rowTotalSisa += $sisa;
                                                    @endphp
                                                    <td style="text-align:center; padding:1px 2px; border: 1px solid #000; font-size: 15px;">
                                                        @if($qty)
                                                            {{ $qty }}
                                                            @if($point || $sisa)<br>@endif
                                                        @endif
                                                        @if($point)
                                                            <span style="color:#000;">{{ $point }}</span>
                                                            @if($sisa)<br>@endif
                                                        @endif
                                                        @if($sisa)
                                                            <span style="color:#000;">{{ $sisa }}</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td style="text-align:center; padding:1px 2px; border: 1px solid #000; font-weight:bold; font-size: 15px;">
                                                    @if($rowTotalQty)
                                                        {{ $rowTotalQty }}
                                                        @if($rowTotalPoint || $rowTotalSisa)<br>@endif
                                                    @endif
                                                    @if($rowTotalPoint)
                                                        <span style="color:#000;">{{ $rowTotalPoint }}</span>
                                                        @if($rowTotalSisa)<br>@endif
                                                    @endif
                                                    @if($rowTotalSisa)
                                                        <span style="color:#000;">{{ $rowTotalSisa }}</span>
                                                    @endif
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
                                                    if (!is_string($val)) {
                                                        return 0;
                                                    }
                                                    $parts = explode('|', $val);
                                                    return isset($parts[0]) ? (int)$parts[0] : 0;
                                                }, $results));
                                                $grandTotalQty += $colTotalsQty[$col];
                                            }
                                        @endphp
                                        <tr>
                                            <td colspan="2" style="padding:1px 2px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; font-size: 15px;">Total</td>
                                            @foreach ($groupColumns as $col)
                                                <td style="text-align:center; padding:1px 2px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; font-size: 15px;">
                                                    @if($colTotalsQty[$col])
                                                        {{ $colTotalsQty[$col] }}
                                                        @if($colTotals[$col] || $colTotalsSisa[$col])<br>@endif
                                                    @endif
                                                    @if($colTotals[$col])
                                                        <span style="color:#000;">{{ $colTotals[$col] }}</span>
                                                        @if($colTotalsSisa[$col])<br>@endif
                                                    @endif
                                                    @if($colTotalsSisa[$col])
                                                        <span style="color:#000;">{{ $colTotalsSisa[$col] }}</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td style="text-align:center; padding:1px 2px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; font-size: 15px;">
                                                @if($grandTotalQty)
                                                    {{ $grandTotalQty }}
                                                    @if($grandTotal || $grandTotalSisa)<br>@endif
                                                @endif
                                                @if($grandTotal)
                                                    <span style="color:#000;">{{ $grandTotal }}</span>
                                                    @if($grandTotalSisa)<br>@endif
                                                @endif
                                                @if($grandTotalSisa)
                                                    <span style="color:#000;">{{ $grandTotalSisa }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
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
