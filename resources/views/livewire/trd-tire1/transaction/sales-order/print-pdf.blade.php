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
                    font-size: 16px;
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
                    font-size: 16px;
                }

                p {
                    margin: 1px 0;
                    font-size: 10px;
                }
            }
        </style> --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <!-- Card hanya tampil di layar, tidak saat print -->
    <div class="card d-print-none"
        style="max-width: 1200px; margin: 30px auto; background: #fff; box-shadow: 0 2px 16px rgba(0,0,0,0.08), 0 0px 1.5px rgba(0,0,0,0.03); border-radius: 10px; padding: 32px 32px 40px 32px;">
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
                        @if($this->object->tax_doc_flag == 1)
                            <p style="margin-bottom: -8px;">
                                <strong>{{ $this->object->npwp_name }}</strong>
                            </p>
                            <p style="margin-bottom: -8px;">{{ $this->object->npwp_addr }}</p>
                        @else
                            <p style="margin-bottom: -8px;">
                                <strong>{{ $this->object->Partner->name }}</strong>
                            </p>
                            <p style="margin-bottom: -8px;">{{ $this->object->Partner->address }}</p>
                            <p style="margin-bottom: -8px;">{{ $this->object->Partner->city }}</p>
                        @endif
                    </td>
                </tr>
            </table>

            <!-- Items Table -->
            <table
                style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000; line-height: 1.2;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 12%; font-size: 16px;">KODE BARANG
                        </th>
                        <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 35%; font-size: 16px;">NAMA BARANG
                        </th>
                        <th style="border: 1px solid #000; text-align: center; width: 5%; font-size: 16px;">QTY</th>
                        <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 12%; font-size: 16px;">HARGA
                            SATUAN</th>
                        @if ($this->object->sales_type != 'O')
                            <th style="border: 1px solid #000; text-align: center; padding-right: 5px; width: 5%; font-size: 16px;">DISC
                            </th>
                        @endif
                        <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 15%; font-size: 16px;">JUMLAH
                            HARGA</th>
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
                            <td
                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 3px 5px 3px 5px; font-size: 16px;">
                                {{ $OrderDtl->matl_code }}
                            </td>
                            <td
                                style="text-align: left; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px; font-size: 16px;">
                                {{ $OrderDtl->matl_descr }}
                            </td>
                            <td
                                style="text-align: center; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px; font-size: 16px;">
                                {{ ceil($OrderDtl->qty) }}
                            </td>
                            <td
                                style="text-align: right; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px; font-size: 16px;">
                                @php
                                    $discount = $OrderDtl->disc_pct / 100;
                                    $priceAfterDisc = round($OrderDtl->price * (1 - $discount));
                                @endphp
                                {{ number_format(ceil($priceAfterDisc), 0, ',', '.') }}
                            </td>
                            @if ($this->object->sales_type != 'O')
                                <td
                                    style="text-align: center; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px; font-size: 16px;">
                                    {{ number_format($OrderDtl->disc_pct, 0, ',', '.') }}%
                                </td>
                            @endif
                            <td
                                style="text-align: right; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px; font-size: 16px;">
                                {{ number_format($subTotalAfterDisc, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach

                    <!-- Summary rows for non-print -->
                    <tr>
                        <td
                            style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; height: 18px;">
                        </td>
                        <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px; font-size: 16px; text-align: start;">
                            Penerima: ________________
                        </td>
                        <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                        @if ($this->object->sales_type != 'O')
                            <td colspan="2"
                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-size: 16px;">
                                Total</td>
                        @else
                            <td
                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-size: 16px;">
                                Total</td>
                        @endif
                        <td
                            style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; font-weight: bold; border-top: 1px solid #000; font-size: 16px;">
                            {{ number_format($grand_total, 0, ',', '.') }}</td>
                    </tr>
                    @if ($this->object->amt_shipcost > 0)
                        <tr>
                            <td
                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; height: 18px;">
                            </td>
                            <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                            <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                            @if ($this->object->sales_type != 'O')
                                <td colspan="2"
                                    style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-size: 16px;">
                                    Biaya EX</td>
                            @else
                                <td
                                    style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-size: 16px;">
                                    Biaya EX</td>
                            @endif
                            <td
                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-size: 16px;">
                                {{ number_format($this->object->amt_shipcost, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td
                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; height: 18px;">
                            </td>
                            <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                            <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;"></td>
                            @if ($this->object->sales_type != 'O')
                                <td colspan="2"
                                    style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-weight: bold; font-size: 16px;">
                                    Grand Total</td>
                            @else
                                <td
                                    style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-weight: bold; font-size: 16px;">
                                    Grand Total</td>
                            @endif
                            <td
                                style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-weight: bold; font-size: 16px;">
                                {{ number_format($grand_total + $this->object->amt_shipcost, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <table style="margin-top: -18px; width: 62%;">
                <tr>
                    <td style="border: 1px solid #000; padding: 5px;">
                        <p style="margin: 0; text-align: start; font-size: 16px;">
                            Pembayaran: <strong>{{ $this->object->payment_method ?? 'CASH' }}</strong>
                        </p>
                    </td>
                </tr>
            </table>
            <p style="font-size: 15px; text-align: start; font-weight: lighter;">Barang yang sudah dibeli tidak bisa dikembalikan</p>
        </div>
    </div>

    <!-- Area print tetap tampil saat print -->
    <div id="print" class="d-none d-print-block p-20">
        <div style="max-width: 1200px; margin: 0 auto; font-family: 'Calibri'; font-size: 16px;">
            <div class="invoice-box" style="max-width: 1200px; margin: auto; padding: 20px;">
                @php
                    $grand_total_all = $this->object->OrderDtl->reduce(function ($carry, $d) {
                        $disc = $d->disc_pct / 100;
                        $price = round($d->price * (1 - $disc));
                        return $carry + $price * $d->qty;
                    }, 0);
                @endphp
                @foreach ([$this->object->OrderDtl] as $chunkIndex => $chunk)
                    <!-- Header per page -->
                    <table width="100%" style="margin-bottom: 10px; border: none;">
                        <tr style="border: none;">
                            <td style="width: 25%; border: none;">
                                <div style="text-align: center;">
                                    <h2
                                        style="margin: 0; text-decoration: underline; font-weight: bold; white-space: nowrap;">
                                        CAHAYA TERANG</h2>
                                    <p style="margin-top: -2px; white-space: nowrap;">SURABAYA</p>
                                </div>
                            </td>
                            <td
                                style="text-align: center; margin-top: 20px; vertical-align: bottom; width: 45%; border: none;">
                                <h3 style="margin-bottom: 1px; text-decoration: underline;">NOTA PENJUALAN</h3>
                                <p style="margin: 0px 0;">No. {{ $this->object->tr_code }}</p>
                            </td>
                            <td style="text-align: left; vertical-align: bottom; width: 30%;">
                                <p style="margin-bottom: -8px;">Surabaya,
                                    {{ \Carbon\Carbon::parse($this->object->tr_date)->format('d-M-Y') }}</p>
                                <p style="margin-bottom: -8px;">Kepada Yth :</p>
                                @if($this->object->tax_doc_flag == 1)
                                    <p style="margin-bottom: -8px;"><strong>{{ $this->object->npwp_name }}</strong></p>
                                    <p style="margin-bottom: -8px;">{{ $this->object->npwp_addr }}</p>
                                @else
                                    <p style="margin-bottom: -8px;"><strong>{{ $this->object->Partner->name }}</strong></p>
                                    <p style="margin-bottom: -8px;">{{ $this->object->Partner->address }}</p>
                                    <p style="margin-bottom: -8px;">{{ $this->object->Partner->city }}</p>
                                @endif
                            </td>
                        </tr>
                    </table>
                    <!-- Items Table -->
                    @php $has_disc = $this->object->sales_type != 'O'; @endphp
                    <table
                        style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000; line-height: 1.2;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 12%; font-size: 16px;">
                                    KODE BARANG</th>
                                <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 35%; font-size: 16px;">
                                    NAMA BARANG</th>
                                <th style="border: 1px solid #000; text-align: center; width: 5%; font-size: 16px;">QTY</th>
                                <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 13%; font-size: 16px;">
                                    HARGA SATUAN</th>
                                @if ($this->object->sales_type != 'O')
                                    <th
                                        style="border: 1px solid #000; text-align: center; padding-right: 5px; width: 5%; font-size: 16px;">
                                        DISC</th>
                                @endif
                                <th style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 15%; font-size: 16px;">
                                    JUMLAH HARGA</th>
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
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 3px 5px 3px 5px; font-size: 16px;">
                                        {{ $OrderDtl->matl_code }}</td>
                                    <td
                                        style="text-align: left; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px; font-size: 16px;">
                                        {{ $OrderDtl->matl_descr }}</td>
                                    <td
                                        style="text-align: center; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px; font-size: 16px;">
                                        {{ ceil($OrderDtl->qty) }}</td>
                                    <td
                                        style="text-align: right; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px; font-size: 16px;">
                                        {{ number_format(ceil($priceAfterDisc), 0, ',', '.') }}</td>
                                    @if ($this->object->sales_type != 'O')
                                        <td
                                            style="text-align: center; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px; font-size: 16px;">
                                            {{ number_format($OrderDtl->disc_pct, 0, ',', '.') }}%</td>
                                    @endif
                                    <td
                                        style="text-align: right; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px; font-size: 16px;">
                                        {{ number_format($subTotalAfterDisc, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <!-- Summary row with blank first columns -->
                            <tr>
                                <td
                                    style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; height: 18px;">
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px; font-size: 16px; text-align: start;">
                                    Penerima: ________________
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;">
                                </td>
                                @if ($has_disc)
                                    <td colspan="2"
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-size: 16px;">
                                        Total</td>
                                @else
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-size: 16px;">
                                        Total</td>
                                @endif
                                <td
                                    style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; font-weight: bold; border-top: 1px solid #000; font-size: 16px;">
                                    {{ number_format($grand_total_all, 0, ',', '.') }}</td>
                            </tr>
                            @if ($this->object->amt_shipcost > 0)
                                <tr>
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; height: 18px;">
                                    </td>
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;">
                                    </td>
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;">
                                    </td>
                                    @if ($has_disc)
                                        <td colspan="2"
                                            style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-size: 16px;">
                                            Biaya EX</td>
                                    @else
                                        <td
                                            style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-size: 16px;">
                                            Biaya EX</td>
                                    @endif
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-size: 16px;">
                                        {{ number_format($this->object->amt_shipcost, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; height: 18px;">
                                    </td>
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;">
                                    </td>
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000;">
                                    </td>
                                    @if ($has_disc)
                                        <td colspan="2"
                                            style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-weight: bold; font-size: 16px;">
                                            Grand Total</td>
                                    @else
                                        <td
                                            style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-weight: bold; font-size: 16px;">
                                            Grand Total</td>
                                    @endif
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding-right: 5px; border-top: 1px solid #000; font-weight: bold; font-size: 16px;">
                                        {{ number_format($grand_total_all + $this->object->amt_shipcost, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    <!-- Footer -->
                    <table style="margin-top: -18px; width: 62%;">
                        <tr>
                            <td style="border: 1px solid #000; padding: 5px;">
                                <p style="margin: 0; text-align: start; font-size: 16px;">
                                    Pembayaran: <strong>{{ $this->object->payment_method ?? 'CASH' }}</strong>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <p style="font-size: 15px; text-align: start; font-weight: lighter;">Barang yang sudah dibeli tidak bisa dikembalikan</p>
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

        // Listen for successful print counter update
        document.addEventListener('livewire:init', () => {
            Livewire.on('success', (message) => {
                if (message.includes('Print counter berhasil diupdate')) {
                    // Refresh halaman setelah berhasil update counter
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000); // Delay 2 detik untuk memastikan print dialog selesai
                }
            });
        });
    </script>
</div>
