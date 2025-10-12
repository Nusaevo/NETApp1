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

    <div id="print">
        @foreach($groupedOrders as $index => $customerGroup)
            <div class="card shadow-sm customer-billing" style="max-width: 800px; margin: 0 auto; @if($index > 0) margin-top: 50px; @endif">
                <div class="card-body p-4">
                    <div class="invoice-box">
                        <div style="margin: 0 auto; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.4;">
                            <!-- Header dengan tanggal di kanan -->
                            <div style="margin-bottom: 15px;">
                                <div style="text-align: right; font-size: 12px; margin-bottom: 10px; padding-top: 30px;">
                                    <span>Tgl.: {{ $customerGroup['print_date'] ? \Carbon\Carbon::parse($customerGroup['print_date'])->format('d-m-Y') : '' }}</span>
                                </div>
                                <div style="border: 1px solid #000; padding: 10px; width: 100%; display: inline-block;">
                                    <div style="font-size: 14px;">{{ $customerGroup['partner']->name }}</div>
                                    <div style="font-size: 14px;">{{ $customerGroup['partner']->address }}</div>
                                </div>
                            </div>

                            <!-- Garis pemisah -->
                            <div style="border-top: 1px solid #000; margin-bottom: 15px;"></div>

                            <!-- Instruksi -->
                            <div style="margin-bottom: 15px; text-align: center;">
                                <span style="text-decoration: underline; font-size: 16px;">
                                    Mohon Periksa Nota-nota tersebut di bawah ini
                                </span>
                            </div>

                            <!-- Tabel data nota -->
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                                @php $customerTotal = 0; @endphp
                                @foreach ($customerGroup['orders'] as $i => $order)
                                    @php $customerTotal += $order->amt; @endphp
                                    <tr style="height: 25px;">
                                        <td style="padding: 2px 5px; width: auto; white-space: nowrap;">
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
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 2px 0; width: 30px;"></td>
                                    <td style="padding: 2px 5px; width: auto;"></td>
                                    <td style="padding: 2px 35px; width: 150px;">Jumlah:</td>
                                    <td style="padding: 2px 5px; text-align: right; width: 30px;">Rp.</td>
                                    <td style="padding: 2px 5px; text-align: right; width: 120px; font-weight: bold; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                                        {{ number_format($customerTotal, 2, ',', '.') }}
                                    </td>
                                </tr>
                            </table>

                            <!-- Tanda tangan -->
                            <div style="margin-top: 30px;">
                                <span>Ttd:</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script type="text/javascript">
        function printInvoice() {
            window.print();
        }
    </script>

    <style>
        @page {
            size: 105mm auto;
            margin: 5mm;
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
            font-size: 17px;
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

            /* Hilangkan styling card saat print tapi tetap tampilkan content */
            .card {
                box-shadow: none !important;
                border: none !important;
                background: transparent !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .card-body {
                padding: 0 !important;
                margin: 0 !important;
                background: transparent !important;
            }

            /* Pastikan print content terlihat */
            #print {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }

            /* Pastikan invoice-box terlihat saat print */
            .invoice-box {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                font-size: 17px !important;
            }

            .invoice-box > div {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }

            /* Pastikan teks terlihat */
            .invoice-box * {
                color: black !important;
                background: transparent !important;
            }

            /* Page break untuk multiple customers */
            .page-break {
                page-break-before: always !important;
            }

            /* Pastikan setiap customer billing dimulai di halaman baru */
            .customer-billing {
                page-break-before: always !important;
            }

            .customer-billing:first-child {
                page-break-before: auto !important;
            }
        }
    </style>
</div>
