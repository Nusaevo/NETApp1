<div>
    <div>
        <div>
            <x-ui-button clickEvent="" type="Back" button-name="Back" class="no-print" />
        </div>
    </div>

    {{-- Link CSS utama --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    {{-- Aturan CSS khusus print --}}
    <style>
        @media print {
            /* Sembunyikan tombol dan elemen tidak perlu saat print */
            .no-print, .btn, .navbar, .footer {
                display: none !important;
            }

            /* Hilangkan background & bayangan untuk hasil print bersih */
            body {
                background: white !important;
                color: black !important;
            }

            .invoice-box {
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>

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
                    <div class="col-xl-3 float-end no-print">
                        <a class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark"
                           onclick="printInvoice()">
                            <i class="fas fa-print text-primary"></i> Print
                        </a>
                    </div>
                    <hr>
                </div>

                {{-- Konten yang akan diprint --}}
                <div id="print">
                    <div class="invoice-box" style="max-width: 800px; margin: auto; padding: 20px; solid #000;">
                        <!-- Header -->
                        <table width="100%" style="margin-bottom: 10px;">
                            <tr>
                                <td style="width: 20%;">
                                    <div style="text-align: center;">
                                        <h2 style="margin: 0; text-decoration: underline; font-weight: bold;">CAHAYA TERANG</h2>
                                        <p style="margin-top: -5px;">SURABAYA</p>
                                    </div>
                                </td>
                                <td style="text-align: center; margin-top: 20px; vertical-align: bottom; width: 50%;">
                                    <h3 style="margin-bottom: -5px; text-decoration: underline;">
                                        NOTA PENJUALAN</h3>
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
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid #000; text-align: center;">KODE BARANG</th>
                                    <th style="border: 1px solid #000; text-align: center;">NAMA BARANG</th>
                                    <th style="border: 1px solid #000; text-align: center;">QTY</th>
                                    <th style="border: 1px solid #000; text-align: center;">HARGA SATUAN</th>
                                    <th style="border: 1px solid #000; text-align: center;">JUMLAH HARGA</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $grand_total = 0;
                                @endphp
                                @foreach ($this->object->OrderDtl as $OrderDtl)
                                    @php
                                        $subTotal = $OrderDtl->qty * $OrderDtl->price;
                                        $grand_total += $subTotal;
                                    @endphp
                                    <tr>
                                        <td style="border: 1px solid #000; text-align: center;">
                                            {{ $OrderDtl->matl_code }}
                                        </td>
                                        <td style="border: 1px solid #000; text-align: left;">
                                            {{ $OrderDtl->matl_descr }}
                                        </td>
                                        <td style="border: 1px solid #000; text-align: center;">
                                            {{ ceil($OrderDtl->qty) }}
                                        </td>
                                        <td style="border: 1px solid #000; text-align: right;">
                                            {{ number_format(ceil($OrderDtl->price), 0, ',', '.') }}
                                        </td>
                                        <td style="border: 1px solid #000; text-align: right;">
                                            {{ number_format(ceil($subTotal), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Footer -->
                        <table style="margin-top: -18px; width: 100%;">
                            <tr>
                                <td colspan="3" style="text-align: start; border: 1px solid #000;">
                                    <p style="margin: 0;">Penerima:</p>
                                </td>
                                <td colspan="3" style="text-align: end; border: 1px solid #000;">
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
</div>

{{-- Script Print --}}
<script>
function printInvoice() {
    @this.updatePrintCounter();
    setTimeout(() => {
        window.print();
    }, 300); // beri jeda supaya update selesai dulu
}
</script>
