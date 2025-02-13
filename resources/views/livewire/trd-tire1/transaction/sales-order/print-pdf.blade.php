<div>
    <div>
        <div>
            <!-- Tombol Back -->
            <x-ui-button clickEvent="" type="Back" button-name="Back" />
        </div>
    </div>

    <!-- Load CSS khusus invoice -->
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <body>
        <div class="card">
            <div class="card-body">
                <div class="container mb-5 mt-3">
                    <div class="row d-flex align-items-baseline">
                        <div class="col-xl-9">
                            <!-- Judul / Info Nota -->
                            <p style="color: #7e8d9f; font-size: 20px;">
                                NOTA PENJUALAN >>
                                <strong>No: {{ $this->object->tr_code }}</strong>
                            </p>
                        </div>
                        <div class="col-xl-3 float-end">
                            <!-- Tombol Print -->
                            <a class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark"
                                onclick="printInvoice()">
                                <i class="fas fa-print text-primary"></i> Print
                            </a>
                        </div>
                        <hr>
                    </div>

                    <!-- Bagian yang akan diprint -->
                    <div id="print">
                        <div class="invoice-box">

                            <!-- Header: Nama Toko & Tanggal -->
                            <table width="100%">
                                <tr>
                                    <td style="vertical-align: top;">
                                        <h2 style="margin:0;">CAHAYA TERANG</h2>
                                        <p style="margin:0;">SURABAYA</p>
                                    </td>
                                    <td style="text-align: right;">
                                        <p style="margin:0;">
                                            Surabaya,
                                            <!-- Contoh format 20-Nov-2024 -->
                                            {{ \Carbon\Carbon::parse($this->object->tr_date)->format('d-M-Y') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Judul Nota & Nomor Nota -->
                            <div style="text-align: center; margin-top: 10px;">
                                <h3 style="margin:0;">NOTA PENJUALAN</h3>
                                <p style="margin:0;">No. {{ $this->object->tr_code }}</p>
                            </div>

                            <!-- Tujuan Nota (Kepada Yth) -->
                            <div style="margin-top: 20px;">
                                <p style="margin:0;">
                                    Kepada Yth: <strong>{{ $this->object->Partner->name }}</strong>
                                </p>
                                <p style="margin:0;">
                                    {{ $this->object->Partner->address }}
                                </p>
                            </div>

                            <!-- Tabel Detail Penjualan -->
                            <table style="width: 100%; margin-top: 20px; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 1px solid #ddd;">
                                        <th style="text-align:center; padding: 8px;">Kode Barang</th>
                                        <th style="text-align:center; padding: 8px;">Nama Barang</th>
                                        <th style="text-align:center; padding: 8px;">QTY</th>
                                        <th style="text-align:center; padding: 8px;">Harga Satuan</th>
                                        <th style="text-align:center; padding: 8px;">Jumlah Harga</th>
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
                                        @endphp
                                        <tr style="border-bottom: 1px solid #f0f0f0;">
                                            <td style="text-align:center; padding: 8px;">{{ $OrderDtl->matl_code }}</td>
                                            <td style="padding: 8px;">{{ $OrderDtl->matl_descr }}</td>
                                            <td style="text-align:center; padding: 8px;">{{ ceil($OrderDtl->qty) }}</td>
                                            <td style="text-align:right; padding: 8px;">
                                                {{ rupiah(ceil($OrderDtl->price)) }}
                                            </td>
                                            <td style="text-align:right; padding: 8px;">
                                                {{ rupiah(ceil($subTotal)) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr style="border-top: 1px solid #ddd;">
                                        <th colspan="4" style="text-align:right; padding: 8px;">Total</th>
                                        <th style="text-align:right; padding: 8px;">
                                            {{ rupiah($grand_total) }}
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>

                            <!-- Info Pembayaran -->
                            <p style="margin-top: 10px;">
                                Pembayaran: <strong>{{ $this->object->payment_method ?? 'CASH' }}</strong>
                            </p>

                        </div> <!-- /.invoice-box -->
                    </div> <!-- /#print -->
                </div>
            </div>
        </div>
    </body>

    <!-- Script Print -->
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
