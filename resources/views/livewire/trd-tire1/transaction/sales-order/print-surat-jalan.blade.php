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
                    <div class="row d-flex align-items-baseline">
                        <div class="col-xl-9">
                            <p style="color: #7e8d9f; font-size: 20px;">
                                NOTA PENJUALAN >>
                                <strong>No: {{ $this->object->tr_code }}</strong>
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
                        <div class="invoice-box"
                            style="max-width: 800px; margin: auto; padding: 20px; border: 1px solid #eee;">
                            <!-- Header -->
                            <table width="100%" style="margin-bottom: 10px;">
                                <tr>
                                    <td style="width: 80%;">
                                        <h2 style="margin: 0; text-decoration: underline; font-weight: bold;">CAHAYA
                                            TERANG</h2>
                                        <p style="margin: 0; text-align: center; width: 23%;">SURABAYA</p>
                                    </td>
                                    <td style="text-align: left;">
                                        <p style="margin: 0;">
                                            Surabaya,
                                            {{ \Carbon\Carbon::parse($this->object->tr_date)->format('d-M-Y') }}
                                        </p>
                                        <!-- Customer Info -->
                                        <div style="margin-bottom: 20px;">
                                            <p style="margin: 0;">Kepada Yth :</p>
                                            <p style="margin: 0;"><strong>{{ $this->object->Partner->name }}</strong>
                                            </p>
                                            <p style="margin: 0;">{{ $this->object->Partner->address }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Title -->
                            <div style="text-align: center; margin: 20px 0;">
                                <h3 style="margin: 0; font-weight: bold; text-decoration: underline;">NOTA SURAT JALAN
                                </h3>
                                <p style="margin: 5px 0;">No. {{ $this->object->tr_code }}</p>
                            </div>



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
                                    @foreach ($this->object->OrderDtl as $key => $OrderDtl)
                                        @php
                                            $subTotal = $OrderDtl->qty * $OrderDtl->price;
                                            $grand_total += $subTotal;
                                            $OrderHdr = $this->object->OrderHdr; // Access OrderHdr within the loop
                                        @endphp
                                        <tr>
                                            <td style="border: 1px solid #000; padding: 8px;">{{ $OrderDtl->matl_code }}
                                            </td>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: left;">
                                                {{ $OrderDtl->matl_descr }}</td>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: center;">
                                                {{ ceil($OrderDtl->qty) }}</td>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: right;">
                                                {{ number_format(ceil($OrderDtl->price), 0, ',', '.') }}</td>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: right;">
                                                {{ number_format(ceil($subTotal), 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="4"
                                            style="border: 1px solid #000; padding: 8px; text-align: right;">TOTAL:</td>
                                        <td style="border: 1px solid #000; padding: 8px; text-align: right;">
                                            {{ number_format($grand_total, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Payment Method -->
                            <div style="margin-top: 20px; display: flex; justify-content: space-between;">
                                <p style="margin: 0;">Pembayaran:
                                    <strong>{{ $this->object->payment_method ?? 'CASH' }}</strong>
                                </p>
                                <p style="margin: 0;">Penerima:
                                    <strong>{{ $OrderHdr ? $OrderHdr->partner_id->partner_name : 'N/A' }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>
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
