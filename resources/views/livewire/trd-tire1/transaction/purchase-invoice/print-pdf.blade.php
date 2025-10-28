<div>
    <!-- Tombol Back -->
    <div>
        {{-- <x-ui-button clickEvent="back" type="Back" button-name="Back" /> --}}
    </div>

    <!-- Include CSS Invoice -->
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <div class="card">
        <div class="card-body">
            <div class="container mb-5 mt-3">
                <!-- Header Report -->
                <div class="row d-flex align-items-baseline">
                    <div class="col-xl-9">
                        <p style="color: #7e8d9f; font-size: 20px;">
                            LAPORAN PENJUALAN MASA {{ strtoupper(\Carbon\Carbon::parse($masa)->translatedFormat('F Y')) }}
                        </p>
                    </div>
                    <div class="col-xl-3 float-end">
                        <a class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark"
                            onclick="printInvoice()">
                            <i class="fas fa-print text-primary"></i> Print
                        </a>
                    </div>
                    <hr>
                </div>
                <div id="print">
                    <div class="invoice-box page" style="max-width: 2480px; margin: auto; padding: 20px;">
                        {{-- yang di print --}}
                        <h3 class="text-center" style="text-decoration: underline;">LAPORAN PENJUALAN MASA {{ strtoupper(\Carbon\Carbon::parse($masa)->translatedFormat('F Y')) }}</h3>
                        @if (!isset($orders) || count($orders) === 0)
                            <p class="text-center text-danger">Tidak ada data untuk ditampilkan.</p>
                        @else
                            <table class="table table-bordered">
                                <thead>
                                    <tr style="border-bottom: 1px solid #000;">
                                        <th>No. Faktur</th>
                                        <th>Tgl. Nota</th>
                                        <th>Nama Customer</th>
                                        <th>Nama Barang</th>
                                        <th>Qty</th>
                                        <th>Harga</th>
                                        <th>DPP</th>
                                        <th>DPP Lain2</th>
                                        <th>PPN</th>
                                        <th>JUMLAH</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $grandTotalDpp = 0;
                                        $grandTotalPpn = 0;
                                        $grandTotalJumlah = 0;
                                    @endphp
                                    @foreach ($orders as $order)
                                        @php
                                            $totalDpp = 0;
                                            $totalPpn = 0;
                                            $totalJumlah = 0;
                                        @endphp
                                        @foreach ($order->OrderDtl as $index => $detail)
                                            @php
                                                $dpp = $detail->dpp;
                                                $ppn = $detail->ppn;
                                                $dpp2 = $detail->dpp * 11 / 12;
                                                $jumlah = $dpp + $ppn;
                                                $totalDpp += $dpp;
                                                $totalPpn += $ppn;
                                                $totalJumlah += $jumlah;
                                            @endphp
                                            <tr style="font-weight: normal;">
                                                @if ($index === 0)
                                                    <!-- Hanya tampilkan pada seq 1 -->
                                                    <td rowspan="{{ count($order->OrderDtl) }}">{{ $order->tax_doc_num }}</td>
                                                    <td style="text-align: left;" rowspan="{{ count($order->OrderDtl) }}">
                                                        {{ \Carbon\Carbon::parse($order->tr_date)->format('d-M-Y') }}
                                                    </td>
                                                    <td rowspan="{{ count($order->OrderDtl) }}">
                                                        {{ $order->Partner?->name ?? 'N/A' }}</td>
                                                @endif
                                                <td>{{ $detail->matl_descr }}</td>
                                                <td style="text-align: center">{{ $detail->qty }}</td>
                                                <td>{{ number_format($detail->price, 0, ',', '.') }}</td>
                                                <td>{{ number_format($dpp, 0, ',', '.') }}</td>
                                                <td>{{ number_format($dpp2, 0, ',', '.') }}</td>
                                                <td>{{ number_format($ppn, 0, ',', '.') }}</td>
                                                <td>{{ number_format($jumlah, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                        <!-- Subtotal -->
                                        <tr style="border-top: 1px solid #000; font-weight: normal;">
                                            <td colspan="6"></td>
                                            <td style="border-top: 1px solid #000; text-align: left;">{{ number_format($totalDpp, 0, ',', '.') }}</td>
                                            <td style="border-top: 1px solid #000;">{{ number_format($totalPpn, 0, ',', '.') }}</td>
                                            <td style="border-top: 1px solid #000;">{{ number_format($totalJumlah, 0, ',', '.') }}</td>
                                        </tr>
                                        @php
                                            $grandTotalDpp += $totalDpp;
                                            $grandTotalPpn += $totalPpn;
                                            $grandTotalJumlah += $totalJumlah;
                                        @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Script untuk Print -->
    <script>
        function printInvoice() {
            window.print();
        }
    </script>
</div>
