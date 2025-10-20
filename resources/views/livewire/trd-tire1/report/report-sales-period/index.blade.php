<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        {{-- Filter Frame --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="container mb-2 mt-2">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <x-ui-dropdown-select label="Masa" model="selectedMasa" :options="$masaOptions" action="Edit" />
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
                        .mb-5 { margin-bottom: 0 !important; }
                        #print table {
                            margin-left: auto !important;
                            margin-right: auto !important;
                            border-collapse: collapse !important;
                            width: 100% !important;
                        }
                        #print th, #print td {
                            padding: 4px 6px !important;
                            font-size:17px !important;
                            border: 1px solid #000 !important;
                            vertical-align: middle !important;
                        }
                        #print th {
                            background: #f9f9f9 !important;
                            font-weight: bold !important;
                            text-align: center !important;
                        }
                        #print h3, #print h4 {
                            margin: 10px 0 !important;
                            font-weight: bold !important;
                        }
                        #print p {
                            margin: 5px 0 !important;
                        }
                        @page {
                            margin: 0.5cm 1cm 0.5cm 1cm;
                            size: A4 portrait;
                        }
                        .btn, .card-header, .card-footer, .page-info {
                            display: none !important;
                        }
                        #print {
                            font-family: 'Calibri', Arial, sans-serif !important;
                            font-size:15px !important;
                        }
                    }
                </style>
                <div class="card print-page">
                    <div class="card-body">
                        <div class="container mb-5 mt-1">
                            <div style="max-width:2480px; margin:auto;">
                                <h4>TOKO BAN CAHAYA TERANG - SURABAYA</h4>
                                <h3 style="text-decoration:underline; text-align:left;">
                                    LAPORAN PENJUALAN PERIODE
                                </h3>
                                <p style="text-align:left; margin-bottom:0;">
                                    <strong>Masa : {{ $selectedMasa ? \Carbon\Carbon::parse($selectedMasa . '-01')->format('F Y') : '-' }}</strong>
                                </p>
                                <p style="text-align:left; margin-bottom:20px;">
                                    Periode:
                                    {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d-M-Y') : '-' }}
                                    s/d {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d-M-Y') : '-' }}
                                </p>

                                @if(is_array($results) || (is_object($results) && method_exists($results, 'count')) ? count($results) > 0 : false)
                                    <table style="width:100%; border-collapse:collapse;">
                                        <thead>
                                            <tr>
                                                <th style="border: 1px solid #000; text-align: center; padding:4px 8px; background:#f9f9f9; font-weight:bold;">No</th>
                                                <th style="border: 1px solid #000; text-align: center; padding:4px 8px; background:#f9f9f9; font-weight:bold;">Customer</th>
                                                <th style="border: 1px solid #000; text-align: center; padding:4px 8px; background:#f9f9f9; font-weight:bold;">Kota</th>
                                                <th style="border: 1px solid #000; text-align: center; padding:4px 8px; background:#f9f9f9; font-weight:bold;">Jumlah Nota</th>
                                                <th style="border: 1px solid #000; text-align: center; padding:4px 8px; background:#f9f9f9; font-weight:bold;">Total Qty</th>
                                                <th style="border: 1px solid #000; text-align: center; padding:4px 8px; background:#f9f9f9; font-weight:bold;">DPP</th>
                                                <th style="border: 1px solid #000; text-align: center; padding:4px 8px; background:#f9f9f9; font-weight:bold;">PPN</th>
                                                <th style="border: 1px solid #000; text-align: center; padding:4px 8px; background:#f9f9f9; font-weight:bold;">Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $grandTotalOrders = 0;
                                                $grandTotalQty = 0;
                                                $grandTotalDpp = 0;
                                                $grandTotalPpn = 0;
                                                $grandTotalAmount = 0;
                                            @endphp
                                            @foreach ($results as $index => $row)
                                                @php
                                                    // Safety check to ensure $row is an object
                                                    if (!is_object($row)) {
                                                        continue;
                                                    }

                                                    $totalOrders = $row->total_orders ?? 0;
                                                    $totalQty = $row->total_qty ?? 0;
                                                    $totalDpp = $row->total_dpp ?? 0;
                                                    $totalPpn = $row->total_ppn ?? 0;
                                                    $totalAmount = $row->total_amount ?? 0;

                                                    $grandTotalOrders += $totalOrders;
                                                    $grandTotalQty += $totalQty;
                                                    $grandTotalDpp += $totalDpp;
                                                    $grandTotalPpn += $totalPpn;
                                                    $grandTotalAmount += $totalAmount;
                                                @endphp
                                                <tr>
                                                    <td style="padding:4px 8px; border: 1px solid #000; font-size: 16px; text-align: center;">{{ $index + 1 }}</td>
                                                    <td style="padding:4px 8px; border: 1px solid #000; font-size: 16px;">{{ $row->customer_name ?? '' }}</td>
                                                    <td style="padding:4px 8px; border: 1px solid #000; font-size: 16px;">{{ $row->customer_city ?? '' }}</td>
                                                    <td style="padding:4px 8px; border: 1px solid #000; font-size: 16px; text-align: center;">{{ $totalOrders }}</td>
                                                    <td style="padding:4px 8px; border: 1px solid #000; font-size: 16px; text-align: right;">{{ number_format($totalQty, 0) }}</td>
                                                    <td style="padding:4px 8px; border: 1px solid #000; font-size: 16px; text-align: right;">{{ rupiah($totalDpp) }}</td>
                                                    <td style="padding:4px 8px; border: 1px solid #000; font-size: 16px; text-align: right;">{{ rupiah($totalPpn) }}</td>
                                                    <td style="padding:4px 8px; border: 1px solid #000; font-size: 16px; text-align: right;">{{ rupiah($totalAmount) }}</td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td colspan="3" style="padding:4px 8px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; text-align: center;">TOTAL</td>
                                                <td style="padding:4px 8px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; text-align: center;">{{ $grandTotalOrders }}</td>
                                                <td style="padding:4px 8px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; text-align: right;">{{ number_format($grandTotalQty, 0) }}</td>
                                                <td style="padding:4px 8px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; text-align: right;">{{ rupiah($grandTotalDpp) }}</td>
                                                <td style="padding:4px 8px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; text-align: right;">{{ rupiah($grandTotalPpn) }}</td>
                                                <td style="padding:4px 8px; border: 1px solid #000; font-weight:bold; background:#f2f2f2; text-align: right;">{{ rupiah($grandTotalAmount) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                @else
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> Tidak ada data untuk masa yang dipilih.
                                    </div>
                                @endif
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
