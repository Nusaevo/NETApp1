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
                            <x-ui-dropdown-select
                                label="Code"
                                model="category"
                                :options="$codeSalesreward"
                                action="Edit"
                                onChanged="onSrCodeChanged"
                            />
                        </div>
                        <div class="col-md-3">
                            <x-ui-text-field
                                label="Tanggal Awal:"
                                model="startCode"
                                type="date"
                                action="Edit"
                            />
                        </div>
                        <div class="col-md-3">
                            <x-ui-text-field
                                label="Tanggal Akhir:"
                                model="endCode"
                                type="date"
                                action="Edit"
                            />
                        </div>
                        <div class="col-md-2">
                            <x-ui-button
                                clickEvent="search"
                                button-name="View"
                                loading="true"
                                action="Edit"
                                cssClass="btn-primary w-100 mb-2"
                            />
                            <button
                                type="button"
                                class="btn btn-light text-capitalize border-0 w-100"
                                onclick="printReport()"
                            >
                                <i class="fas fa-print text-primary"></i>
                                Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- End Filter Frame --}}

        <div id="print">
            <br>
            <link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}">
            {{-- Inline CSS for table borders --}}
            <style>
                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                table th,
                table td {
                    border: 1px solid #000;
                    padding: 4px 8px;
                }
            </style>

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
                                s/d
                                {{ $endCode ? \Carbon\Carbon::parse($endCode)->format('d-M-Y') : '-' }}
                            </p>

                            @php
                                // Ambil kolom dinamis dari hasil crosstab
                                $columns = [];
                                if (count($results)) {
                                    $columns = array_keys((array)$results[0]);
                                    // kolom pertama biasanya 'customer'
                                    $groupColumns = array_filter($columns, fn($col) => $col !== 'customer');
                                } else {
                                    $groupColumns = [];
                                }

                                function splitCustomer($customer) {
                                    $parts = explode(' - ', $customer, 2);
                                    return [
                                        'name' => $parts[0] ?? $customer,
                                        'city' => $parts[1] ?? '',
                                    ];
                                }
                            @endphp

                            <table>
                                <thead>
                                    <tr>
                                        <th rowspan="2">Customer</th>
                                        @foreach ($groupColumns as $col)
                                            <th
                                                style="text-align:center;
                                                       writing-mode:vertical-lr;
                                                       transform:rotate(180deg);
                                                       font-size:12px;
                                                       min-width:40px;"
                                                rowspan="2"
                                            >
                                                {{ $col }}
                                            </th>
                                        @endforeach
                                    </tr>
                                    {{-- Baris kedua header kosong --}}
                                </thead>
                                <tbody>
                                    @foreach ($results as $row)
                                        @php
                                            $customer = $row->customer ?? '';
                                        @endphp
                                        <tr>
                                            <td>{{ $customer }}</td>
                                            @foreach ($groupColumns as $col)
                                                @php
                                                    $val = (int)($row->$col ?? 0);
                                                @endphp
                                                <td style="text-align:center;">
                                                    {{ $val ?: '' }}
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
    </x-ui-page-card>
</div>
