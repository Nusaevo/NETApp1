<div>
    <!-- UI Controls - Hidden saat print -->
    <div class="container mb-5 mt-3 no-print">
        <div>
            <x-ui-button clickEvent="" type="Back" button-name="Back" />
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row d-flex align-items-baseline">
                    <div class="col-xl-9">
                        <p style="color: #7e8d9f; font-size: 20px;">Sales Billing</p>
                    </div>
                    <div class="col-xl-3 float-end">
                        <button type="button"
                            style="background: linear-gradient(135deg, #007bff, #0056b3);
                                   color: white;
                                   border: none;
                                   padding: 12px 20px;
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
                            <span>Print Billing</span>
                        </button>
                    </div>
                    <hr>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Content - Selalu visible -->
    <div id="print">
                    <div class="invoice-box">
                        <div style="margin: 0 auto; font-family: Arial, sans-serif; font-size: 14px; line-height: 1.4;">
                            <!-- Header dengan tanggal di kanan -->
                            <div style="margin-bottom: 15px;">
                                <div style="text-align: right; font-size: 14px; margin-bottom: 10px;">
                                    <span>Tgl.: {{ now()->format('d-M-Y') }}</span>
                                </div>
                                <div style="border: 1px solid #000; padding: 10px; width: 100%; display: inline-block;">
                                    <div style="font-weight: bold; font-size: 16px;">PLATINA</div>
                                    <div style="font-size: 14px;">KERTAJAYA 16, SURABAYA</div>
                                </div>
                            </div>

                            <!-- Garis pemisah -->
                            <div style="border-top: 1px solid #000; margin-bottom: 15px;"></div>

                            <!-- Instruksi -->
                            <div style="margin-bottom: 15px;">
                                <span style="text-decoration: underline; font-weight: bold; font-size: 14px;">
                                    Mohon Periksa Nota-nota tersebut di bawah ini
                                </span>
                            </div>

                            <!-- Tabel data nota -->
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                                @php $grandTotal = 0; @endphp
                                @foreach ($orders as $i => $order)
                                    @php $grandTotal += $order->amt; @endphp
                                    <tr style="height: 25px;">
                                        <td style="padding: 2px 0; width: 30px;">{{ $i + 1 }}.</td>
                                        <td style="padding: 2px 5px; width: 120px;">
                                            Tgl. {{ \Carbon\Carbon::parse($order->tr_date)->format('d-m-Y') }}
                                        </td>
                                        <td style="padding: 2px 5px; width: 150px;">No. {{ $order->tr_code }}</td>
                                        <td style="padding: 2px 5px; text-align: right; width: 30px;">Rp.</td>
                                        <td style="padding: 2px 5px; text-align: right; width: 120px;">
                                            {{ number_format($order->amt, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <!-- Total -->
                            <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
                                <span style="font-weight: bold;">Jumlah:</span>
                                <span style="font-weight: bold; text-decoration: underline;">
                                    Rp. {{ number_format($grandTotal, 2, ',', '.') }}
                                </span>
                            </div>

                            <!-- Tanda tangan -->
                            <div style="margin-top: 30px;">
                                <span>Ttd:</span>
                            </div>
                        </div>
                    </div>
    </div>

    <script type="text/javascript">
        function printInvoice() {
            window.print();
        }
    </script>

    <style>
        @page {
            size: A6 portrait;
            margin: 10mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            print-color-adjust: exact;
        }

        #print {
            width: 100%;
            margin: 0 auto;
            padding: 0;
        }

        .invoice-box {
            width: 100%;
            box-sizing: border-box;
            padding: 0;
            font-size: 14px;
            line-height: 1.4;
        }

        .invoice-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-box td,
        .invoice-box th {
            padding: 2px 5px;
            vertical-align: top;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }

            /* Sembunyikan elemen UI */
            .no-print {
                display: none !important;
            }

            /* Pastikan print content terlihat */
            #print {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }

            .invoice-box {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 14px !important;
                background: white !important;
            }

            .invoice-box > div {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                background: white !important;
            }

            /* Pastikan teks terlihat */
            .invoice-box * {
                color: black !important;
                background: transparent !important;
            }
        }
    </style>
</div>
