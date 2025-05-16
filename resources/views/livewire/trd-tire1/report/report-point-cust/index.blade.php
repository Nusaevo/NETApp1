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
                <div class="card">
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
                                <table style="width:100%; border-collapse:collapse;" border="1">
                                    <thead>
                                        <tr>
                                            <th rowspan="2">Custommer</th>
                                            @foreach ($groupColumns as $col)
                                                <th style="text-align:center; padding:4px 8px; writing-mode:vertical-lr; transform:rotate(180deg); font-size:12px; min-width:40px;" rowspan="2">
                                                    {{ $col }}
                                                </th>
                                            @endforeach
                                            {{-- <th rowspan="2" style="text-align:center; padding:4px 8px; min-width:60px;">Total</th> --}}
                                        </tr>
                                        {{-- Baris kedua header kosong karena header customer sudah dipecah --}}
                                    </thead>
                                    <tbody>
                                        @foreach ($results as $row)
                                            @php
                                                $rowTotal = 0;
                                                $customer = $row->customer ?? '';
                                            @endphp
                                            <tr>
                                                <td style="padding:4px 8px;">{{ $customer }}</td>
                                                @foreach ($groupColumns as $col)
                                                    @php
                                                        $val = (int)($row->$col ?? 0);
                                                        $rowTotal += $val;
                                                    @endphp
                                                    <td style="text-align:center; padding:4px 8px;">
                                                        {{ $val ? $val : '' }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
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
