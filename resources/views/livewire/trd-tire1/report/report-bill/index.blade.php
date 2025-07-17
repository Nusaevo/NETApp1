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

        <div id="print">
            <div>
                <br>
                <style>
                    @media print {

                        body,
                        .container,
                        .card,
                        .card-body {
                            width: 210mm !important;
                            max-width: 210mm !important;
                            min-width: 210mm !important;
                            margin: 0 auto !important;
                            padding: 0 !important;
                            box-sizing: border-box;
                        }

                        table {
                            page-break-inside: auto;
                        }

                        tr {
                            page-break-inside: avoid;
                            page-break-after: auto;
                        }
                    }
                </style>
                <link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}">
                <div class="card">
                    <div class="card-body">
                        <div class="container mb-5 mt-3">
                            <div style="max-width:2480px; margin:auto; padding:20px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div style="min-width:120px; text-align:left;">
                                        <span>{{ \Carbon\Carbon::now()->format('d-M-Y') }}</span>
                                    </div>
                                    <div style="flex:1; text-align:center;">
                                        <h2 style="text-decoration:underline; font-weight:bold; margin:0;">DAFTAR NOTA
                                            TAGIHAN</h2>
                                    </div>
                                </div>
                                <div
                                    style="margin-top:10px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <span style="font-weight:bold; text-decoration:underline;">TANGGAL TAGIH</span>
                                        <span>:
                                            {{ $selectedPrintDate ? \Carbon\Carbon::parse($selectedPrintDate)->format('d-M-Y') : '-' }}</span>
                                    </div>
                                    <div id="page-info">
                                        Page <span class="pageNumber">1</span> of <span class="totalPages">1</span>
                                    </div>
                                </div>
                                <table style="width:100%; border-collapse:collapse; font-size:14px;">
                                    <thead>
                                        <tr>
                                            <th rowspan="2"
                                                style="border:1px solid #000; text-align:center; vertical-align:middle; padding:2px 4px; width:200px;">
                                                NAMA CUSTOMER</th>
                                            <th colspan="2" style="border:1px solid #000; text-align:center;">NOTA
                                            </th>
                                            <th rowspan="2"
                                                style="border:1px solid #000; text-align:center; vertical-align:middle; width: 100px;">
                                                JUMLAH<br>Rp</th>
                                            <th rowspan="2"
                                                style="border:1px solid #000; text-align:center; vertical-align:middle; width: 80px;">
                                                TGL LUNAS</th>
                                            <th colspan="4" style="border:1px solid #000; text-align:center;">
                                                PEMBAYARAN</th>
                                            <th rowspan="2"
                                                style="border:1px solid #000; text-align:center; vertical-align:middle; width: 150px;">
                                                KET.</th>
                                        </tr>
                                        <tr>
                                            <th style="border:1px solid #000; text-align:center;">TGL</th>
                                            <th style="border:1px solid #000; text-align:center; width:100px;">NO</th>
                                            <th style="border:1px solid #000; text-align:center;">BANK</th>
                                            <th style="border:1px solid #000; text-align:center;">TGL</th>
                                            <th style="border:1px solid #000; text-align:center;">NO. BG</th>
                                            <th style="border:1px solid #000; text-align:center; width: 100px;">JUMLAH
                                            </th>
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
                                                <td rowspan="{{ count($rows) + 1 }}"
                                                    style="font-weight:bold; border:1px solid #000; vertical-align:top; padding:2px 4px; height:24px;">
                                                    {{ $customer }}</td>
                                                @php $subtotal = 0; @endphp
                                                @foreach ($rows as $i => $row)
                                                    @if ($i > 0)
                                            <tr>
                                        @endif
                                        <td style="border:1px solid #000; text-align:center;">
                                            {{ $row->tanggal_tagih ? \Carbon\Carbon::parse($row->tanggal_tagih)->format('d') : '' }}
                                        </td>
                                        <td style="border:1px solid #000; text-align: center;">{{ $row->no_nota }}</td>
                                        <td style="border:1px solid #000; text-align:right;">
                                            {{ number_format($row->total_tagihan, 0) }}</td>
                                        <td style="border:1px solid #000;"></td>
                                        <td style="border:1px solid #000;"></td>
                                        <td style="border:1px solid #000;"></td>
                                        <td style="border:1px solid #000;"></td>
                                        <td style="border:1px solid #000;"></td>
                                        <td style="border:1px solid #000;"></td>
                                        </tr>
                                        @php $subtotal += $row->total_tagihan; @endphp
                                        @endforeach
                                        {{-- Baris subtotal, pastikan kolom TGL LUNAS kosong --}}
                                        <tr>
                                            <td style="border:1px solid #000;"></td>
                                            <td style="border:1px solid #000; font-weight:bold; text-align:right;"
                                                colspan="1">Total :</td>
                                            <td
                                                style="border:1px solid #000; text-align:right; font-weight:bold; border-bottom:3px #000;">
                                                {{ number_format($subtotal, 0) }}</td>
                                            <td style="border:1px solid #000;"></td> <!-- TGL LUNAS kosong -->
                                            <td style="border:1px solid #000;"></td> <!-- BANK -->
                                            <td style="border:1px solid #000;"></td> <!-- TGL -->
                                            <td style="border:1px solid #000;"></td> <!-- NO. BG -->
                                            <td style="border:1px solid #000;"></td> <!-- JUMLAH -->
                                            <td style="border:1px solid #000;"></td> <!-- KET. -->
                                        </tr>
                                        @php $grandTotal += $subtotal; @endphp
                                        @endforeach
                                        {{-- Baris total tagihan, pastikan kolom TGL LUNAS kosong --}}
                                        <tr>
                                            <td style="border:1px solid #000;" colspan="2"></td>
                                            <td style="border:1px solid #000; font-weight:bold; text-align:right;">Total
                                                Tagihan</td>
                                            <td style="border:1px solid #000; text-align:right; font-weight:bold;">
                                                {{ number_format($grandTotal, 0) }}</td>
                                            <td style="border:1px solid #000;"></td>
                                            <td style="border:1px solid #000;"></td>
                                            <td style="border:1px solid #000;"></td>
                                            <td style="border:1px solid #000;"></td>
                                            <td style="border:1px solid #000;"></td> <!-- KET. -->
                                            <td style="border:1px solid #000;"></td> <!-- KET. -->
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
<script>
    window.addEventListener('beforeprint', function() {
        // Estimasi tinggi halaman A4 pada 96dpi: 1122px
        var totalPages = Math.ceil(document.body.scrollHeight / 1122);
        document.querySelector('.pageNumber').textContent = 1;
        document.querySelector('.totalPages').textContent = totalPages;
    });
    window.addEventListener('afterprint', function() {
        document.querySelector('.pageNumber').textContent = 1;
        document.querySelector('.totalPages').textContent = 1;
    });
</script>
