<div>
    @php
        use App\Services\TrdJewel1\Master\MasterService;

        $masterService = new MasterService();
    @endphp
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap');
    </style>

    <div class="container mb-5 mt-3">
        <div>
            <x-ui-button clickEvent="" type="Back" button-name="Back" />
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row d-flex align-items-baseline">
                    <div class="col-xl-9">
                        <p style="color: #7e8d9f; font-size: 24px;">Nota >> <strong>No:
                                {{ $this->object->tr_id }}</strong></p>
                    </div>
                    <div class="col-xl-3 float-end">
                        <button type="button" class="btn btn-light text-capitalize border-0" onclick="printInvoice()">
                            <i class="fas fa-print text-primary"></i> Print
                        </button>
                    </div>
                    <hr>
                </div>

                <div id="print">
                    @foreach ($object->OrderDtl as $OrderDtl)
                        <div class="invoice-box-container">
                            <div class="invoice-box">
                                <div style="padding-left: 10px; padding-right:10px;">
                                    <div
                                        style="display: flex; justify-content: center; align-items: center; min-height: 100px; width: 100%;">
                                        <!-- Kontainer konten yang di tengah -->
                                        <table style="width: 100%;">
                                            <tr>
                                                @if ($isShowLogo)
                                                    <td style="width: 20%; text-align: left; vertical-align: middle;">
                                                        <div style="text-align: left;">
                                                            <img src="{{ asset('customs/logos/TrdJewel1.png') }}"
                                                                alt="Logo TrdJewel1"
                                                                style="width: 140px; height: 60px; margin-bottom: 5px;">
                                                        </div>
                                                    </td>
                                                @else
                                                    <td style="width: 20%;"></td>
                                                @endif

                                                <!-- Kolom untuk logo Wijaya Mas dan detail kontak -->
                                                <td style="width: 60%; text-align: center; vertical-align: middle;">
                                                    <div style="text-align: center; display: inline-block;">
                                                        <img src="{{ asset('customs/logos/WijayaMas.png') }}"
                                                            alt="Logo Wijaya Mas"
                                                            style="width: 180px; height: auto; margin-bottom: 5px;">
                                                        <ul
                                                            style="list-style: none; margin: 0; padding: 0; line-height: 1.2;">
                                                            <li style="font-size: 10px;">Ruko Pluit Village No.59, Jl
                                                                Pluit Indah Raya, Jakarta 14440</li>
                                                            <li style="font-size: 10px;">+62.216683859</li>
                                                            <li style="font-size: 10px;">wijayamas28@yahoo.com</li>
                                                        </ul>
                                                    </div>
                                                </td> <!-- Kolom untuk nomor transaksi dan tanggal -->
                                                <td style="width: 20%; text-align: right; vertical-align: top;">
                                                    <ul
                                                        style="list-style: none; margin: 0; padding: 0; line-height: 1; padding-top: 40px;">
                                                        <li style="font-size: 16px;">No:
                                                            <strong>WM-{{ $this->object->tr_id }}</strong></li>
                                                        <li style="font-size: 12px;">Tgl:
                                                            <strong>{{ $this->object->tr_date }}</strong></li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </table>

                                    </div>

                                    <table style="margin-top: 0px;">
                                        <tr class="information">
                                            <td style="width: 50%; vertical-align: top;">
                                                @php
                                                    $imagePath = $OrderDtl->Material->Attachment->first()
                                                        ? $OrderDtl->Material->Attachment->first()->getUrl()
                                                        : 'https://via.placeholder.com/200';
                                                @endphp
                                                <div style="width: 300px;">
                                                    <img src="{{ $imagePath }}"
                                                        style="width: 150px; height: 200px; object-fit: cover;">
                                                </div>
                                            </td>
                                            <td style="width: 50%;">
                                                <p style="margin: 0; padding: 0;">Kode Barang :
                                                    <strong>{{ $OrderDtl->matl_code }}</strong></p>
                                                <p style="margin: 0; padding: 0;">
                                                    <strong>{{ $masterService->GetMatlCategory1String($OrderDtl->Material->jwl_category1) }}
                                                        {{ $masterService->GetMatlCategory2String($OrderDtl->Material->jwl_category2) }}</strong>
                                                </p>

                                                <p style="margin: 0; padding: 0;">Berat :
                                                    {{ $OrderDtl->Material->jwl_wgt_gold }} Gram</p>
                                                <p style="margin: 0; padding: 0;">Kemurnian :
                                                    {{ $masterService->GetMatlJewelPurityString($OrderDtl->Material->jwl_carat) }}
                                                </p>
                                                <p style="margin: 0; padding: 0;">Bahan :
                                                    {{ $OrderDtl->Material->descr }}</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <table style="margin-top: 10px;">
                                        <tr class="heading">
                                            <td>KETERANGAN:</td>
                                        </tr>
                                        <tr class="item">
                                            <td class="description" style="font-size: 8px; margin: 0; padding: 0;">
                                                <ul style="margin: 0; padding: 2px; line-height: 1;">
                                                    @foreach ($printRemarks as $remark)
                                                        @if ($remark['checked'])
                                                            <li>{{ $remark['label'] }}</li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            </td>
                                            <td class="item-price" style="text-align: right; width: 50%;">
                                                @if ($isShowPrice)
                                                    <p style="margin: 0; padding: 0; font-size: 18px;"><b>
                                                            {{ rupiah(ceil($OrderDtl->price)) }}</b></p>
                                                    <p style="margin: 0; padding: 0; font-size: 12px;">
                                                        {{ terbilang($OrderDtl->price) }}</p>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                            </div>
                        </div>
                    @endforeach
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
            size: A5 landscape;
            /* Ukuran kertas A5 landscape (210mm x 148mm) */
            margin: 0 10mm 5mm 10mm;
            /* Margin: atas 0, kanan 10mm, bawah 5mm, kiri 10mm */
        }


        body {
            margin: 0;
            padding: 0;
            font-family: 'Calibri';
            font-size: 14px;
            color: #555;
        }

        .container {
            padding: 0;
            margin: 0;
        }

        .card {
            border: none;
            box-shadow: none;
        }

        #print {
            width: 100%;
            height: 100%;
            box-sizing: border-box;
        }

        .invoice-box-container {
            display: flex;
            flex-direction: column;
            height: auto;
            /* Ubah untuk menyesuaikan konten */
            box-sizing: border-box;
            page-break-inside: avoid;
            margin-left: 5px;
        }

        .invoice-box {
            width: 100%;
            height: auto;
            /* Sesuaikan tinggi dengan konten */
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            line-height: 20px;
            /* Kurangi jarak baris untuk menghemat ruang */
            font-weight: 400;
            /* Kurangi ketebalan font untuk menghemat ruang */
            color: #555;
            box-sizing: border-box;
            page-break-inside: avoid;
            margin: 0;
            padding: 0;
            /* Hilangkan semua padding */
        }

        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }

        .invoice-box table td {
            vertical-align: top;
            padding: 1mm;
            /* Kurangi padding untuk menghemat ruang */
        }

        .information td {
            border-top: 3px solid #ddd;
            border-bottom: 3px solid #ddd;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #print,
            #print * {
                visibility: visible;
            }

            #print {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                box-sizing: border-box;
            }

            .btn,
            .d-flex {
                display: none;
            }

            .invoice-box {
                border: none;
                box-shadow: none;
                margin: 0;
                padding: 0;
                height: auto;
                page-break-after: always;
            }
        }
    </style>

</div>
