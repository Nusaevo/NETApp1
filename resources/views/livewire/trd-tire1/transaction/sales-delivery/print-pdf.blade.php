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
                        <p style="color: #7e8d9f; font-size: 20px;">Surat Jalan</p>
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
                            <span>Print Surat Jalan</span>
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
                    <table width="100%" style="margin-bottom: 5px; border: none;">
                        <!-- Counter untuk array nota -->
                        <div class="mt-3" style="text-align: end; margin-bottom: -30px; z-index: 100;">
                            {{ $this->notaCounter['surat_jalan'] }}
                        </div>
                        <tr style="border: none;">
                            @if ($this->isFirstShipTo())
                                <td style="width: 25%; border: none;">
                                    <div style="text-align: center;">
                                        <h2 style="margin: 0; text-decoration: underline; font-weight: bold; font-size: 22px;">
                                            CAHAYA TERANG</h2>
                                        <p style="margin-top: -5px;">SURABAYA</p>
                                    </div>
                                </td>
                            @else
                                <td style="width: 25%; border: none;">
                                    <!-- Header disembunyikan untuk ship_to yang bukan pertama -->
                                </td>
                            @endif
                            <td style="text-align: center; margin-top: 20px; vertical-align: bottom; width: 47%; border: none;">
                                <h3 style="margin-bottom: -5px; text-decoration: underline;">
                                    SURAT JALAN</h3>
                                <p style="margin: 0px 0;">No. {{ $this->object->tr_code }}</p>
                            </td>
                            <td style="text-align: left; vertical-align: bottom; width: 30%; padding-bottom: 5px; border: none;">
                                <p style="margin-bottom: -9px;">
                                    Surabaya, {{ \Carbon\Carbon::parse($this->object->tr_date)->format('d-M-Y') }}
                                </p>
                                <p style="margin-bottom: -8px;">Kepada Yth :</p>
                                <p style="margin-bottom: -25px;">
                                    <strong>{{ $this->object->ship_to_name }}</strong>
                                </p>
                                <p style="margin-bottom: -8px; white-space: pre-line; line-height: 1; margin-top: 5px;">
                                    {{ $this->object->ship_to_addr }}</p>
                            </td>
                        </tr>
                    </table>

                    <!-- Items Table -->
                    <table
                        style="width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1px solid #000; line-height: 1.1;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid #000; text-align: center; width: 5%; font-size: 16px;">NO</th>
                                <th
                                    style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 15%; font-size: 16px;">
                                    KODE BARANG</th>
                                <th style="border: 1px solid #000; text-align: center; width: 25%; font-size: 16px;">KETERANGAN
                                </th>
                                <th
                                    style="border: 1px solid #000; text-align: right; padding-right: 5px; width: 5%; font-size: 16px;">
                                    QTY</th>
                                <th
                                    style="border: 1px solid #000; text-align: left; padding-left: 5px; width: auto; font-size: 16px;">
                                    NAMA BARANG</th>
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
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 2px 3px; font-size: 16px;">
                                        {{ $counter++ }}
                                    </td>
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 2px 5px; font-size: 16px;">
                                        {{ $OrderDtl->matl_code }}
                                    </td>
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 2px 3px; font-size: 16px;">
                                    </td>
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: right; padding: 2px 5px; font-size: 16px;">
                                        {{ ceil($OrderDtl->qty) }}
                                    </td>
                                    <td
                                        style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 2px 3px; font-size: 16px;">
                                        {{ $OrderDtl->matl_descr }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <table style="width: 100%; margin-top: -11px;">
                        <tr style="line-height: 1.1;">
                            <td style="text-align: right; padding: 2px 3px; width: 5%; font-size: 16px;"></td>
                            <td style="text-align: right; padding: 2px 3px; width: 15%; font-size: 16px;">TOTAL :</td>
                            <td
                                style="border-top: 1px solid #000; border-left: 1px solid #000; border-bottom: 1px solid #000; text-align: right; padding: 2px 3px; width: 25%; font-size: 16px;">
                            </td>
                            <td
                                style="border: 1px solid #000; text-align: right; padding: 2px 5px; width: 5%; font-size: 16px;">
                                {{ $total_qty }}</td>
                            <td style="text-align: left; padding: 2px 3px; width: auto; font-size: 16px;"></td>
                        </tr>
                    </table>

                    <!-- Empty rows untuk memposisikan tanda tangan di bawah -->
                    @php
                        $itemCount = $this->object->OrderDtl->count();
                        $minRows = 8; // Minimum 8 baris untuk memastikan tanda tangan di bawah
                        $emptyRows = max(0, $minRows - $itemCount);
                    @endphp

                    @for ($i = 0; $i < $emptyRows; $i++)
                        <div style="height: 20px; line-height: 20px;">&nbsp;</div>
                    @endfor

                    <!-- Recipient Info -->
                    <div style="margin-top: 30px;">
                        <p style="margin: 0 0 0px 0;">
                            {{ $this->object->npwp_name }} -
                            {{ $this->object->npwp_addr }}
                        </p>

                        <div width="100%" style="margin-top: -10px;">
                            <div class="row justify-content-between" style="text-align: center;">
                                <div style="width: 25%;">
                                    <p style="margin: 5px 0;">Administrasi:</p><br>
                                    <p style="margin: 5px 0;">(________________)</p>
                                </div>
                                <div style="width: 25%;">
                                    <p style="margin: 5px 0;">Gudang:</p><br>
                                    <p style="margin: 5px 0;">(________________)</p>
                                </div>
                                <div style="width: 25%;">
                                    <p style="margin: 5px 0;">Driver:</p><br>
                                    <p style="margin: 5px 0;">(________________)</p>
                                </div>
                                <div style="width: 25%;">
                                    <p style="margin: 5px 0;">Penerima:</p><br>
                                    <p style="margin: 5px 0;">(________________)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        function printInvoice() {
            @this.updateDeliveryPrintCounter();

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
                if (message.includes('Print counter surat jalan berhasil diupdate')) {
                    console.log('Print counter surat jalan berhasil diupdate');
                }
            });
        });
    </script>

    <style>
        @page {
            size: 217mm auto landscape;
            margin: 2mm 5mm;
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
            margin: 0;
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
            padding: 20px;
            background: #fff;
        }

        .invoice-box {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 10px;
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
                margin: 5mm 10mm 0mm 5mm;
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
