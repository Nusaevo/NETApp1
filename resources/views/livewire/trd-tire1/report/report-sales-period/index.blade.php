<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        {{-- Filter Frame --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="container mb-2 mt-2">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <x-ui-dropdown-select label="Masa" model="selectedMasa" :options="$masaOptions" action="Edit" />
                        </div>
                        <div class="col-md-3">
                            <x-ui-text-field label="Tanggal Awal:" model="dateFrom" type="date" action="Edit" />
                        </div>
                        <div class="col-md-3">
                            <x-ui-text-field label="Tanggal Akhir:" model="dateTo" type="date" action="Edit" />
                        </div>
                        <div class="col-md-2">
                            <x-ui-button clickEvent="search" button-name="View" loading="true" action="Edit"
                                cssClass="btn-primary w-100 mb-2" />
                            <button type="button" class="btn btn-light text-capitalize border-0 w-100 mb-2"
                                onclick="printReport()">
                                <i class="fas fa-print text-primary"></i> Print
                            </button>
                            <x-ui-button clickEvent="downloadExcel" button-name="Download Excel" loading="true" action="Edit"
                                cssClass="btn-success w-100" />
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

                        .mb-5 {
                            margin-bottom: 0 !important;
                        }

                        #print table {
                            margin-left: auto !important;
                            margin-right: auto !important;
                            border-collapse: collapse !important;
                            width: 100% !important;
                        }

                        #print th,
                        #print td {
                            padding: 4px 6px !important;
                            font-size: 17px !important;
                            border: none !important;
                            vertical-align: middle !important;
                        }

                        #print th {
                            background: transparent !important;
                            font-weight: bold !important;
                            text-align: center !important;
                            border-bottom: 1px solid #000 !important;
                        }

                        #print h3,
                        #print h4 {
                            margin: 10px 0 !important;
                            font-weight: bold !important;
                        }

                        #print h3 {
                            text-decoration: underline !important;
                            text-align: center !important;
                        }

                        #print p {
                            margin: 5px 0 !important;
                        }

                        #print .footer {
                            margin-top: 20px !important;
                            display: flex !important;
                            justify-content: space-between !important;
                            font-size: 14px !important;
                        }

                        /* Hanya kasih border-top dari kolom Qty sampai Jumlah */
                        #print .subtotal-row td:nth-child(n+5):nth-child(-n+9) {
                            border-top: 1px solid #000 !important;
                        }

                        #print table {
                            border-collapse: separate !important;
                            border-spacing: 0 !important;
                        }

                        @page {
                            margin: 0.5cm 1cm 0.5cm 1cm;
                            size: A4 landscape;
                        }

                        .btn,
                        .card-header,
                        .card-footer,
                        .page-info {
                            display: none !important;
                        }

                        #print {
                            font-family: 'Calibri', Arial, sans-serif !important;
                            font-size: 15px !important;
                        }
                    }
                </style>
                <div class="card print-page">
                    <div class="card-body">
                        <div class="container mb-5 mt-1">
                            <div style="max-width:2480px; margin:auto;">
                                {{-- <h4>TOKO BAN CAHAYA TERANG - SURABAYA</h4> --}}
                                <h3 style="text-decoration:underline; text-align:center; margin-bottom: 40px;">
                                    LAPORAN PENJUALAN MASA
                                    @if ($dateFrom && $dateTo)
                                        {{ strtoupper(\Carbon\Carbon::parse($dateFrom)->format('d F Y')) }} -
                                        {{ strtoupper(\Carbon\Carbon::parse($dateTo)->format('d F Y')) }}
                                    @elseif($selectedMasa)
                                        {{ strtoupper(\Carbon\Carbon::parse($selectedMasa . '-01')->format('F Y')) }}
                                    @else
                                        -
                                    @endif
                                </h3>
                                {{-- <p style="text-align:left; margin-bottom:20px;">
                                    Periode:
                                    {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d-M-Y') : '-' }}
                                    s/d {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d-M-Y') : '-' }}
                                </p> --}}

                                {{-- @if (is_array($results) || (is_object($results) && method_exists($results, 'count')) ? count($results) > 0 : false) --}}
                                <table style="width:100%; border-collapse:collapse;">
                                    <thead>
                                        <tr>
                                            <th
                                                style="text-align: center; padding:4px 8px; font-weight:bold; border-bottom: 1px solid #000;">
                                                No. Faktur</th>
                                            <th
                                                style="text-align: center; padding:4px 8px; font-weight:bold; border-bottom: 1px solid #000;">
                                                Tgl. Nota</th>
                                            <th
                                                style="text-align: center; padding:4px 8px; font-weight:bold; border-bottom: 1px solid #000;">
                                                Nama WP</th>
                                            <th
                                                style="text-align: center; padding:4px 8px; font-weight:bold; border-bottom: 1px solid #000;">
                                                Nama Barang</th>
                                            <th
                                                style="text-align: center; padding:4px 8px; font-weight:bold; border-bottom: 1px solid #000;">
                                                Qty</th>
                                            <th
                                                style="text-align: center; padding:4px 8px; font-weight:bold; border-bottom: 1px solid #000;">
                                                Harga</th>
                                            <th
                                                style="text-align: center; padding:4px 8px; font-weight:bold; border-bottom: 1px solid #000;">
                                                DPP</th>
                                            <th
                                                style="text-align: center; padding:4px 8px; font-weight:bold; border-bottom: 1px solid #000;">
                                                PPN</th>
                                            <th
                                                style="text-align: center; padding:4px 8px; font-weight:bold; border-bottom: 1px solid #000;">
                                                JUMLAH</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $grandTotalQty = 0;
                                            $grandTotalPrice = 0;
                                            $grandTotalDpp = 0;
                                            $grandTotalPpn = 0;
                                            $grandTotalAmount = 0;
                                            $currentTrCode = null;
                                            $trCodeSubtotalQty = 0;
                                            $trCodeSubtotalPrice = 0;
                                            $trCodeSubtotalDpp = 0;
                                            $trCodeSubtotalPpn = 0;
                                            $trCodeSubtotalAmount = 0;
                                            $previousTrCodeSubtotalQty = 0;
                                            $previousTrCodeSubtotalPrice = 0;
                                            $previousTrCodeSubtotalDpp = 0;
                                            $previousTrCodeSubtotalPpn = 0;
                                            $previousTrCodeSubtotalAmount = 0;
                                        @endphp
                                        @foreach ($results as $index => $row)
                                            @php
                                                // Safety check to ensure $row is an object
                                                if (!is_object($row)) {
                                                    continue;
                                                }

                                                $invoiceNo = $row->invoice_no ?? '';
                                                $invoiceDate = $row->invoice_date
                                                    ? \Carbon\Carbon::parse($row->invoice_date)->format('d-M-Y')
                                                    : '';
                                                $customerName = $row->customer_name ?? '';
                                                $trCode = $row->tr_code ?? '';
                                                $itemName = $row->item_name ?? '';
                                                $qty = $row->qty ?? 0;
                                                $price = $row->price ?? 0;
                                                $dpp = $row->dpp ?? 0;
                                                $ppn = $row->ppn ?? 0;
                                                $totalAmount = $row->total_amount ?? 0;

                                                // Check if this is a new tr_code (nota)
                                                $isNewTrCode = $currentTrCode !== $trCode;

                                                if ($isNewTrCode) {
                                                    // If not the first tr_code, show subtotal for previous tr_code
                                                    if ($currentTrCode !== null) {
                                                        $previousTrCodeSubtotalQty = $trCodeSubtotalQty;
                                                        $previousTrCodeSubtotalPrice = $trCodeSubtotalPrice;
                                                        $previousTrCodeSubtotalDpp = $trCodeSubtotalDpp;
                                                        $previousTrCodeSubtotalPpn = $trCodeSubtotalPpn;
                                                        $previousTrCodeSubtotalAmount = $trCodeSubtotalAmount;
                                                    }

                                                    // Reset subtotals for new tr_code
                                                    $currentTrCode = $trCode;
                                                    $trCodeSubtotalQty = 0;
                                                    $trCodeSubtotalPrice = 0;
                                                    $trCodeSubtotalDpp = 0;
                                                    $trCodeSubtotalPpn = 0;
                                                    $trCodeSubtotalAmount = 0;
                                                }

                                                // Add to tr_code subtotals
                                                $trCodeSubtotalQty += $qty;
                                                $trCodeSubtotalPrice += $price;
                                                $trCodeSubtotalDpp += $dpp;
                                                $trCodeSubtotalPpn += $ppn;
                                                $trCodeSubtotalAmount += $totalAmount;

                                                // Add to grand totals
                                                $grandTotalQty += $qty;
                                                $grandTotalPrice += $price;
                                                $grandTotalDpp += $dpp;
                                                $grandTotalPpn += $ppn;
                                                $grandTotalAmount += $totalAmount;
                                            @endphp

                                            @if ($isNewTrCode && $currentTrCode !== null && $index > 0)
                                                {{-- Show subtotal row for previous tr_code --}}
                                                <tr class="subtotal-row">
                                                    <td colspan="4"
                                                        style="padding:4px 8px; font-size: 16px; text-align: right; font-weight: bold;">
                                                    </td>
                                                    <td
                                                        style="padding:4px 8px; border-top: 1px solid #000; font-size: 16px; text-align: right;">
                                                        {{ number_format($previousTrCodeSubtotalQty, 0) }}</td>
                                                    <td
                                                        style="padding:4px 8px; border-top: 1px solid #000; font-size: 16px; text-align: right;">
                                                        {{ number_format($previousTrCodeSubtotalPrice, 0, ',', '.') }}
                                                    </td>
                                                    <td
                                                        style="padding:4px 8px; border-top: 1px solid #000; font-size: 16px; text-align: right;">
                                                        {{ number_format($previousTrCodeSubtotalDpp, 0, ',', '.') }}
                                                    </td>
                                                    <td
                                                        style="padding:4px 8px; border-top: 1px solid #000; font-size: 16px; text-align: right;">
                                                        {{ number_format($previousTrCodeSubtotalPpn, 0, ',', '.') }}
                                                    </td>
                                                    <td
                                                        style="padding:4px 8px; border-top: 1px solid #000; font-size: 16px; text-align: right;">
                                                        {{ number_format($previousTrCodeSubtotalAmount, 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td style="padding:4px 8px; font-size: 16px; text-align: left;">
                                                    {{ $isNewTrCode ? $invoiceNo : '' }}</td>
                                                <td
                                                    style="padding:4px 8px; font-size: 16px; text-align: left; white-space: nowrap;">
                                                    {{ $isNewTrCode ? $invoiceDate : '' }}</td>
                                                <td style="padding:4px 8px; font-size: 16px; text-align: left;">
                                                    {{ $isNewTrCode ? $customerName : '' }}</td>
                                                <td style="padding:4px 8px; font-size: 16px; text-align: left;">
                                                    {{ $itemName }}</td>
                                                <td style="padding:4px 8px; font-size: 16px; text-align: right;">
                                                    {{ number_format($qty, 0) }}</td>
                                                <td style="padding:4px 8px; font-size: 16px; text-align: right;">
                                                    {{ number_format($price, 0, ',', '.') }}</td>
                                                <td style="padding:4px 8px; font-size: 16px; text-align: right;">
                                                    {{ number_format($dpp, 0, ',', '.') }}</td>
                                                <td style="padding:4px 8px; font-size: 16px; text-align: right;">
                                                    {{ number_format($ppn, 0, ',', '.') }}</td>
                                                <td style="padding:4px 8px; font-size: 16px; text-align: right;">
                                                    {{ number_format($totalAmount, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach

                                        @if ($currentTrCode !== null)
                                            {{-- Show subtotal for last tr_code --}}
                                            <tr class="subtotal-row">
                                                <td colspan="4"
                                                    style="padding:4px 8px; font-size: 16px; text-align: right; font-weight: bold;">
                                                </td>
                                                <td
                                                    style="padding:4px 8px; border-top: 1px solid #000; font-size: 16px; text-align: right;">
                                                    {{ number_format($trCodeSubtotalQty, 0) }}</td>
                                                <td
                                                    style="padding:4px 8px; border-top: 1px solid #000; font-size: 16px; text-align: right;">
                                                    {{ number_format($trCodeSubtotalPrice, 0, ',', '.') }}</td>
                                                <td
                                                    style="padding:4px 8px; border-top: 1px solid #000; font-size: 16px; text-align: right;">
                                                    {{ number_format($trCodeSubtotalDpp, 0, ',', '.') }}</td>
                                                <td
                                                    style="padding:4px 8px; border-top: 1px solid #000; font-size: 16px; text-align: right;">
                                                    {{ number_format($trCodeSubtotalPpn, 0, ',', '.') }}</td>
                                                <td
                                                    style="padding:4px 8px; border-top: 1px solid #000; font-size: 16px; text-align: right;">
                                                    {{ number_format($trCodeSubtotalAmount, 0, ',', '.') }}</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>


                                {{-- @else
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> Tidak ada data untuk masa yang dipilih.
                                    </div>
                                @endif --}}
                            </div>
                        </div>
                    </div>
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
