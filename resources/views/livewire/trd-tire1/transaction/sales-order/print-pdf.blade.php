<div>
    <!-- UI Controls - Hidden saat print -->
    <div class="container mb-5 mt-3 no-print">
        <div>
            {{-- <x-ui-button clickEvent="" type="Back" button-name="Back" /> --}}
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row d-flex align-items-baseline">
                    <div class="col-xl-9">
                        <p style="color: #7e8d9f; font-size: 20px;">Sales Order</p>
                    </div>
                    <div class="col-xl-3 float-end">
                        <button type="button"
                            style="background: linear-gradient(135deg, #007bff, #0056b3);
                                   color: white;
                                   border: none;
                                   padding: 12px 12px;
                                   border-radius: 8px;
                                   font-size: 14px;
                                   font-weight: 500;
                                   cursor: pointer;
                                   display: inline-flex;
                                   align-items: center;
                                   gap: 8px;
                                   box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
                                   transition: all 0.2s ease;"
                            onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(0, 123, 255, 0.4)';"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0, 123, 255, 0.3)';"
                            onclick="printInvoice()">
                            <i class="fas fa-print" style="font-size: 16px;"></i>
                            <span>Print Order</span>
                        </button>
                    </div>
                    <hr>
                </div>
            </div>
        </div>
    </div>

    <div id="print">
        <div class="card print-card">
            <div class="card-body">
                <div class="invoice-box">
                    <!-- Header -->
                    <table width="100%" style="margin-bottom: 10px; border: none;">
                        <!-- Counter untuk array nota -->
                        <div class="mt-1" style="text-align: end; margin-bottom: -30px; z-index: 100;">
                            {{ $this->notaCounter['nota'] }}
                        </div>
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
                                <p style="margin: 0px 0;">No. <span style="font-family: 'Times New Roman', Times, serif;">{{ $this->object->tr_code }}</span></p>
                            </td>
                            <td style="text-align: left; vertical-align: bottom; width: 30%; border: none;">
                                <p style="margin-bottom: -8px;">
                                    Surabaya, {{ \Carbon\Carbon::parse($this->object->tr_date)->format('d-M-Y') }}
                                </p>
                                <p style="margin-bottom: -8px;">Kepada Yth :</p>
                                @if ($this->object->tax_doc_flag == 1)
                                    <p style="margin-bottom: -9px;">
                                        <strong>{{ $this->object->npwp_name }}</strong>
                                    </p>
                                    <p style="margin-bottom: -8px; line-height: 1;">{{ $this->object->npwp_addr }}</p>
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
                                <th
                                    style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 12%; font-size: 16px;">
                                    KODE BARANG
                                </th>
                                <th
                                    style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 35%; font-size: 16px;">
                                    NAMA BARANG
                                </th>
                                <th style="border: 1px solid #000; text-align: center; width: 5%; font-size: 16px;">QTY</th>
                                <th
                                    style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 15%; font-size: 16px;">
                                    HARGA
                                    SATUAN</th>
                                @if ($this->object->sales_type != 'O')
                                    <th
                                        style="border: 1px solid #000; text-align: center; padding-right: 5px; width: 5%; font-size: 16px;">
                                        DISC
                                    </th>
                                @endif
                                <th
                                    style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 15%; font-size: 16px;">
                                    JUMLAH
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
                                        @if ($this->object->sales_type == 'O')
                                            {{ number_format(ceil($OrderDtl->price_afterdisc), 0, ',', '.') }}
                                        @else
                                            {{ number_format(ceil($OrderDtl->price), 0, ',', '.') }}
                                        @endif
                                    </td>
                                    @if ($this->object->sales_type != 'O')
                                        <td
                                            style="text-align: center; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px; font-size: 16px;">
                                            {{ $OrderDtl->disc_pct == (int)$OrderDtl->disc_pct ? (int)$OrderDtl->disc_pct : number_format($OrderDtl->disc_pct, 1, ',', '.') }}%
                                        </td>
                                    @endif
                                    <td
                                        style="text-align: right; border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px 3px 5px; font-size: 16px;">
                                        {{ number_format($subTotalAfterDisc, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Summary rows -->
                            <tr>
                                <td
                                    style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; height: 18px;">
                                </td>
                                <td
                                    style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; padding: 3px 5px; font-size: 16px; text-align: start;">
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
                    <table style="margin-top: -18px; width: 60%;">
                        <tr>
                            <td style="border: 1px solid #000; padding: 5px;">
                                <p style="margin: 0; text-align: start; font-size: 16px;">
                                    Pembayaran: <strong>{{ $this->object->payment_method ?? 'CASH' }}</strong>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <p style="font-size: 15px; text-align: start; font-weight: lighter;">Barang yang sudah dibeli tidak bisa
                        dikembalikan</p>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        function printInvoice() {
            @this.updatePrintCounter();

            // Trigger print dialog
            setTimeout(function() {
                window.print();
            }, 1000);

            // Listen for print dialog close event
            window.addEventListener('afterprint', function() {
                // Refresh halaman setelah print dialog ditutup
                setTimeout(function() {
                    window.location.reload();
                }, 500); // Delay singkat untuk memastikan print selesai
            });
        }

        // Listen for successful print counter update
        document.addEventListener('livewire:init', () => {
            Livewire.on('success', (message) => {
                if (message.includes('Print counter berhasil diupdate')) {
                    console.log('Print counter berhasil diupdate');
                }
            });
        });
    </script>

<style>
    @page {
        size: 217mm auto landscape;
        margin: 1mm 5mm;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Calibri', sans-serif;
        font-size: 14px;
        color: #000;
        -webkit-font-smoothing: antialiased;
        background: #f5f7fa;
    }

    /* Styling untuk tampilan layar (non-print) */
    #print {
        width: 100%;
        margin: 20px auto;
        padding: 0;
        background: transparent;
    }

    .print-card {
        max-width: 1200px;
        margin: 0 auto;
        background: #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
        border-radius: 12px;
        border: none;
        overflow: hidden;
    }

    .print-card .card-body {
        padding: 40px;
        background: #fff;
    }

    .invoice-box {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        padding: 20px;
        box-sizing: border-box;
        font-family: 'Calibri', sans-serif;
        font-size: 16px;
        line-height: 1.4;
        color: #000;
        background: #fff;
    }

    .invoice-box table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .invoice-box td,
    .invoice-box th {
        vertical-align: top;
        font-size: 16px;
    }

    .invoice-box th {
        background-color: #f8f9fa;
        font-weight: bold;
    }

    .invoice-box p {
        margin: 4px 0;
    }

    /* Styling untuk print */
    @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        @page {
            size: 217mm auto landscape;
            margin: 7mm 11mm 0mm 5mm;
        }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            width: 100% !important;
            height: 100% !important;
        }

        /* Hilangkan semua elemen non-print */
        .no-print {
            display: none !important;
        }

        /* Bersihkan margin/padding semua container saat print */
        #print {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .print-card,
        .print-card .card-body {
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            background: #fff !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        /* Pastikan isi invoice menempel penuh ke tepi saat print */
        .invoice-box {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            background: #fff !important;
            box-shadow: none !important;
        }

        .invoice-box th {
            background-color: transparent !important;
        }
    }
</style>


</div>
