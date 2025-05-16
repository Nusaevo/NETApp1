<div>
    <!-- Tombol Back -->
    <div>
        <x-ui-button clickEvent="back" type="Back" button-name="Back" />
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
                            FAKTUR PAJAK REPORT
                            {{-- <strong>No: {{ $this->object->code ?? '' }}</strong> --}}
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
                        <h3 class="text-left" style="text-decoration: underline;">Proses Faktur Pajak</h3>
                        <p class="text-left">Tanggal Proses:
                            {{ \Carbon\Carbon::parse($printDate)->format('d-M-Y') }}</p>
                        @if (!isset($orders))
                            <p class="text-center text-danger">Tidak ada data untuk ditampilkan.</p>
                        @else
                            <table class="table table-bordered">
                                <thead>
                                    <tr style="border-bottom: 1px solid #000;">
                                        <th>No. Nota</th>
                                        <th>No. Faktur</th>
                                        <th>Tanggal</th>
                                        <th>Nama Pelanggan</th>
                                        <th>Nama Barang</th>
                                        <th>Qty</th>
                                        <th>Harga Pcs</th>
                                        <th>Dpp</th>
                                        <th>PPN</th>
                                        <th>Dpp + PPN</th>
                                        <th>Amt Nota</th>
                                        <th>Hitung PPN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orders as $order)
                                        @php
                                            $totalAmt = 0;
                                            $totalPpn = 0;
                                        @endphp
                                        @foreach ($order->OrderDtl as $index => $detail)
                                            @php
                                                // $amt = $detail->qty * $detail->price;
                                                $dpp = $detail->dpp;
                                                $ppn = $detail->ppn ?? 0;
                                                $totalAmt = $detail->amt;
                                                $totalPpn = $detail->amt * 11 / 100;
                                            @endphp
                                            <tr style="font-weight: normal;">
                                                @if ($index === 0)
                                                    <!-- Hanya tampilkan pada seq 1 -->
                                                    <td rowspan="{{ count($order->OrderDtl) }}">{{ $order->tr_code }}
                                                    </td>
                                                    <td rowspan="{{ count($order->OrderDtl) }}"
                                                        style="text-align: left">{{ $order->print_remarks }}</td>
                                                    <td rowspan="{{ count($order->OrderDtl) }}">
                                                        {{ \Carbon\Carbon::parse($order->tr_date)->format('d-M-Y') }}
                                                    </td>
                                                    <td rowspan="{{ count($order->OrderDtl) }}">
                                                        {{ $order->Partner?->name ?? 'N/A' }}</td>
                                                @endif
                                                <td>{{ $detail->matl_descr }}</td>
                                                <td style="text-align: center">{{ $detail->qty }}</td>
                                                <td>{{ number_format($detail->price, 0, ',', '.') }}</td>
                                                <td>{{ number_format($dpp, 0, ',', '.') }}</td>
                                                <td>{{ number_format($ppn, 0, ',', '.') }}</td>
                                                <td>{{ number_format($dpp + $ppn, 0, ',', '.') }}</td>
                                                <td>{{ number_format($totalAmt, 0, ',', '.') }}</td>
                                                <!-- Tampilkan total Amt Nota -->
                                                <td>{{ number_format($totalPpn, 0, ',', '.') }}</td>
                                                <!-- Tampilkan total Hitung PPN -->
                                            </tr>
                                        @endforeach
                                        @if (count($order->OrderDtl) > 1)
                                            <!-- Tampilkan subtotal jika lebih dari satu item -->
                                            <tr style="font-weight: normal; border-top: 1px solid #000;">
                                                <td colspan="7" style="text-align: right;"></td>
                                                <td style="border-top: 1px solid #000;">
                                                    {{ number_format($totalAmt, 0, ',', '.') }}</td>
                                                <td style="border-top: 1px solid #000;">
                                                    {{ number_format($totalPpn, 0, ',', '.') }}</td>
                                                <td style="border-top: 1px solid #000;">
                                                    {{ number_format($totalAmt + $totalPpn, 0, ',', '.') }}</td>
                                            </tr>
                                        @endif
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
