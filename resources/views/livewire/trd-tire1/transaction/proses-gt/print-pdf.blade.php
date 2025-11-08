<div>
    <!-- Tombol Back dan Print -->
    <div class="row d-flex align-items-baseline">
        <div class="col-xl-9">
            {{-- <x-ui-button clickEvent="" type="Back" button-name="Back" /> --}}
        </div>
        <div class="col-xl-3 float-end">
            <a class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark" onclick="printInvoice()">
                <i class="fas fa-print text-primary"></i> Print
            </a>
        </div>
        <hr>
    </div>

    <!-- Include CSS Invoice -->
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        @media print {
            .d-print-none {
                display: none !important;
            }

            .d-print-block {
                display: block !important;
            }

            body {
                margin: 0;
                padding: 0;
                font-family: 'Calibri', Arial, sans-serif;
                font-size: 12px;
                line-height: 1.2;
            }

            #print {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 0;
            }

            .invoice-box {
                max-width: none;
                width: 100%;
                margin: 0;
                padding: 5mm;
                box-sizing: border-box;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 10px;
            }

            th {
                border: 1px solid #000;
                padding: 3px 5px;
                font-size: 13px;
                line-height: 1.2;
                background-color: #f0f0f0;
                font-weight: bold;
                text-align: center;
            }

            td {
                padding: 3px 5px;
                font-size: 13px;
                line-height: 1.2;
                border: none;
            }

            .text-left { text-align: left; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
        }

        /* Style untuk view screen */
        .view-table {
            width: 100%;
            border-collapse: collapse;
        }

        .view-table thead th {
            border: 1px solid #000;
            padding: 8px;
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .view-table tbody td {
            padding: 8px;
            border: none;
        }

        .view-table tbody tr {
            border-bottom: none;
        }
    </style>

    <!-- Area untuk View (Screen) -->
    <div class="card d-print-none">
        <div class="card-body">
            <div class="container mb-5 mt-3">
                <!-- Header Report -->
                <div class="row d-flex align-items-baseline">
                    <div class="col-xl-12">
                        <p style="color: #7e8d9f; font-size: 20px;">
                            PROSES NOTA GAJAH TUNGGAL GT RADIAL
                        </p>
                    </div>
                    <hr>
                </div>

                <!-- Content untuk View -->
                <div class="table-responsive">
                    <table class="view-table">
                        <thead>
                            <tr>
                                <th>Nama Pelanggan</th>
                                <th>No. Nota</th>
                                <th>Kode Brg.</th>
                                <th>Nama Barang</th>
                                <th>T. Ban</th>
                                <th>Point</th>
                                <th>T. Point</th>
                                <th>Nota GT</th>
                                <th>Customer Point</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (!empty($orders) && count($orders) > 0)
                                @php
                                    $allDetailsView = [];
                                    foreach ($orders as $order) {
                                        foreach ($order->OrderDtl as $detail) {
                                            $allDetailsView[] = [
                                                'order' => $order,
                                                'detail' => $detail
                                            ];
                                        }
                                    }
                                    // Hitung total untuk view
                                    $totalQtyView = 0;
                                    $totalPointView = 0;
                                    foreach ($allDetailsView as $item) {
                                        $detail = $item['detail'];
                                        $qty = $detail->qty ?? 0;
                                        $totalQtyView += $qty;

                                        if (isset($detail->aggregated_total_point)) {
                                            $totalPointView += $detail->aggregated_total_point;
                                        } elseif ($detail->SalesReward && $detail->SalesReward->qty > 0) {
                                            $totalPointView += ($qty / $detail->SalesReward->qty) * $detail->SalesReward->reward;
                                        }
                                    }
                                @endphp
                                @foreach ($allDetailsView as $index => $item)
                                    @php
                                        $order = $item['order'];
                                        $detail = $item['detail'];
                                        $currentGtTrCode = $detail->gt_tr_code ?? '-';
                                        $isLastRow = $index === count($allDetailsView) - 1;
                                        $nextGtTrCode = !$isLastRow ? ($allDetailsView[$index + 1]['detail']->gt_tr_code ?? '-') : null;
                                        $hasBorderBottom = !$isLastRow && $currentGtTrCode !== $nextGtTrCode;
                                        $borderBottomStyle = $hasBorderBottom ? 'border-bottom: 1px solid #000;' : '';
                                    @endphp
                                    <tr>
                                        <td style="{{ $borderBottomStyle }}">{{ $this->getCustomerName($order, $detail) }}</td>
                                        <td style="{{ $borderBottomStyle }}"></td>
                                        <td style="{{ $borderBottomStyle }}">{{ $detail->matl_code }}</td>
                                        <td style="{{ $borderBottomStyle }}">{{ $detail->matl_descr }}</td>
                                        <td style="text-align: center; {{ $borderBottomStyle }}">{{ ceil($this->getAggregatedQty($detail)) }}</td>
                                        <td style="text-align: center; {{ $borderBottomStyle }}">
                                            {{ $this->getAggregatedPoint($detail) }}
                                        </td>
                                        <td style="text-align: center; {{ $borderBottomStyle }}">
                                            {{ $this->getAggregatedTotalPoint($detail) }}
                                        </td>
                                        <td style="{{ $borderBottomStyle }}">{{ $detail->gt_tr_code ?? '-' }}</td>
                                        <td style="{{ $borderBottomStyle }}">{{ $this->getCustomerPointName($detail, $order->Partner->city ?? '') }}</td>
                                    </tr>
                                @endforeach
                                @if(count($allDetailsView) > 0)
                                    <tr>
                                        <td colspan="4" style="padding: 5px; font-weight: bold; text-align: right; border-top: 1px solid #000;"></td>
                                        <td style="padding: 5px; font-weight: bold; text-align: center; border-top: 1px solid #000;">
                                            {{ ceil($totalQtyView) }}
                                        </td>
                                        <td style="padding: 5px; font-weight: bold; text-align: center; border-top: 1px solid #000;">
                                            -
                                        </td>
                                        <td style="padding: 5px; font-weight: bold; text-align: center; border-top: 1px solid #000;">
                                            {{ number_format($totalPointView) }}
                                        </td>
                                        <td colspan="2" style="padding: 5px; border-top: 1px solid #000;"></td>
                                    </tr>
                                @endif
                            @else
                                <tr>
                                    <td colspan="9" class="text-center">Tidak ada data untuk ditampilkan.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Area untuk Print -->
    <div id="print" class="d-none d-print-block">
        <div class="invoice-box page" style="max-width: 2480px; margin: auto; padding: 20px;">
            <h3 class="text-left" style="text-decoration: underline;">Proses Nota Gajah Tunggal GT RADIAL
                per Customer</h3>
            <p class="text-left">Tanggal Proses:
                {{ \Carbon\Carbon::parse($selectedProcessDate)->format('d-M-Y') }}</p>

            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #000; text-align: left; padding: 5px; font-size: 13px;">Nama Pelanggan</th>
                        <th style="border: 1px solid #000; text-align: left; padding: 5px; font-size: 13px;">No. Nota</th>
                        <th style="border: 1px solid #000; text-align: left; padding: 5px; font-size: 13px;">Kode Brg.</th>
                        <th style="border: 1px solid #000; text-align: left; padding: 5px; font-size: 13px;">Nama Barang</th>
                        <th style="border: 1px solid #000; text-align: center; padding: 5px; font-size: 13px;">T. Ban</th>
                        <th style="border: 1px solid #000; text-align: center; padding: 5px; font-size: 13px;">Point</th>
                        <th style="border: 1px solid #000; text-align: center; padding: 5px; font-size: 13px;">T. Point</th>
                        <th style="border: 1px solid #000; text-align: left; padding: 5px; font-size: 13px;">Nota GT</th>
                        <th style="border: 1px solid #000; text-align: left; padding: 5px; font-size: 13px;">Customer Point</th>
                    </tr>
                </thead>
                <tbody>
                    @if (!empty($orders) && count($orders) > 0)
                        @php
                            $allDetails = [];
                            foreach ($orders as $order) {
                                foreach ($order->OrderDtl as $detail) {
                                    $allDetails[] = [
                                        'order' => $order,
                                        'detail' => $detail
                                    ];
                                }
                            }
                            // Hitung total setelah semua data dikumpulkan
                            // Karena data sudah di-aggregate, kita cukup sum semua detail yang ada
                            $totalQty = 0;
                            $totalPoint = 0;
                            foreach ($allDetails as $item) {
                                $detail = $item['detail'];
                                // Qty sudah di-update dengan nilai aggregate di PrintPdf.php
                                $qty = $detail->qty ?? 0;
                                $totalQty += $qty;

                                // Gunakan aggregated_total_point jika ada (sudah di-sum)
                                if (isset($detail->aggregated_total_point)) {
                                    $totalPoint += $detail->aggregated_total_point;
                                } elseif ($detail->SalesReward && $detail->SalesReward->qty > 0) {
                                    // Fallback: hitung jika property tidak ada
                                    $totalPoint += ($qty / $detail->SalesReward->qty) * $detail->SalesReward->reward;
                                }
                            }
                        @endphp
                        @foreach ($allDetails as $index => $item)
                            @php
                                $order = $item['order'];
                                $detail = $item['detail'];
                                $currentGtTrCode = $detail->gt_tr_code ?? '-';
                                $isLastRow = $index === count($allDetails) - 1;
                                $nextGtTrCode = !$isLastRow ? ($allDetails[$index + 1]['detail']->gt_tr_code ?? '-') : null;
                                $hasBorderBottom = !$isLastRow && $currentGtTrCode !== $nextGtTrCode;
                                $borderBottomStyle = $hasBorderBottom ? 'border-bottom: 1px solid #000;' : '';
                            @endphp
                            <tr>
                                <td style="padding: 3px 5px; font-size: 13px; {{ $borderBottomStyle }}">
                                    {{ $this->getCustomerName($order, $detail) }}
                                </td>
                                <td style="padding: 3px 5px; font-size: 13px; {{ $borderBottomStyle }}">

                                </td>
                                <td style="padding: 3px 5px; font-size: 13px; {{ $borderBottomStyle }}">
                                    {{ $detail->matl_code }}
                                </td>
                                <td style="padding: 3px 5px; font-size: 13px; {{ $borderBottomStyle }}">
                                    {{ $detail->matl_descr }}
                                </td>
                                <td style="padding: 3px 5px; font-size: 13px; text-align: center; {{ $borderBottomStyle }}">
                                    {{ ceil($this->getAggregatedQty($detail)) }}
                                </td>
                                <td style="padding: 3px 5px; font-size: 13px; text-align: center; {{ $borderBottomStyle }}">
                                    {{ $this->getAggregatedPoint($detail) }}
                                </td>
                                <td style="padding: 3px 5px; font-size: 13px; text-align: center; {{ $borderBottomStyle }}">
                                    {{ $this->getAggregatedTotalPoint($detail) }}
                                </td>
                                <td style="padding: 3px 5px; font-size: 13px; {{ $borderBottomStyle }}">
                                    {{ $detail->gt_tr_code ?? '-' }}
                                </td>
                                <td style="padding: 3px 5px; font-size: 13px; {{ $borderBottomStyle }}">
                                    {{ $this->getCustomerPointName($detail, $order->Partner->city ?? '') }}
                                </td>
                            </tr>
                        @endforeach
                        @if(count($allDetails) > 0)
                            <tr>
                                <td colspan="4" style="padding: 5px; font-size: 13px; font-weight: bold; text-align: right; border-top: 1px solid #000;">

                                </td>
                                <td style="padding: 5px; font-size: 13px; font-weight: bold; text-align: center; border-top: 1px solid #000;">
                                    {{ ceil($totalQty) }}
                                </td>
                                <td style="padding: 5px; font-size: 13px; font-weight: bold; text-align: center; border-top: 1px solid #000;">
                                    -
                                </td>
                                <td style="padding: 5px; font-size: 13px; font-weight: bold; text-align: center; border-top: 1px solid #000;">
                                    {{ number_format($totalPoint) }}
                                </td>
                                <td colspan="2" style="padding: 5px; font-size: 13px; border-top: 1px solid #000;">
                                </td>
                            </tr>
                        @endif                    @else
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 8px; font-size: 13px;">
                                Tidak ada data untuk ditampilkan.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Script untuk Print -->
    <script>
        function printInvoice() {
            window.print();
        }
    </script>
</div>
