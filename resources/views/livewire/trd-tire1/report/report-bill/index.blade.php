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
                            <x-ui-dropdown-select label="Tanggal Tagih" model="selectedPrintDate" :options="$printDateOptions"
                                action="Edit" />
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
                box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 0px 1.5px rgba(0,0,0,0.03);
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


        }

        @media print {
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
        }
    </style>

    <!-- Card hanya tampil di layar, tidak saat print -->
    <div class="portrait-container d-print-none">
        <div class="portrait-report">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div style="min-width:120px; text-align:left;">
                    <span>{{ \Carbon\Carbon::now()->format('d-M-Y') }}</span>
                </div>
                <div style="flex:1; text-align:center;">
                    <h2 style="text-decoration:underline; font-weight:bold; margin:0;">DAFTAR NOTA TAGIHAN</h2>
                </div>
            </div>
            <div style="margin-top:10px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-weight:bold; text-decoration:underline;">TANGGAL TAGIH</span>
                    <span>: {{ $selectedPrintDate ? \Carbon\Carbon::parse($selectedPrintDate)->format('d-M-Y') : '-' }}</span>
                </div>
                <div id="page-info">
                    Page <span class="pageNumber">1</span> of <span class="totalPages">1</span>
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
                        <th style="width:100px;">NO</th>
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
                        <tr>
                            <td rowspan="{{ count($rows) + 1 }}" style="vertical-align:top; height:24px;">
                                {{ $customer }}</td>
                            @php $subtotal = 0; @endphp
                            @foreach ($rows as $i => $row)
                                @if ($i > 0)
                        <tr>
                    @endif
                    <td>{{ $row->tanggal_tagih ? \Carbon\Carbon::parse($row->tanggal_tagih)->format('d') : '' }}</td>
                    <td>{{ $row->no_nota }}</td>
                    <td>{{ number_format($row->total_tagihan, 0) }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    </tr>
                    @php $subtotal += $row->total_tagihan; @endphp
                    @endforeach
                    {{-- Baris subtotal, pastikan kolom TGL LUNAS kosong --}}
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
                    @php $grandTotal += $subtotal; @endphp
                    @endforeach
                    {{-- Baris total tagihan, pastikan kolom TGL LUNAS kosong --}}
                    <tr class="grand-total-row">
                        <td colspan="2"></td>
                        <td style="font-weight:bold; text-align:right;">Total Tagihan</td>
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

    <!-- Area print tetap tampil saat print -->
    <div id="print" class="d-none d-print-block p-20">
        <div style="max-width: 1200px; margin: 0 auto; font-family: 'Calibri'; font-size: 14px;">
            <div class="report-box" style="max-width: 1200px; margin: auto; padding: 20px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="min-width:120px; text-align:left;">
                        <span>{{ \Carbon\Carbon::now()->format('d-M-Y') }}</span>
                    </div>
                    <div style="flex:1; text-align:center;">
                        <h2 style="text-decoration:underline; font-weight:bold; margin:0;">DAFTAR NOTA TAGIHAN</h2>
                    </div>
                </div>
                <div style="margin-top:10px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <span style="font-weight:bold; text-decoration:underline;">TANGGAL TAGIH</span>
                        <span>: {{ $selectedPrintDate ? \Carbon\Carbon::parse($selectedPrintDate)->format('d-M-Y') : '-' }}</span>
                    </div>
                    <div id="page-info">
                        Page <span class="pageNumber">1</span> of <span class="totalPages">1</span>
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
                            <th style="width:100px;">NO</th>
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
                            <tr>
                                <td rowspan="{{ count($rows) + 1 }}" style="vertical-align:top; height:24px;">
                                    {{ $customer }}</td>
                                @php $subtotal = 0; @endphp
                                @foreach ($rows as $i => $row)
                                    @if ($i > 0)
                            <tr>
                        @endif
                        <td>{{ $row->tanggal_tagih ? \Carbon\Carbon::parse($row->tanggal_tagih)->format('d') : '' }}</td>
                        <td>{{ $row->no_nota }}</td>
                        <td>{{ number_format($row->total_tagihan, 0) }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        </tr>
                        @php $subtotal += $row->total_tagihan; @endphp
                        @endforeach
                        {{-- Baris subtotal, pastikan kolom TGL LUNAS kosong --}}
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
                        @php $grandTotal += $subtotal; @endphp
                        @endforeach
                        {{-- Baris total tagihan, pastikan kolom TGL LUNAS kosong --}}
                        <tr class="grand-total-row">
                            <td colspan="2"></td>
                            <td style="font-weight:bold; text-align:right;">Total Tagihan</td>
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
        // Calculate total pages
        const printContent = document.getElementById('print');
        const contentHeight = printContent.scrollHeight;
        const pageHeight = 1122; // A4 height in pixels at 96dpi
        const totalPages = Math.ceil(contentHeight / pageHeight);

        // Update page info
        const pageNumberElement = document.querySelector('.pageNumber');
        const totalPagesElement = document.querySelector('.totalPages');
        if (pageNumberElement && totalPagesElement) {
            pageNumberElement.textContent = '1';
            totalPagesElement.textContent = totalPages;
        }

        // Print the document
        window.print();
    }

    window.addEventListener('beforeprint', function() {
        // Additional setup before print if needed
        document.body.classList.add('printing');
    });

    window.addEventListener('afterprint', function() {
        // Cleanup after print
        document.body.classList.remove('printing');
        const pageNumberElement = document.querySelector('.pageNumber');
        const totalPagesElement = document.querySelector('.totalPages');
        if (pageNumberElement && totalPagesElement) {
            pageNumberElement.textContent = '1';
            totalPagesElement.textContent = '1';
        }
    });
</script>
