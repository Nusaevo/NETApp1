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
                            style="max-width: 800px; margin: auto; padding: 20px;">
                            <!-- Header -->
                            <table width="100%" style="margin-bottom: 10px;">
                                <tr>
                                    <td style="width: 25%;">
                                        <div style="text-align: center;">
                                            <h2 style="margin: 0; text-decoration: underline; font-weight: bold; font-size: 22px;">CAHAYA
                                                TERANG</h2>
                                            <p style="margin-top: -5px;">SURABAYA</p>
                                        </div>
                                    </td>
                                    <td
                                        style="text-align: center; margin-top: 20px; vertical-align: bottom; width: 50%;">
                                        <h3 style="margin-bottom: -5px; text-decoration: underline;">
                                            SURAT JALAN</h3>
                                        <p style="margin: 0px 0;">No. {{ $this->object->tr_code }}</p>
                                    </td>
                                    <td style="text-align: left; vertical-align: bottom; width: 30%;">
                                        <p style="margin-bottom: -8px;">
                                            Surabaya,
                                            {{ \Carbon\Carbon::parse($this->object->tr_date)->format('d-M-Y') }}
                                        </p>
                                        <p style="margin-bottom: -8px;">Kepada Yth :</p>
                                        <p style="margin-bottom: -8px;">
                                            <strong>{{ $this->object->Partner->name }}</strong>
                                        </p>
                                        <p style="margin-bottom: -8px;">{{ $this->object->Partner->address }}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Items Table -->
                            <table
                                style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000;">
                                <thead>
                                    <tr>
                                        <th style="border: 1px solid #000; text-align: center; width: 5%;">NO</th>
                                        <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 20%;">KODE BARANG</th>
                                        <th style="border: 1px solid #000; text-align: center; width: 25%;">KETERANGAN</th>
                                        <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 15%;">QTY</th>
                                        <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: auto;">NAMA BARANG</th>
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
                                            <td
                                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center;">
                                                {{ $counter++ }}
                                            </td>
                                            <td
                                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left;">
                                                {{ $OrderDtl->matl_code }}</td>
                                            <td
                                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center;">
                                            </td>
                                            <td
                                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 10px;">
                                                {{ ceil($OrderDtl->qty) }}</td>
                                            <td
                                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left;">
                                                {{ $OrderDtl->matl_descr }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <table style="width: 100%; margin-top: -21px;">
                                <tr>
                                    <td style="text-align: right; padding-right: 10px; width: 50%;">TOTAL :</td>
                                    <td style="border: 1px solid #000; text-align: right; padding-right: 10px; width: 15%;">{{ $total_qty }}
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </table>

                            <!-- Recipient Info -->
                            <div style="margin-top: 70px;">
                                <p style="margin: 0 0 10px 0;">
                                    {{ $this->object->Partner->name }} -
                                    {{ $this->object->Partner->address }} -
                                    {{ $this->object->Partner->city }}
                                </p>

                                <div width="100%" style="margin-top: -10px;">
                                    <div class="row justify-content-between" style="text-align: center;">
                                        <div style="width: 25%;">
                                            <p>Administrasi:</p><br><br>
                                            <p>(________________)</p>
                                        </div>
                                        <div style="width: 25%;">
                                            <p>Gudang:</p><br><br>
                                            <p>(________________)</p>
                                        </div>
                                        <div style="width: 25%;">
                                            <p>Driver:</p><br><br>
                                            <p>(________________)</p>
                                        </div>
                                        <div style="width: 25%;">
                                            <p>Penerima:</p><br><br>
                                            <p>(________________)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>

    <script>
        function printInvoice() {
            @this.updateDeliveryPrintCounter();
            setTimeout(function() {
                window.print();
            }, 1000);
        }
    </script>
</div>
