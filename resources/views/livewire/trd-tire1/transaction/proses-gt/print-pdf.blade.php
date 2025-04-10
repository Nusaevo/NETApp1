<div>
    <div>
        <div>
            <x-ui-button clickEvent="" type="Back" button-name="Back" />
        </div>
    </div>

    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <body>
        <div class="card">
            <div class="card-body">
                <div class="container mb-5 mt-3">
                    @foreach ($orders as $order)
                        <div class="row d-flex align-items-baseline">
                            <div class="col-xl-9">
                                <p style="color: #7e8d9f; font-size: 20px;">
                                    NOTA PENJUALAN >>
                                    <strong>No: {{ $order->tr_code }}</strong>
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
                            <div class="invoice-box" style="max-width: 800px; margin: auto; padding: 20px;  solid #000;">
                                <!-- Header -->
                                <table width="100%" style="margin-bottom: 10px;">
                                    <tr>
                                        <td style="width: 30%;">
                                            <div style="text-align: center;">
                                                <h2 style="margin: 0; text-decoration: underline; font-weight: bold;">CAHAYA
                                                    TERANG</h2>
                                                <p style="margin: 0;">SURABAYA</p>
                                            </div>
                                        </td>
                                        <td colspan="2"
                                            style="text-align: center; margin-top: 20px; vertical-align: bottom;">
                                            <h3 style="margin: 0; font-weight: bold; text-decoration: underline;">NOTA
                                                PENJUALAN</h3>
                                            <p style="margin: 5px 0;">No. {{ $order->tr_code }}</p>
                                        </td>
                                        <td style="text-align: right; vertical-align: bottom;">
                                            <p style="margin: 0;">
                                                Surabaya,
                                                {{ \Carbon\Carbon::parse($order->tr_date)->format('d-M-Y') }}
                                            </p>
                                            <p style="margin: 0;">Kepada Yth :</p>
                                            <p style="margin: 0;"><strong>{{ $order->Partner->name }}</strong></p>
                                            <p style="margin: 0;">{{ $order->Partner->address }}</p>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Items Table -->
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                    <thead>
                                        <tr>
                                            <th style="border: 1px solid #000; padding: 8px;">KODE BARANG</th>
                                            <th style="border: 1px solid #000; padding: 8px;">NAMA BARANG</th>
                                            <th style="border: 1px solid #000; padding: 8px; text-align: center;">QTY</th>
                                            <th style="border: 1px solid #000; padding: 8px; text-align: right;">HARGA
                                                SATUAN</th>
                                            <th style="border: 1px solid #000; padding: 8px; text-align: right;">JUMLAH
                                                HARGA</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $grand_total = 0;
                                        @endphp
                                        @foreach ($order->OrderDtl as $key => $OrderDtl)
                                            @php
                                                $subTotal = $OrderDtl->qty * $OrderDtl->price;
                                                $grand_total += $subTotal;
                                            @endphp
                                            <tr style="border: 1px solid #000;">
                                                <td style="padding: 8px; border: 1px solid #000;">{{ $OrderDtl->matl_code }}</td>
                                                <td style="padding: 8px; text-align: left; border: 1px solid #000;">
                                                    {{ $OrderDtl->matl_descr }}
                                                    @if($loop->last)
                                                        <p style="margin: 0; text-align: left;">Penerima: ________________</p>
                                                    @endif
                                                </td>
                                                <td style="padding: 8px; text-align: center; border: 1px solid #000;">{{ ceil($OrderDtl->qty) }}</td>
                                                <td style="padding: 8px; text-align: right; border: 1px solid #000;">
                                                    {{ number_format(ceil($OrderDtl->price), 0, ',', '.') }}
                                                </td>
                                                <td style="padding: 8px; text-align: right; border: 1px solid #000;">
                                                    {{ number_format(ceil($subTotal), 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                        <!-- Empty row for spacing like in the image -->
                                        <tr>
                                            <td colspan="3"
                                                style="border: 1px solid #000; padding: 8px; border-right: none; text-align: center;">
                                                <p style="margin: 0;">Pembayaran:
                                                    <strong>{{ $order->payment_method ?? 'CASH' }}</strong>
                                                </p>
                                            </td>
                                            <td
                                                style="border: 1px solid #000; padding: 8px; text-align: right; font-weight: bold;">
                                                TOTAL:</td>
                                            <td
                                                style="border: 1px solid #000; padding: 8px; text-align: right; font-weight: bold;">
                                                {{ number_format($grand_total, 0, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </body>

    <script type="text/javascript">
        function printInvoice() {
            var page = document.getElementById("print");
            var newWin = window.open('', 'Print-Window');
            newWin.document.open();
            newWin.document.write(
                '<html>' +
                '<link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}" >' +
                '<body onload="window.print()">' +
                page.innerHTML +
                '</body></html>'
            );
            newWin.document.close();
            setTimeout(function() {
                newWin.close();
            }, 10);
        }
    </script>
</div>
