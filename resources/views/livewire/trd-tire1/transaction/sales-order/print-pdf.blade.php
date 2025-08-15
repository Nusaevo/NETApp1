<div>
    <div>
        <div>
            <x-ui-button clickEvent="" type="Back" button-name="Back" />
        </div>
    </div>

    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <style>
        /* Print-specific styles to maintain layout and styling */
        @media print {
            @page {
                size: landscape;
                margin: 10mm;
            }

            /* Hide browser print headers and footers */
            @page {
                margin-top: 0;
                margin-bottom: 0;
                margin-left: 0;
                margin-right: 0;
            }

            body {
                margin: 0;
                padding: 0;
                background: white !important;
                color: black !important;
            }

            .card, .card-body {
                border: none !important;
                box-shadow: none !important;
                background: white !important;
            }

            .container {
                max-width: none !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .row {
                margin: 0 !important;
            }

            .col-xl-9, .col-xl-3 {
                flex: 0 0 auto !important;
                width: auto !important;
                padding: 0 !important;
            }

            .btn {
                display: none !important;
            }

            hr {
                border: none !important;
                margin: 10px 0 !important;
            }

            #print {
                display: block !important;
            }

            .invoice-box {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 10px !important;
                border: none !important;
            }

            table {
                page-break-inside: avoid !important;
            }

            tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }

            /* Ensure text remains visible */
            h2, h3, p, td, th {
                color: black !important;
                background: white !important;
            }

            /* Maintain borders for tables */
            th, td {
                border: 1px solid black !important;
            }

            /* Remove borders from header table */
            table:first-of-type {
                border: none !important;
            }

            table:first-of-type tr,
            table:first-of-type td {
                border: none !important;
            }

            /* Hide print button and other UI elements */
            .btn-light, .fas, [onclick*="print"] {
                display: none !important;
            }

            /* Ensure proper spacing */
            .mb-5, .mt-3 {
                margin: 0 !important;
            }

            /* Force background colors to print */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            /* Hide browser print headers and footers */
            @media print {
                html, body {
                    height: 100%;
                    margin: 0 !important;
                    padding: 0 !important;
                }
            }

            /* Ensure company name stays in one line */
            h2[style*="CAHAYA TERANG"],
            h2 {
                white-space: nowrap !important;
                word-wrap: normal !important;
                overflow: visible !important;
            }

            /* Ensure location stays in one line */
            p[style*="SURABAYA"],
            p {
                white-space: nowrap !important;
                word-wrap: normal !important;
                overflow: visible !important;
            }
        }

        /* Screen styles to maintain current appearance */
        @media screen {
            .invoice-box {
                border: 1px solid #ddd;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        }
    </style>

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
                        <div class="invoice-box" style="max-width: 1200px; margin: auto; padding: 20px;">
                            <!-- Header -->
                            <table width="100%" style="margin-bottom: 10px; border: none;">
                                <tr style="border: none;">
                                    <td style="width: 25%; border: none;">
                                        <div style="text-align: center;">
                                            <h2 style="margin: 0; text-decoration: underline; font-weight: bold; white-space: nowrap;">CAHAYA TERANG</h2>
                                            <p style="margin-top: -5px; white-space: nowrap;">SURABAYA</p>
                                        </div>
                                    </td>
                                    <td
                                        style="text-align: center; margin-top: 20px; vertical-align: bottom; width: 45%; border: none;">
                                        <h3 style="margin-bottom: -5px; text-decoration: underline;">
                                            NOTA
                                            PENJUALAN</h3>
                                        <p style="margin: 0px 0;">No. {{ $this->object->tr_code }}</p>
                                    </td>
                                    <td style="text-align: left; vertical-align: bottom; width: 30%; border: none;">
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
                                                {{-- @if ($loop->last)
                                                    <p style="margin-top: 5px; margin-bottom: -5px">Penerima:
                                                        ________________
                                                    </p>
                                                @endif --}}
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
                            <table style="margin-top: -18px; width: 100%;">
                                <tr>
                                    <td style="text-align: start; border: 1px solid #000; padding: 10px;">
                                        <p style="margin: 0; display: inline;">Penerima: ________________</p>
                                        <p style="margin: 0; text-align: end; display: inline; float: right;">Pembayaran: <strong>{{ $this->object->payment_method ?? 'CASH' }}</strong></p>
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
            @this.updatePrintCounter();
            setTimeout(function() {
                window.print();
            }, 1000);
        }
    </script>
</div>
