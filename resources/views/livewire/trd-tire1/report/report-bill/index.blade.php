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
                    <div class="row align-items-end justify-content-between">
                        <div class="col-md-4 d-flex justify-content-start">
                            <x-ui-text-field label="Tanggal Tagih" model="selectedPrintDate" type="date" action="Edit"
                               onChanged="onDateChanged" />
                        </div>
                        <div class="col-md-2 d-flex justify-content-end">
                            <div class="row">
                                <x-ui-button clickEvent="search" button-name="View" loading="true" action="Edit"
                                    cssClass="btn-primary w-100 mb-2 me-2" />
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
        {{-- End Filter Frame --}}

        <style>
            @media screen {
                .portrait-container {
                    max-width: 1200px !important;
                    width: 100%;
                    margin: 20px auto;
                    padding: 20px;
                }

                .portrait-report {
                    background: #fff;
                    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08), 0 0px 1.5px rgba(0, 0, 0, 0.03);
                    border-radius: 10px;
                    padding: 20px;
                }

                .portrait-table {
                    width: 100%;
                    font-size: 12px;
                    border-collapse: collapse;
                }

                .portrait-table th,
                .portrait-table td {
                    border: 1px solid #000;
                    padding: 6px 8px;
                    text-align: center;
                    vertical-align: middle;
                }

                .portrait-table th {
                    background-color: #f5f5f5;
                    font-weight: bold;
                }

                .portrait-table td:first-child {
                    text-align: left;
                    font-weight: bold;
                }

                .portrait-table td:nth-child(3) {
                    text-align: left;
                }

                .portrait-table td:nth-child(4) {
                    text-align: right;
                }

                .portrait-table td:nth-child(6) {
                    text-align: right;
                }

                /* Remove borders for grand total row only */
                .portrait-table .grand-total-row td {
                    border: none !important;
                }

                .portrait-table .grand-total-row td:not(:empty) {
                    border-bottom: 2px solid #000 !important;
                }


                /* Specific cell styles */
                .portrait-table .customer-name {
                    font-size: 20px;
                    line-height: 1.2;
                }

            }

            @media print {
                @page {
                    margin-top: 10mm;
                    margin-bottom: 10mm;
                    margin-left: 5mm;
                    margin-right: 5mm;

                    @top-right {
                        content: "Page " counter(page) " of " counter(pages);
                        font-size: 10px;
                        color: #000000;
                    }
                }

                .print-table {
                    width: 100%;
                    font-size: 12px;
                    border-collapse: collapse;
                }

                .print-table th,
                .print-table td {
                    border: 1px solid #000;
                    padding: 6px 8px;
                    text-align: center;
                    vertical-align: middle;
                }

                .print-table th {
                    background-color: #f5f5f5;
                    font-weight: bold;
                }

                .print-table td:first-child {
                    text-align: left;
                    font-weight: bold;
                }

                .print-table td:nth-child(3) {
                    text-align: left;
                }

                .print-table td:nth-child(4) {
                    text-align: right;
                }

                .print-table td:nth-child(6) {
                    text-align: right;
                }

                /* Remove borders for grand total row only */
                .print-table .grand-total-row td {
                    border: none !important;
                }

                .print-table .grand-total-row td:not(:empty) {
                    border-bottom: 2px solid #000 !important;
                }

                /* Specific cell styles */
                .print-table .customer-name {
                    font-size: 16px;
                    line-height: 1.2;
                }

                /* Page counter untuk menghitung halaman */
                body {
                    counter-reset: page;
                }

                /* Pastikan page counter berfungsi */
                .print-table {
                    page-break-inside: auto;
                }

                /* Styling untuk page info di mode print */
                #page-info {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    font-size: 10px;
                    color: #666;
                    background: white;
                    padding: 2px 6px;
                    border: 1px solid #ddd;
                    border-radius: 3px;
                    z-index: 10;
                }

                /* Styling untuk page info yang ditampilkan di layar */
                #page-info {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    font-size: 12px;
                    color: #666;
                    background: white;
                    padding: 4px 8px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    z-index: 10;
                }
            }
        </style>

        <!-- Card hanya tampil di layar, tidak saat print -->
        <div class="portrait-container d-print-none">
            <div class="portrait-report">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="min-width:120px; text-align:left; margin-bottom: -10px;">
                        <span>{{ \Carbon\Carbon::now()->format('d-M-Y') }}</span>
                    </div>
                    <div style="flex:1; text-align:center;">
                        <h2 style="text-decoration:underline; font-weight:bold; margin:0;">DAFTAR NOTA TAGIHAN</h2>
                    </div>
                </div>
                <div
                    style="margin-top:1px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <span style="font-weight:bold;">TANGGAL TAGIH</span>
                        <span>:
                            {{ $selectedPrintDate ? \Carbon\Carbon::parse($selectedPrintDate)->format('d-M-Y') : '-' }}</span>
                    </div>
                </div>
                <table class="portrait-table" style="margin-top:10px;">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width:200px;">NAMA CUSTOMER</th>
                            <th colspan="2">NOTA</th>
                            <th rowspan="2" style="width: 100px;">JUMLAH<br>Rp</th>
                            <th rowspan="2" style="width: 50px;">TGL LUNAS</th>
                            <th colspan="4">PEMBAYARAN</th>
                            <th rowspan="2" style="width: 150px;">KET.</th>
                        </tr>
                        <tr>
                            <th>TGL</th>
                            <th style="width:80px;">NO</th>
                            <th>BANK</th>
                            <th>TGL</th>
                            <th style="width: 100px;">NO. BG</th>
                            <th style="width: 100px;">JUMLAH</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $grandTotal = 0;
                            $grouped = [];
                            foreach ($results as $row) {
                                $key = $row->nama_pelanggan . ', ' . $row->kota_pelanggan;
                                if (!isset($grouped[$key])) {
                                    $grouped[$key] = [];
                                }
                                $grouped[$key][] = $row;
                            }
                        @endphp
                        @foreach ($grouped as $customer => $rows)
                            {{-- Baris kosong sebelum setiap customer --}}
                            <tr style="border: 1px #000 solid; height: 20px;">
                                <td style="border: 1px #000 solid; height: 20px;"></td>
                                <td style="border: 1px #000 solid; height: 20px;"></td>
                                <td style="border: 1px #000 solid; height: 20px;"></td>
                                <td style="border: 1px #000 solid; height: 20px;"></td>
                                <td style="border: 1px #000 solid; height: 20px;"></td>
                                <td style="border: 1px #000 solid; height: 20px;"></td>
                                <td style="border: 1px #000 solid; height: 20px;"></td>
                                <td style="border: 1px #000 solid; height: 20px;"></td>
                                <td style="border: 1px #000 solid; height: 20px;"></td>
                                <td style="border: 1px #000 solid; height: 20px;"></td>
                            </tr>
                            <tr style="border: 1px #000 solid;">
                                <td class="customer-name" rowspan="{{ count($rows) + (count($rows) > 1 ? 1 : 0) }}" style="vertical-align:top; height:24px; border: 1px #000 solid;">
                                    {{ $customer }}</td>
                                @php $subtotal = 0; @endphp
                                @foreach ($rows as $i => $row)
                                    @if ($i > 0)
                            <tr>
                        @endif
                        <td style="text-align: center; font-weight: normal;">
                            {{ $row->tanggal_nota ? \Carbon\Carbon::parse($row->tanggal_nota)->format('d') : '' }}</td>
                        <td style="text-align: left;">{{ $row->no_nota }}</td>
                        <td style="text-align: right;">{{ number_format($row->total_tagihan, 0) }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        </tr>
                        @php $subtotal += $row->total_tagihan; @endphp
                        @endforeach
                        {{-- Baris subtotal hanya tampil jika lebih dari 1 nota --}}
                        @if(count($rows) > 1)
                            <tr class="subtotal-row">
                                <td></td>
                                <td style="font-weight:bold; text-align:right;" colspan="1">Total :</td>
                                <td style="text-align:right; font-weight:bold; text-decoration: underline;">
                                    {{ number_format($subtotal, 0) }}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        @endif
                        @php $grandTotal += $subtotal; @endphp
                        @endforeach
                        <tr class="grand-total-row">
                            <td colspan="2"></td>
                            <td style="font-weight:bold; text-align:right; width: auto; white-space: nowrap;">Total
                                Tagihan</td>
                            <td style="text-align:right; font-weight:bold; text-decoration: underline;">
                                {{ number_format($grandTotal, 0) }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Area print tetap tampil saat print -->
        <div id="print" class="d-none d-print-block p-20">
            <div style="max-width: 1200px; margin: 0 auto; font-family: 'Calibri'; font-size: 14px;">
                <div class="report-box" style="max-width: 1200px; margin: auto; padding: 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="min-width:120px; text-align:left; margin-bottom: -10px;">
                            <span>{{ \Carbon\Carbon::now()->format('d-M-Y') }}</span>
                        </div>
                        <div style="flex:1; text-align:center;">
                            <h2 style="text-decoration:underline; font-weight:bold; margin:0;">DAFTAR NOTA TAGIHAN</h2>
                        </div>
                    </div>
                    <div
                        style="margin-top:1px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <span style="font-weight:bold;">TANGGAL TAGIH</span>
                            <span>:
                                {{ $selectedPrintDate ? \Carbon\Carbon::parse($selectedPrintDate)->format('d-M-Y') : '-' }}</span>
                        </div>
                    </div>
                    <table class="print-table" style="margin-top:10px;">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width:200px;">NAMA CUSTOMER</th>
                                <th colspan="2">NOTA</th>
                                <th rowspan="2" style="width: 100px;">JUMLAH<br>Rp</th>
                                <th rowspan="2" style="width: 50px;">TGL LUNAS</th>
                                <th colspan="4">PEMBAYARAN</th>
                                <th rowspan="2" style="width: 150px;">KET.</th>
                            </tr>
                            <tr>
                                <th>TGL</th>
                                <th style="width:80px;">NO</th>
                                <th>BANK</th>
                                <th>TGL</th>
                                <th style="width: 100px;">NO. BG</th>
                                <th style="width: 100px;">JUMLAH</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $grandTotal = 0;
                                $grouped = [];
                                foreach ($results as $row) {
                                    $key = $row->nama_pelanggan . ', ' . $row->kota_pelanggan;
                                    if (!isset($grouped[$key])) {
                                        $grouped[$key] = [];
                                    }
                                    $grouped[$key][] = $row;
                                }
                            @endphp
                            @foreach ($grouped as $customer => $rows)
                                {{-- Baris kosong sebelum setiap customer --}}
                                <tr style="border: 1px #000 solid; height: 20px;">
                                    <td style="border: 1px #000 solid; height: 20px;"></td>
                                    <td style="border: 1px #000 solid; height: 20px;"></td>
                                    <td style="border: 1px #000 solid; height: 20px;"></td>
                                    <td style="border: 1px #000 solid; height: 20px;"></td>
                                    <td style="border: 1px #000 solid; height: 20px;"></td>
                                    <td style="border: 1px #000 solid; height: 20px;"></td>
                                    <td style="border: 1px #000 solid; height: 20px;"></td>
                                    <td style="border: 1px #000 solid; height: 20px;"></td>
                                    <td style="border: 1px #000 solid; height: 20px;"></td>
                                    <td style="border: 1px #000 solid; height: 20px;"></td>
                                </tr>
                                <tr>
                                    <td class="customer-name" rowspan="{{ count($rows) + (count($rows) > 1 ? 1 : 0) }}" style="vertical-align:top; height:24px;">
                                        {{ $customer }}</td>
                                    @php $subtotal = 0; @endphp
                                    @foreach ($rows as $i => $row)
                                        @if ($i > 0)
                                <tr>
                            @endif
                            <td style="text-align: center; font-weight: normal;">
                                {{ $row->tanggal_nota ? \Carbon\Carbon::parse($row->tanggal_nota)->format('d') : '' }}
                            </td>
                            <td style="text-align: left;">{{ $row->no_nota }}</td>
                            <td style="text-align: right;">{{ number_format($row->total_tagihan, 0) }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            </tr>
                            @php $subtotal += $row->total_tagihan; @endphp
                            @endforeach
                            {{-- Baris subtotal hanya tampil jika lebih dari 1 nota --}}
                            @if(count($rows) > 1)
                                <tr class="subtotal-row">
                                    <td></td>
                                    <td style="font-weight:bold; text-align:right;" colspan="1">Total :</td>
                                    <td style="text-align:right; font-weight:bold;">
                                        {{ number_format($subtotal, 0) }}</td>
                                    <td></td> <!-- TGL LUNAS kosong -->
                                    <td></td> <!-- BANK -->
                                    <td></td> <!-- TGL -->
                                    <td></td> <!-- NO. BG -->
                                    <td></td> <!-- JUMLAH -->
                                    <td></td> <!-- KET. -->
                                </tr>
                            @endif
                            @php $grandTotal += $subtotal; @endphp
                            @endforeach
                            {{-- Baris total tagihan, pastikan kolom TGL LUNAS kosong --}}
                            <tr class="grand-total-row">
                                <td colspan="2"></td>
                                <td style="font-weight:bold; text-align:right; width: auto; white-space: nowrap;">Total
                                    Tagihan</td>
                                <td style="text-align:right; font-weight:bold;">
                                    {{ number_format($grandTotal, 0) }}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td> <!-- KET. -->
                                <td></td> <!-- KET. -->
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-ui-page-card>
</div>
<script>
    function printReport() {
        window.print();
    }
</script>
