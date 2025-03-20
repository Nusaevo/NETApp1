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
                                SURAT JALAN >>
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
                                    <td style="width: 30%;">
                                        <div style="text-align: center;">
                                            <h2 style="margin: 0; text-decoration: underline; font-weight: bold;">CAHAYA TERANG</h2>
                                            <p style="margin: 0;">SURABAYA</p>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <p style="margin: 0;">
                                            Surabaya,
                                            {{ \Carbon\Carbon::parse($this->object->tr_date)->format('d-M-Y') }}
                                        </p>
                                        <p style="margin: 0;">Kepada Yth :</p>
                                        <p style="margin: 0;"><strong>{{ $this->object->Partner->name }}</strong></p>
                                        <p style="margin: 0;">{{ $this->object->Partner->address }}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Title -->
                            <div style="text-align: center; margin: 20px 0;">
                                <h3 style="margin: 0; font-weight: bold; text-decoration: underline;">SURAT JALAN</h3>
                                <p style="margin: 5px 0;">No. {{ $this->object->tr_code }}</p>
                            </div>

                            <!-- Items Table -->
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                <thead>
                                    <tr>
                                        <th style="border: 1px solid #000; padding: 8px; width: 5%;">NO</th>
                                        <th style="border: 1px solid #000; padding: 8px;">KODE BARANG</th>
                                        <th style="border: 1px solid #000; padding: 8px;">KETERANGAN</th>
                                        <th style="border: 1px solid #000; padding: 8px;">QTY</th>
                                        <th style="border: 1px solid #000; padding: 8px;">NAMA BARANG</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $total_qty = 0;
                                        $counter = 1;
                                    @endphp
                                    @foreach ($this->object->OrderDtl as $OrderDtl)
                                        @php
                                            $total_qty += $OrderDtl->qty;
                                        @endphp
                                        <tr>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: center;">{{ $counter++ }}</td>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: left;">{{ $OrderDtl->matl_code }}</td>
                                            <td style="border: 1px solid #000; padding: 8px;"></td>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: center;">{{ ceil($OrderDtl->qty) }}</td>
                                            <td style="border: 1px solid #000; padding: 8px;">{{ $OrderDtl->matl_descr }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="3" style="border: 1px solid #000; padding: 8px; text-align: right;"><strong>TOTAL :</strong></td>
                                        <td style="border: 1px solid #000; padding: 8px; text-align: center;">{{ $total_qty }}</td>
                                        <td style="border: 1px solid #000; padding: 8px;"></td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Recipient Info -->
                            <div style="margin: 20px 0;">
                                <p style="margin: 0 0 10px 0;">
                                    {{ $this->object->Partner->name }} -
                                    {{ $this->object->Partner->address }} -
                                    {{ $this->object->Partner->city }}
                                </p>

                                <table width="100%" style="margin-top: 30px; text-align: center;">
                                    <tr>
                                        <td>Administrasi:</td>
                                        <td>Gudang:</td>
                                        <td>Driver:</td>
                                        <td>Penerima:</td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top: 20px;">(__________)</td>
                                        <td style="padding-top: 20px;">(__________)</td>
                                        <td style="padding-top: 20px;">(__________)</td>
                                        <td style="padding-top: 20px;">(__________)</td>
                                    </tr>
                                </table>
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
                '<link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}">' +
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
