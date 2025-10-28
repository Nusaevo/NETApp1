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
            size: A4 portrait;
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

            th, td {
                border: 1px solid #000;
                padding: 3px 5px;
                font-size: 11px;
                line-height: 1.2;
            }

            th {
                background-color: #f0f0f0;
                font-weight: bold;
                text-align: center;
            }

            .text-left { text-align: left; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
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
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
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
                                @foreach ($orders as $order)
                                    @foreach ($order->OrderDtl as $detail)
                                        <tr>
                                            <td>{{ $order->Partner->name }} - {{ $order->Partner->city }}</td>
                                            <td>{{ $order->tr_code }}</td>
                                            <td>{{ $detail->matl_code }}</td>
                                            <td>{{ $detail->matl_descr }}</td>
                                            <td class="text-center">{{ ceil($detail->qty) }}</td>
                                            <td class="text-center">
                                                {{ $detail->SalesReward ? round($detail->SalesReward->reward / $detail->SalesReward->qty, 2) : 0 }}
                                            </td>
                                            <td class="text-center">
                                                {{ $detail->SalesReward ? round(($detail->qty / $detail->SalesReward->qty) * $detail->SalesReward->reward, 2) : 0 }}
                                            </td>
                                            <td>{{ $detail->gt_tr_code ?? '-' }}</td>
                                             <td>{{ $detail->gt_partner_code ? $detail->gt_partner_code . ' - ' . ($order->Partner->city ?? '') : '' }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
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
                    <tr style="border-bottom:1px solid #000;">
                        <th style="border: 1px solid #000; text-align: left; padding: 5px; font-size: 11px;">Nama Pelanggan</th>
                        <th style="border: 1px solid #000; text-align: left; padding: 5px; font-size: 11px;">No. Nota</th>
                        <th style="border: 1px solid #000; text-align: left; padding: 5px; font-size: 11px;">Kode Brg.</th>
                        <th style="border: 1px solid #000; text-align: left; padding: 5px; font-size: 11px;">Nama Barang</th>
                        <th style="border: 1px solid #000; text-align: center; padding: 5px; font-size: 11px;">T. Ban</th>
                        <th style="border: 1px solid #000; text-align: center; padding: 5px; font-size: 11px;">Point</th>
                        <th style="border: 1px solid #000; text-align: center; padding: 5px; font-size: 11px;">T. Point</th>
                        <th style="border: 1px solid #000; text-align: left; padding: 5px; font-size: 11px;">Nota GT</th>
                        <th style="border: 1px solid #000; text-align: left; padding: 5px; font-size: 11px;">Customer Point</th>
                    </tr>
                </thead>
                <tbody>
                    @if (!empty($orders) && count($orders) > 0)
                        @foreach ($orders as $order)
                            @foreach ($order->OrderDtl as $detail)
                                <tr>
                                    <td style="border: 1px solid #000; padding: 3px 5px; font-size: 11px;">
                                        {{ $order->Partner->name }} - {{ $order->Partner->city }}
                                    </td>
                                    <td style="border: 1px solid #000; padding: 3px 5px; font-size: 11px;">
                                        {{ $order->tr_code }}
                                    </td>
                                    <td style="border: 1px solid #000; padding: 3px 5px; font-size: 11px;">
                                        {{ $detail->matl_code }}
                                    </td>
                                    <td style="border: 1px solid #000; padding: 3px 5px; font-size: 11px;">
                                        {{ $detail->matl_descr }}
                                    </td>
                                    <td style="border: 1px solid #000; padding: 3px 5px; font-size: 11px; text-align: center;">
                                        {{ ceil($detail->qty) }}
                                    </td>
                                    <td style="border: 1px solid #000; padding: 3px 5px; font-size: 11px; text-align: center;">
                                        {{ $detail->SalesReward ? round($detail->SalesReward->reward / $detail->SalesReward->qty, 2) : 0 }}
                                    </td>
                                    <td style="border: 1px solid #000; padding: 3px 5px; font-size: 11px; text-align: center;">
                                        {{ $detail->SalesReward ? round(($detail->qty / $detail->SalesReward->qty) * $detail->SalesReward->reward, 2) : 0 }}
                                    </td>
                                    <td style="border: 1px solid #000; padding: 3px 5px; font-size: 11px;">
                                        {{ $detail->gt_tr_code ?? '-' }}
                                    </td>
                                    <td style="border: 1px solid #000; padding: 3px 5px; font-size: 11px;">
                                        {{ $detail->gt_partner_code ? $detail->gt_partner_code . ' - ' . ($order->Partner->city ?? '') : '' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" style="border: 1px solid #000; text-align: center; padding: 8px; font-size: 11px;">
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
