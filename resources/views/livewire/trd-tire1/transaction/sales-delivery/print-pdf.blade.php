<div>
    <div class="row d-flex align-items-baseline">
        <div class="col-xl-9">
            <x-ui-button clickEvent="" type="Back" button-name="Back" />
        </div>
        <div class="col-xl-3 float-end">
            <a class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark" onclick="printInvoice()">
                <i class="fas fa-print text-primary"></i> Print
            </a>
        </div>
        <hr>
    </div>

    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <!-- Card hanya tampil di layar, tidak saat print -->
    <div class="card d-print-none" style="max-width: 800px; margin: 30px auto; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 0px 1.5px rgba(0,0,0,0.03); border-radius: 10px; padding: 32px 32px 40px 32px;">
        <div class="invoice-box" style="margin: auto; padding: 20px;">
            <!-- Header -->
            <table width="100%" style="margin-bottom: 10px;">
                <tr>
                    <td style="width: 25%;">
                        <div style="text-align: center;">
                            <h2 style="margin: 0; text-decoration: underline; font-weight: bold; font-size: 22px;">CAHAYA TERANG</h2>
                            <p style="margin-top: -5px;">SURABAYA</p>
                        </div>
                    </td>
                    <td style="text-align: center; margin-top: 20px; vertical-align: bottom; width: 50%;">
                        <h3 style="margin-bottom: -5px; text-decoration: underline;">
                            SURAT JALAN</h3>
                        <p style="margin: 0px 0;">No. {{ $this->object->tr_code }}</p>
                    </td>
                    <td style="text-align: left; vertical-align: bottom; width: 30%;">
                        <p style="margin-bottom: -8px;">
                            Surabaya, {{ \Carbon\Carbon::parse($this->object->tr_date)->format('d-M-Y') }}
                        </p>
                        <p style="margin-bottom: -8px;">Kepada Yth :</p>
                        <p style="margin-bottom: -8px;">
                            <strong>{{ $this->object->Partner->name }}</strong>
                        </p>
                        <p style="margin-bottom: -8px;">{{ $this->object->Partner->address }}</p>
                    </td>
                </tr>
            </table>

                            <!-- Items Table (screen only, no page-break) -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000; line-height: 1.2;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid #000; text-align: center; width: 5%;">NO</th>
                            <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 15%;">KODE BARANG</th>
                            <th style="border: 1px solid #000; text-align: center; width: 25%;">KETERANGAN</th>
                            <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 5%;">QTY</th>
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
                            <tr style="line-height: 1.2;">
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 3px 5px 3px 5px;">
                                    {{ $counter++ }}
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 3px 5px 3px 5px;">
                                    {{ $OrderDtl->matl_code }}
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 3px 5px 3px 5px;">
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding: 3px 5px 3px 5px;">
                                    {{ ceil($OrderDtl->qty) }}
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 3px 5px 3px 5px;">
                                    {{ $OrderDtl->matl_descr }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            <table style="width: 100%; border-collapse: collapse; margin-top: -21px;">
                <tr>
                    <td style="text-align: right; padding-right: 10px; width: 5%;"></td>
                    <td style="text-align: right; padding-right: 10px; width: 15%;">TOTAL :</td>
                    <td style="border-top: 1px solid #000; border-left: 1px solid #000; border-bottom: 1px solid #000; text-align: right; padding-right: 10px; width: 25%;"></td>
                    <td style="border: 1px solid #000; text-align: right; width: 5%;">{{ $total_qty }}</td>
                    <td style="text-align: left; padding-left: 5px; width: auto;"></td>
                </tr>
            </table>

            <!-- Recipient Info -->
            <div style="margin-top: 30px;">
                <p style="margin: 0 0 10px 0;">
                    {{ $this->object->Partner->name }} -
                    {{ $this->object->Partner->address }} -
                    {{ $this->object->Partner->city }}
                </p>

                <div width="100%" style="margin-top: -10px;">
                    <div class="row justify-content-between" style="text-align: center;">
                        <div style="width: 25%;">
                            <p>Administrasi:</p><br>
                            <p>(________________)</p>
                        </div>
                        <div style="width: 25%;">
                            <p>Gudang:</p><br>
                            <p>(________________)</p>
                        </div>
                        <div style="width: 25%;">
                            <p>Driver:</p><br>
                            <p>(________________)</p>
                        </div>
                        <div style="width: 25%;">
                            <p>Penerima:</p><br>
                            <p>(________________)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Area print tetap tampil saat print -->
    <div id="print" class="d-none d-print-block p-20">
        <div style="margin: 0 auto; font-family: 'Calibri'; font-size: 14px;">
            <div class="invoice-box" style="margin: auto; padding: 20px;">
                @php
                    $counter = 1;
                    $chunks = $this->object->OrderDtl->chunk(10);
                @endphp
                @foreach ($chunks as $chunkIndex => $chunk)
                    <!-- Header per page -->
                    <table width="100%" style="margin-bottom: 10px;">
                        <tr>
                            <td style="width: 25%;">
                                <div style="text-align: center;">
                                    <h2 style="margin: 0; text-decoration: underline; font-weight: bold; font-size: 22px;">CAHAYA TERANG</h2>
                                    <p style="margin-top: -5px;">SURABAYA</p>
                                </div>
                            </td>
                            <td style="text-align: center; margin-top: 20px; vertical-align: bottom; width: 50%;">
                                <h3 style="margin-bottom: -5px; text-decoration: underline;">SURAT JALAN</h3>
                                <p style="margin: 0px 0;">No. {{ $this->object->tr_code }}</p>
                            </td>
                            <td style="text-align: left; vertical-align: bottom; width: 30%;">
                                <p style="margin-bottom: -8px;">Surabaya, {{ \Carbon\Carbon::parse($this->object->tr_date)->format('d-M-Y') }}</p>
                                <p style="margin-bottom: -8px;">Kepada Yth :</p>
                                <p style="margin-bottom: -8px;"><strong>{{ $this->object->Partner->name }}</strong></p>
                                <p style="margin-bottom: -8px;">{{ $this->object->Partner->address }}</p>
                            </td>
                        </tr>
                    </table>

                    <!-- Items (10 per page) -->
                    @php $page_total_qty = 0; @endphp
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000; line-height: 1.2;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid #000; text-align: center; width: 5%;">NO</th>
                                <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 15%;">KODE BARANG</th>
                                <th style="border: 1px solid #000; text-align: center; width: 25%;">KETERANGAN</th>
                                <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 5%;">QTY</th>
                                <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: auto;">NAMA BARANG</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($chunk as $OrderDtl)
                                @php $page_total_qty += $OrderDtl->qty; @endphp
                                <tr style="line-height: 1.2;">
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 3px 5px 3px 5px;">{{ $counter++ }}</td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 3px 5px 3px 5px;">{{ $OrderDtl->matl_code }}</td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 3px 5px 3px 5px;"></td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding: 3px 5px 3px 5px;">{{ ceil($OrderDtl->qty) }}</td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 3px 5px 3px 5px;">{{ $OrderDtl->matl_descr }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Total per page -->
                    <table style="width: 100%; border-collapse: collapse; margin-top: -21px;">
                        <tr>
                            <td style="text-align: right; padding-right: 10px; width: 5%;"></td>
                            <td style="text-align: right; padding-right: 10px; width: 15.1%;">TOTAL :</td>
                            <td style="border-top: 1px solid #000; border-left: 1px solid #000; border-bottom: 1px solid #000; text-align: right; padding-right: 10px; width: 24.9%;"></td>
                            <td style="border: 1px solid #000; text-align: right; width: 5%; padding-right: 5px;">{{ $page_total_qty }}</td>
                            <td style="text-align: left; padding-left: 5px; width: auto;"></td>
                        </tr>
                    </table>

                    <!-- Footer per page -->
                    <div style="margin-top: 30px;">
                        <p style="margin: 0 0 10px 0;">{{ $this->object->Partner->name }} - {{ $this->object->Partner->address }} - {{ $this->object->Partner->city }}</p>
                        <div width="100%" style="margin-top: -10px;">
                            <div class="row justify-content-between" style="text-align: center;">
                                <div style="width: 25%;"><p>Administrasi:</p><br><p>(________________)</p></div>
                                <div style="width: 25%;"><p>Gudang:</p><br><p>(________________)</p></div>
                                <div style="width: 25%;"><p>Driver:</p><br><p>(________________)</p></div>
                                <div style="width: 25%;"><p>Penerima:</p><br><p>(________________)</p></div>
                            </div>
                        </div>
                    </div>

                    @if ($chunkIndex < $chunks->count() - 1)
                        <div style="page-break-after: always;"></div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <script>
        function printInvoice() {
            @this.updateDeliveryPrintCounter();
            setTimeout(function() {
                window.print();
            }, 1000);
        }
    </script>
</div>
