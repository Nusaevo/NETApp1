<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <div class="card mb-4">
            <div class="card-body">
                <div class="container mb-2 mt-2">
                    <div class="row align-items-start">
                        <div class="col-md-10">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <x-ui-text-field label="Tanggal Awal:" model="start_date" type="date"
                                        action="Edit" />
                                </div>
                                <div class="col-md-3">
                                    <x-ui-text-field label="Tanggal Akhir:" model="end_date" type="date"
                                        action="Edit" />
                                </div>
                                <div class="col-md-3">
                                    <x-ui-dropdown-select label="Gudang:" model="wh_code" :options="$warehouses"
                                        action="Edit" onChanged="onWarehouseChanged" />
                                </div>
                                <div class="col-md-3">
                                    <x-ui-dropdown-search label="Kode Barang" model="matl_id" optionValue="id"
                                        :query="$materialQuery" optionLabel="code,name"
                                        placeHolder="Ketik untuk cari barang..." :selectedValue="$matl_id" required="false"
                                        action="Edit" enabled="true" type="int" onChanged="onMaterialChanged" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column gap-2">
                                <x-ui-button clickEvent="search" button-name="View" loading="true" action="Edit"
                                    cssClass="btn-primary w-100" />
                                <button type="button" class="btn btn-light text-capitalize border-0 w-100"
                                    onclick="printReport()">
                                    <i class="fas fa-print text-primary"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="print">
            <div>
                <style>
                    @media print {
                        body {
                            background: #fff !important;
                            font-family: 'Calibri', Arial, sans-serif !important;
                            padding-top: 10px;
                        }

                        #print .card {
                            box-shadow: none !important;
                            border: none !important;
                            background: transparent !important;
                        }

                        #print .card-body {
                            padding: 0;
                            margin: 0;
                            background: transparent !important;
                        }

                        #print .container {
                            margin: 0 auto !important;
                            padding: 0;
                            max-width: none !important;
                        }

                        #print table {
                            /* margin-left: auto !important; */
                            /* margin-right: auto !important; */
                            border-collapse: collapse !important;
                            width: 100% !important;
                        }

                        #print th,
                        #print td {
                            padding: 4px 6px !important;
                            font-size: 13px !important;
                            border: 1px solid #000 !important;
                            vertical-align: middle !important;
                            color: #000 !important;
                        }

                        #print th {
                            background: transparent !important;
                            font-weight: bold !important;
                            text-align: left !important;
                        }

                        #print h3,
                        #print h4 {
                            margin: 0 !important;
                            font-weight: bold !important;
                            color: #000 !important;
                        }

                        #print p {
                            margin: 5px 0 !important;
                        }

                        .btn,
                        .card-header,
                        .card-footer,
                        .page-info {
                            display: none !important;
                        }

                        #print {
                            font-family: 'Calibri', Arial, sans-serif !important;
                            font-size: 14px !important;
                            color: #000 !important;
                            background: transparent !important;
                        }

                        #print * {
                            color: #000 !important;
                        }

                        #print div[style*="max-width:2480px"] {
                            padding: 20px 20px 20px 20px !important;
                            max-width: 100% !important;
                            background: transparent !important;
                        }
                    }
                </style>
                <div class="card print-page">
                    <div class="card-body">
                        <div class="container mb-3 mt-4">
                            <div style="max-width:2480px; margin:auto; padding:20px 20px 10px 20px;">
                                <div style="text-align: left; margin-bottom: 2px;">
                                    <h3 style="font-weight:bold; margin:0; text-decoration: underline;">KARTU STOCK
                                        BARANG</h3>
                                </div>

                                <div style="margin-bottom: 15px;">
                                    <div style="text-align: left; margin-bottom: 15px; font-size: 18px;">
                                        <strong>{{ $warehouse_name ?: $wh_code ?: '-' }}</strong>
                                    </div>

                                    <div style="display: inline-block; font-size: 14px; ">
                                        <strong>Kode Barang:</strong> <span
                                            style="text-decoration: underline; font-weight: bold;">{{ $matl_code ?: '-' }}</span>
                                    </div>
                                    <br>
                                    <div style="display: inline-block; font-size: 14px; ">
                                        <strong>Nama Barang:</strong> <span
                                            style="text-decoration: underline; font-weight: bold;">{{ $material_name ?: '-' }}</span>
                                    </div>

                                    {{-- <div style="text-align: left; margin-bottom: 10px; font-size: 12px;">
                                        <strong>Periode:</strong> {{ $start_date ? \Carbon\Carbon::parse($start_date)->format('d-M-Y') : '-' }}
                                        s/d {{ $end_date ? \Carbon\Carbon::parse($end_date)->format('d-M-Y') : '-' }}
                                    </div> --}}
                                </div>

                                @php
                                    function fmtDate($d)
                                    {
                                        return $d ? \Carbon\Carbon::parse($d)->format('d-M-y') : '';
                                    }
                                    function nfmt($n)
                                    {
                                        return number_format($n ?? 0, 0, ',', '.');
                                    }
                                @endphp

                                <table
                                    style="width:100%; border-collapse:collapse; font-family: 'Calibri', Arial, sans-serif; border: 1px solid #000;">
                                    <thead>
                                        <tr>
                                            <th
                                                style="text-align:left; padding:5px 8px; font-weight:bold; font-size:13px; width:90px; border:1px solid #000;">
                                                Tgl.</th>
                                            <th
                                                style="text-align:left; padding:5px 8px; font-weight:bold; font-size:13px; width:110px; border:1px solid #000;">
                                                No. Bukti</th>
                                            <th
                                                style="text-align:left; padding:5px 8px; font-weight:bold; font-size:13px; width:320px; border:1px solid #000;">
                                                KETERANGAN</th>
                                            <th
                                                style="text-align:center; padding:5px 8px; font-weight:bold; font-size:13px; width:70px; border:1px solid #000;">
                                                MASUK</th>
                                            <th
                                                style="text-align:center; padding:5px 8px; font-weight:bold; font-size:13px; width:70px; border:1px solid #000;">
                                                KELUAR</th>
                                            <th
                                                style="text-align:center; padding:5px 8px; font-weight:bold; font-size:13px; width:80px; border:1px solid #000;">
                                                SISA</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $previousDate = null; // Initialize variable to store the date of the previous row
                                        @endphp
                                        @foreach ($results as $row)
                                            @php
                                                $urut = $row->urut ?? null;
                                                $currentDate = $row->tr_date ?? null;
                                                $displayDate = '';

                                                // Apply date suppression logic only for transaction rows (urut == 1)
                                                if ($urut == 1) {
                                                    if ($currentDate !== null && $currentDate != $previousDate) {
                                                        $displayDate = fmtDate($currentDate);
                                                    }
                                                    $previousDate = $currentDate;
                                                } else {
                                                    $displayDate = fmtDate($currentDate);
                                                    $previousDate = null;
                                                }
                                            @endphp
                                            <tr>
                                                <td
                                                    style="text-align:left; padding:4px 6px; font-size:13px; width:90px; border:1px solid #000;">
                                                    {{ $displayDate }}</td>
                                                <td
                                                    style="text-align:left; padding:4px 6px; font-size:13px; width:110px; border:1px solid #000;">
                                                    {{ $row->tr_code ?? '' }}</td>
                                                <td
                                                    style="text-align:left; padding:4px 6px; font-size:13px; border:1px solid #000;">
                                                    {{ $row->tr_desc ?? '' }}</td>
                                                <td
                                                    style="text-align:right; padding:4px 6px; font-size:13px; width:70px; border:1px solid #000;">
                                                    {{ $urut === 0 || $urut === 2 ? '' : (($row->masuk ?? 0) > 0 ? nfmt($row->masuk) : '') }}
                                                </td>
                                                <td
                                                    style="text-align:right; padding:4px 6px; font-size:13px; width:70px; border:1px solid #000;">
                                                    {{ $urut === 0 || $urut === 2 ? '' : (($row->keluar ?? 0) > 0 ? nfmt($row->keluar) : '') }}
                                                </td>
                                                <td
                                                    style="text-align:right; padding:4px 6px; font-size:13px; width:80px; border:1px solid #000;">
                                                    {{ $urut === 0 || $urut === 2 ? nfmt($row->sisa ?? 0) : (($row->sisa ?? 0) != 0 ? nfmt($row->sisa) : '') }}
                                                </td>
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

    <script>
        function printReport() {
            window.print();
        }
    </script>
</div>
