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
    <div class="card d-print-none" style="max-width: 800px; margin: 15px auto; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 0px 1.5px rgba(0,0,0,0.03); border-radius: 10px; padding: 20px 20px 25px 20px;">
        <div class="invoice-box" style="margin: auto; padding: 10px;">
            <!-- Header -->
            <table width="100%" style="margin-bottom: 5px;">
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
                        <p style="margin-bottom: -8px;">{{ $this->object->Partner->city }}</p>
                    </td>
                </tr>
            </table>

                            <!-- Items Table (screen only, no page-break) -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1px solid #000; line-height: 1.1;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid #000; text-align: center; width: 5%; font-size: 12px;">NO</th>
                            <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 15%; font-size: 12px;">KODE BARANG</th>
                            <th style="border: 1px solid #000; text-align: center; width: 25%; font-size: 12px;">KETERANGAN</th>
                            <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 5%; font-size: 12px;">QTY</th>
                            <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: auto; font-size: 12px;">NAMA BARANG</th>
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
                            <tr style="line-height: 1.1;">
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 2px 3px; font-size: 12px;">
                                    {{ $counter++ }}
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 2px 5px; font-size: 12px;">
                                    {{ $OrderDtl->matl_code }}
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 2px 3px; font-size: 12px;">
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding: 2px 5px; font-size: 12px;">
                                    {{ ceil($OrderDtl->qty) }}
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 2px 3px; font-size: 12px;">
                                    {{ $OrderDtl->matl_descr }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            <table style="width: 100%; margin-top: -11px;">
                <tr style="line-height: 1.1;">
                    <td style="text-align: right; padding: 2px 3px; width: 5%; font-size: 12px;"></td>
                    <td style="text-align: right; padding: 2px 3px; width: 15%; font-size: 12px;">TOTAL :</td>
                    <td style="border-top: 1px solid #000; border-left: 1px solid #000; border-bottom: 1px solid #000; text-align: right; padding: 2px 3px; width: 25%; font-size: 12px;"></td>
                    <td style="border: 1px solid #000; text-align: right; padding: 2px 5px; width: 5%; font-size: 12px;">{{ $total_qty }}</td>
                    <td style="text-align: left; padding: 2px 3px; width: auto; font-size: 12px;"></td>
                </tr>
            </table>

            <!-- Recipient Info -->
            <div style="margin-top: 15px;">
                <p style="margin: 0 0 10px 0;">
                    {{ $this->object->Partner->name }} -
                    {{ $this->object->Partner->address }} -
                    {{ $this->object->Partner->city }}
                </p>

                <div width="100%" style="margin-top: 5px;">
                    <div class="row justify-content-between" style="text-align: center;">
                        <div style="width: 25%;">
                            <p style="margin: 5px 0;">Administrasi:</p>
                            <p style="margin: 5px 0;">(________________)</p>
                        </div>
                        <div style="width: 25%;">
                            <p style="margin: 5px 0;">Gudang:</p>
                            <p style="margin: 5px 0;">(________________)</p>
                        </div>
                        <div style="width: 25%;">
                            <p style="margin: 5px 0;">Driver:</p>
                            <p style="margin: 5px 0;">(________________)</p>
                        </div>
                        <div style="width: 25%;">
                            <p style="margin: 5px 0;">Penerima:</p>
                            <p style="margin: 5px 0;">(________________)</p>
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
                @endphp
                @foreach ([$this->object->OrderDtl] as $chunkIndex => $chunk)
                    <!-- Header per page -->
                    <table width="100%" style="margin-bottom: 5px;">
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
                                <p style="margin-bottom: -8px;">{{ $this->object->Partner->city }}</p>
                            </td>
                        </tr>
                    </table>

                    <!-- Items -->
                    @php $page_total_qty = 0; @endphp
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1px solid #000; line-height: 1.1;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid #000; text-align: center; width: 5%; font-size: 12px;">NO</th>
                                <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 15%; font-size: 12px;">KODE BARANG</th>
                                <th style="border: 1px solid #000; text-align: center; width: 25%; font-size: 12px;">KETERANGAN</th>
                                <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 5%; font-size: 12px;">QTY</th>
                                <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: auto; font-size: 12px;">NAMA BARANG</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($chunk as $OrderDtl)
                                @php $page_total_qty += $OrderDtl->qty; @endphp
                                <tr style="line-height: 1.1;">
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 2px 3px; font-size: 12px;">{{ $counter++ }}</td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 2px 5px; font-size: 12px;">{{ $OrderDtl->matl_code }}</td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 2px 3px; font-size: 12px;"></td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding: 2px 5px; font-size: 12px;">{{ ceil($OrderDtl->qty) }}</td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 2px 3px; font-size: 12px;">{{ $OrderDtl->matl_descr }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Total -->
                    <table style="width: 100%; margin-top: -11px;">
                        <tr style="line-height: 1.1;">
                            <td style="text-align: right; padding: 2px 3px; width: 5%; font-size: 12px;"></td>
                            <td style="text-align: right; padding: 2px 3px; width: 15%; font-size: 12px;">TOTAL :</td>
                            <td style="border-top: 1px solid #000; border-left: 1px solid #000; border-bottom: 1px solid #000; text-align: right; padding: 2px 3px; width: 25%; font-size: 12px;"></td>
                            <td style="border: 1px solid #000; text-align: right; padding: 2px 5px; width: 5%; font-size: 12px;">{{ $total_qty }}</td>
                            <td style="text-align: left; padding: 2px 3px; width: auto; font-size: 12px;"></td>
                        </tr>
                    </table>

                    <!-- Footer -->
                    <div style="margin-top: 15px;">
                        <p style="margin: 0 0 5px 0;">{{ $this->object->Partner->name }} - {{ $this->object->Partner->address }} - {{ $this->object->Partner->city }}</p>
                        <div width="100%" style="margin-top: 5px;">
                            <div class="row justify-content-between" style="text-align: center;">
                                <div style="width: 25%;"><p style="margin: 5px 0;">Administrasi:</p><p style="margin: 5px 0;">(________________)</p></div>
                                <div style="width: 25%;"><p style="margin: 5px 0;">Gudang:</p><p style="margin: 5px 0;">(________________)</p></div>
                                <div style="width: 25%;"><p style="margin: 5px 0;">Driver:</p><p style="margin: 5px 0;">(________________)</p></div>
                                <div style="width: 25%;"><p style="margin: 5px 0;">Penerima:</p><p style="margin: 5px 0;">(________________)</p></div>
                            </div>
                        </div>
                    </div>
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
