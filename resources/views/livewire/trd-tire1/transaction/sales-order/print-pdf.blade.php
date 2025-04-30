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
                        <div class="invoice-box" style="max-width: 800px; margin: auto; padding: 20px;  solid #000;">
                            <!-- Header -->
                            <table width="100%" style="margin-bottom: 10px;">
                                <tr>
                                    <td style="width: 20%;">
                                        <div style="text-align: center;">
                                            <h2 style="margin: 0; text-decoration: underline; font-weight: bold;">CAHAYA
                                                TERANG</h2>
                                            <p style="margin-top: -5px;">SURABAYA</p>
                                        </div>
                                    </td>
                                    <td
                                        style="text-align: center; margin-top: 20px; vertical-align: bottom; width: 50%;">
                                        <h3 style="margin-bottom: -5px; font-weight: bold; text-decoration: underline;">
                                            NOTA
                                            PENJUALAN</h3>
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
                                        <th style="border: 1px solid #000; text-align: center;">KODE
                                            BARANG</th>
                                        <th style="border: 1px solid #000; text-align: center;">NAMA
                                            BARANG</th>
                                        <th style="border: 1px solid #000; text-align: center;">QTY</th>
                                        <th style="border: 1px solid #000; text-align: center;">HARGA
                                            SATUAN</th>
                                        <th style="border: 1px solid #000; text-align: center;">JUMLAH
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
                                        @endphp
                                        <tr>
                                            <td
                                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center;">
                                                {{ $OrderDtl->matl_code }}
                                            </td>
                                            <td
                                                style="text-align: left; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;">
                                                {{ $OrderDtl->matl_descr }}
                                                @if ($loop->last)
                                                    <p style="margin-top: 5px; margin-bottom: -5px">Penerima:
                                                        ________________
                                                    </p>
                                                @endif
                                            </td>
                                            <td style="text-align: center;">
                                                {{ ceil($OrderDtl->qty) }}</td>
                                            <td
                                                style="text-align: right; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;">
                                                {{ number_format(ceil($OrderDtl->price), 0, ',', '.') }}
                                                @if ($loop->last)
                                                    <p
                                                        style="border: 1px solid #000000; text-align: right; font-weight: bold; margin-left: -6px; margin-right: -6px; margin-top: 10px; margin-bottom: -8px; padding: 2px;">
                                                        TOTAL:
                                                    </p>
                                                @endif
                                            </td>
                                            <td
                                                style="text-align: right; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;">
                                                {{ number_format(ceil($subTotal), 0, ',', '.') }}
                                                @if ($loop->last)
                                                    <p
                                                        style="border: 1px solid #000; text-align: right; font-weight: bold; margin-left: -5.7px; margin-right: -6px; margin-top: 10px; margin-bottom: -6px; padding: 2px;">
                                                        {{ number_format($grand_total, 0, ',', '.') }}
                                                    </p>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <table style="margin-top: -19px; width: 60.9%;">
                                <tr>
                                    <td colspan="3" style="text-align: center; border: 1px solid #000;">
                                        <p style="margin: 0;">Pembayaran:
                                            <strong>{{ $this->object->payment_method ?? 'CASH' }}</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    <script>
        function printInvoice() {
            window.print();
        }
    </script>
</div>
