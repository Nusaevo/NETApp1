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

        {{-- <style>
            @page {
                size: A5 portrait;
                margin: 10mm;
            }

            @media print {
                .d-print-none {
                    display: none !important;
                }

                body {
                    margin: 0;
                    padding: 0;
                    font-family: 'Calibri', Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.2;
                }

                #print {
                    width: 100%;
                    max-width: none;
                    margin: 0;
                    padding: 0;
                }

                .invoice-box {
                    max-width: none;
                    width: 100%;
                    margin: 0;
                    padding: 5mm;
                    box-sizing: border-box;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 11px;
                }

                th, td {
                    padding: 2px 3px;
                    border: 1px solid #000;
                    font-size: 10px;
                }

                h2, h3 {
                    margin: 2px 0;
                    font-size: 14px;
                }

                p {
                    margin: 1px 0;
                    font-size: 10px;
                }
            }
        </style> --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <!-- Card hanya tampil di layar, tidak saat print -->
    <div class="card d-print-none" style="max-width: 1200px; margin: 30px auto; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 0px 1.5px rgba(0,0,0,0.03); border-radius: 10px; padding: 32px 32px 40px 32px;">
        <div class="invoice-box" style="max-width: 1200px; margin: auto; padding: 20px;">
            <!-- Header -->
            <table width="100%" style="margin-bottom: 10px; border: none;">
                <tr style="border: none;">
                    <td style="width: 25%; border: none;">
                        <div style="text-align: center;">
                            <h2 style="margin: 0; text-decoration: underline; font-weight: bold; white-space: nowrap;">
                                CAHAYA TERANG</h2>
                            <p style="margin-top: -5px; white-space: nowrap;">SURABAYA</p>
                        </div>
                    </td>
                    <td style="text-align: center; margin-top: 20px; vertical-align: bottom; width: 45%; border: none;">
                        <h3 style="margin-bottom: -5px; text-decoration: underline;">
                            NOTA PENJUALAN</h3>
                        <p style="margin: 0px 0;">No. {{ $this->object->tr_code }}</p>
                    </td>
                    <td style="text-align: left; vertical-align: bottom; width: 30%; border: none;">
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

            <!-- Items Table -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000; line-height: 1.2;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 12%;">KODE BARANG</th>
                        <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 35%;">NAMA BARANG</th>
                        <th style="border: 1px solid #000; text-align: center; width: 5%;">QTY</th>
                        <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 12%;">HARGA SATUAN</th>
                        @if($this->object->sales_type != 'O')
                            <th style="border: 1px solid #000; text-align: center; padding-right: 5px; width: 5%;">DISC</th>
                        @endif
                        <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 15%;">JUMLAH HARGA</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grand_total = 0;
                    @endphp
                    @foreach ($this->object->OrderDtl as $key => $OrderDtl)
                        @php
                            $discount = $OrderDtl->disc_pct / 100;
                            $priceAfterDisc = round($OrderDtl->price * (1 - $discount));
                            $subTotalAfterDisc = $priceAfterDisc * $OrderDtl->qty;
                            $grand_total += $subTotalAfterDisc;
                        @endphp
                        <tr style="line-height: 1.2;">
                            <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 3px 5px 3px 5px;">
                                {{ $OrderDtl->matl_code }}
                            </td>
                            <td style="text-align: left; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px;">
                                {{ $OrderDtl->matl_descr }}
                            </td>
                            <td style="text-align: center; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px;">
                                {{ ceil($OrderDtl->qty) }}
                            </td>
                            <td style="text-align: right; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px;">
                                @php
                                    $discount = $OrderDtl->disc_pct / 100;
                                    $priceAfterDisc = round($OrderDtl->price * (1 - $discount));
                                @endphp
                                {{ number_format(ceil($priceAfterDisc), 0, ',', '.') }}
                                @if ($loop->last)
                                    <div style="border-top: 1px solid #000; text-align: right; padding-top: 5px; margin-top: 6px; margin-left: -5px; margin-right: -5px; padding-right: 5px; min-height: 20px;">
                                        <!-- Biaya kirim -->
                                        Total
                                    </div>
                                @endif
                                @if ($loop->last && $this->object->amt_shipcost > 0)
                                    <div style="border-top: 1px solid #000; text-align: right; padding-top: 5px; margin-top: 6px; margin-left: -5px; margin-right: -5px; padding-right: 5px; min-height: 20px;">
                                        <!-- Biaya kirim -->
                                        Biaya EX
                                    </div>
                                @endif
                                @if ($loop->last && $this->object->amt_shipcost > 0)
                                    <div style="border-top: 1px solid #000; text-align: right; padding-top: 5px; margin-top: 6px; margin-left: -5px; margin-right: -5px; padding-right: 5px; min-height: 20px;">
                                        Grand Total
                                    </div>
                                @endif
                            </td>
                            @if($this->object->sales_type != 'O')
                                <td style="text-align: center; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px;">
                                    {{ number_format($OrderDtl->disc_pct, 0, ',', '.') }}%
                                </td>
                            @endif
                            <td style="text-align: right; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px;">
                                {{ number_format($subTotalAfterDisc, 0, ',', '.') }}
                                @if ($loop->last)
                                    <div style="border-top: 1px solid #000; text-align: right; font-weight: bold; padding-top: 5px; margin-top: 6px; margin-left: -5px; margin-right: -5px; padding-right: 5px;">
                                        {{ number_format($grand_total, 0, ',', '.') }}
                                    </div>
                                    @if($this->object->amt_shipcost > 0)
                                        <div style="border-top: 1px solid #000; text-align: right; padding-top: 5px; margin-top: 6px; margin-left: -5px; margin-right: -5px; padding-right: 5px; min-height: 20px;">
                                            <!-- Isian biaya plus -->
                                            {{ number_format($this->object->amt_shipcost, 0, ',', '.') }}
                                        </div>
                                    @endif
                                    @if($this->object->amt_shipcost > 0)
                                        <div style="border-top: 1px solid #000; text-align: right; font-weight: bold; padding-top: 5px; margin-top: 6px; margin-left: -5px; margin-right: -5px; padding-right: 5px; min-height: 20px;">
                                            <!-- Total akhir -->
                                            {{ number_format($grand_total + $this->object->amt_shipcost, 0, ',', '.') }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <table style="margin-top: -18px; width: 100%;">
                <tr>
                    <td style="border: 1px solid #000; padding: 10px;">
                        <p style="margin: 0; display: inline;">Penerima: ________________</p>
                        <p style="margin: 0; text-align: end; display: inline; float: right;">
                            Pembayaran: <strong>{{ $this->object->payment_method ?? 'CASH' }}</strong>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Area print tetap tampil saat print -->
    <div id="print" class="d-none d-print-block p-20">
        <div style="max-width: 1200px; margin: 0 auto; font-family: 'Calibri'; font-size: 14px;">
            <div class="invoice-box" style="max-width: 1200px; margin: auto; padding: 20px;">
                @php
                    $grand_total_all = $this->object->OrderDtl->reduce(function($carry, $d){
                        $disc = $d->disc_pct / 100;
                        $price = round($d->price * (1 - $disc));
                        return $carry + ($price * $d->qty);
                    }, 0);
                @endphp
                @foreach ([$this->object->OrderDtl] as $chunkIndex => $chunk)
                    <!-- Header per page -->
                    <table width="100%" style="margin-bottom: 10px; border: none;">
                        <tr style="border: none;">
                            <td style="width: 25%; border: none;">
                                <div style="text-align: center;">
                                    <h2 style="margin: 0; text-decoration: underline; font-weight: bold; white-space: nowrap;">CAHAYA TERANG</h2>
                                    <p style="margin-top: -2px; white-space: nowrap;">SURABAYA</p>
                                </div>
                            </td>
                            <td style="text-align: center; margin-top: 20px; vertical-align: bottom; width: 45%; border: none;">
                                <h3 style="margin-bottom: 1px; text-decoration: underline;">NOTA PENJUALAN</h3>
                                <p style="margin: 0px 0;">No. {{ $this->object->tr_code }}</p>
                            </td>
                        <td style="text-align: left; width: 30%; border: none; padding: 0 2px;">
                            <p style="margin: -5px 0;">Surabaya, {{ \Carbon\Carbon::parse($this->object->tr_date)->format('d-M-Y') }}</p>
                            <p style="margin: -5px 0;">Kepada Yth :</p>
                            <p style="margin: -5px 0;"><strong>{{ $this->object->Partner->name }}</strong></p>
                            <p style="margin: -5px 0;">{{ $this->object->Partner->address }}</p>
                            <p style="margin: -5px 0;">{{ $this->object->Partner->city }}</p>
                            </td>
                        </tr>
                    </table>

                    <!-- Items Table -->
                    @php $has_disc = $this->object->sales_type != 'O'; @endphp
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000; line-height: 1.2;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 12%;">KODE BARANG</th>
                                <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 35%;">NAMA BARANG</th>
                                <th style="border: 1px solid #000; text-align: center; width: 5%;">QTY</th>
                                <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 13%;">HARGA SATUAN</th>
                                @if($this->object->sales_type != 'O')
                                    <th style="border: 1px solid #000; text-align: center; padding-right: 5px; width: 5%;">DISC</th>
                                @endif
                                <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 15%;">JUMLAH HARGA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($chunk as $key => $OrderDtl)
                                @php
                                    $discount = $OrderDtl->disc_pct / 100;
                                    $priceAfterDisc = round($OrderDtl->price * (1 - $discount));
                                    $subTotalAfterDisc = $priceAfterDisc * $OrderDtl->qty;
                                @endphp
                                <tr style="line-height: 1.2;">
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 3px 5px 3px 5px;">{{ $OrderDtl->matl_code }}</td>
                                    <td style="text-align: left; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px;">{{ $OrderDtl->matl_descr }}</td>
                                    <td style="text-align: center; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px;">{{ ceil($OrderDtl->qty) }}</td>
                                    <td style="text-align: right; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px;">{{ number_format(ceil($priceAfterDisc), 0, ',', '.') }}</td>
                                    @if($this->object->sales_type != 'O')
                                        <td style="text-align: center; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px;">{{ number_format($OrderDtl->disc_pct, 0, ',', '.') }}%</td>
                                    @endif
                                    <td style="text-align: right; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px;">{{ number_format($subTotalAfterDisc, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <!-- Summary row with blank first columns -->
                            <tr>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; height: 18px;"></td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000;">Total</td>
                                @if($has_disc)
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                                @endif
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; font-weight: bold; border-top: 1px solid #000;">{{ number_format($grand_total_all, 0, ',', '.') }}</td>
                            </tr>
                            @if($this->object->amt_shipcost > 0)
                                <tr>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; height: 18px;"></td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000;">Biaya EX</td>
                                    @if($has_disc)
                                        <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                                    @endif
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000;">{{ number_format($this->object->amt_shipcost, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; height: 18px;"></td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-weight: bold;">Grand Total</td>
                                    @if($has_disc)
                                        <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                                    @endif
                                    <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-weight: bold;">{{ number_format($grand_total_all + $this->object->amt_shipcost, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    <!-- Footer -->
                    <table style="margin-top: -18px; width: 100%;">
                        <tr>
                            <td style="border: 1px solid #000; padding: 10px;">
                                <p style="margin: 0; display: inline;">Penerima: ________________</p>
                                <p style="margin: 0; text-align: end; display: inline; float: right;">Pembayaran: <strong>{{ $this->object->payment_method ?? 'CASH' }}</strong></p>
                            </td>
                        </tr>
                    </table>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        function printInvoice() {
            @this.updatePrintCounter();
            setTimeout(function() {
                window.print();
            }, 1000);
        }
    </script>
</div>
