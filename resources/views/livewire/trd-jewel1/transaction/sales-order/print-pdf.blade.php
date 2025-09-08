<div>
    @php
        use App\Services\TrdJewel1\Master\MasterService;

        $masterService = new MasterService();
    @endphp
    <!-- Remove Google Fonts import that can cause loading issues -->

    <div style="padding: 0; margin: 0; max-width: 100%;">
        <div class="no-print">
            <x-ui-button clickEvent="" type="Back" button-name="Back" />
        </div>

        <div style="border: none; box-shadow: none;">
            <div>
                <div class="no-print" style="display: flex; align-items: baseline;">
                    <div style="flex: 1;">
                        <p style="color: #7e8d9f; font-size: 24px; margin-bottom: 0;">Nota >> <strong>No: {{ $this->object->tr_id }}</strong></p>
                    </div>
                    <div style="text-align: right;">
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
                            <span>Print Nota</span>
                        </button>
                    </div>
                </div>
                <hr class="no-print">

                <div id="print" style="width: 100%; box-sizing: border-box;">
                    @foreach ($object->OrderDtl as $OrderDtl)
                        <div style="width: 100%; height: 148mm; box-sizing: border-box; page-break-after: always; page-break-inside: avoid; margin: 0; padding: 0; display: flex; flex-direction: column;">
                            <div style="width: 100%; height: 100%; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); box-sizing: border-box; background: white; display: flex; flex-direction: column;">
                                <div style="padding: 8mm; height: 100%; display: flex; flex-direction: column;">
                                    <!-- Header Section -->
                                    <div style="height: 25mm; display: flex; align-items: center; margin-bottom: 5mm;">
                                        <table style="width: 100%; height: 100%; border-collapse: collapse;">
                                            <tr>
                                                @if ($isShowLogo)
                                                    <td style="width: 20%; text-align: left; vertical-align: middle; padding: 0;">
                                                        <div style="text-align: left;">
                                                            <img src="{{ asset('customs/logos/TrdJewel1.png') }}"
                                                                alt="Logo TrdJewel1" style="max-width: 100px; max-height: 50px; object-fit: contain;">
                                                        </div>
                                                    </td>
                                                @else
                                                    <td style="width: 20%; padding: 0;"></td>
                                                @endif

                                                <td style="width: 60%; text-align: center; vertical-align: middle; padding: 0;">
                                                    <div style="text-align: center; display: inline-block;">
                                                        <img src="{{ asset('customs/logos/WijayaMas.png') }}"
                                                            alt="Logo Wijaya Mas" style="max-width: 140px; max-height: 60px; object-fit: contain; margin-bottom: 2mm;">
                                                        <ul style="list-style: none; margin: 0; padding: 0; line-height: 1.2;">
                                                            <li style="font-size: 10px; color: #666; margin: 0;">Ruko Pluit Village No.59, Jl Pluit Indah Raya, Jakarta 14440</li>
                                                            <li style="font-size: 10px; color: #666; margin: 0;">+62.216683859</li>
                                                            <li style="font-size: 10px; color: #666; margin: 0;">wijayamas28@yahoo.com</li>
                                                        </ul>
                                                    </div>
                                                </td>

                                                <td style="width: 20%; text-align: right; vertical-align: middle; padding: 0;">
                                                    <ul style="list-style: none; margin: 0; padding: 0;">
                                                        <li style="font-size: 12px; font-weight: bold; color: #333; margin-bottom: 2px;">No: <strong>WM-{{ $this->object->tr_id }}</strong></li>
                                                        <li style="font-size: 10px; color: #666;">Tgl: <strong>{{ $this->object->tr_date }}</strong></li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Product Section -->
                                    <table style="width: 100%;">
                                        <tr>
                                            <td style="width: 40%; text-align: center; vertical-align: top; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 3mm;">
                                                @php
                                                    $imagePath = $OrderDtl->Material->Attachment->first()
                                                        ? $OrderDtl->Material->Attachment->first()->getUrl()
                                                        : 'https://via.placeholder.com/200';
                                                @endphp
                                                <div style="text-align: center;">
                                                    <img src="{{ $imagePath }}" style="max-width: 200px; max-height: 200px; object-fit: cover; border: 1px solid #ccc;">
                                                </div>
                                            </td>
                                            <td style="width: 60%; vertical-align: top; padding-left: 5mm; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 3mm;">
                                                <p style="margin: 2mm 0; font-size: 11px;">Kode Barang : <strong>{{ $OrderDtl->matl_code }}</strong></p>
                                                <p style="margin: 3mm 0; font-size: 13px; font-weight: bold;">
                                                    <strong>{{ $masterService->GetMatlCategory1String($OrderDtl->Material->jwl_category1) }}
                                                        {{ $masterService->GetMatlCategory2String($OrderDtl->Material->jwl_category2) }}</strong>
                                                </p>
                                                <p style="margin: 2mm 0; font-size: 11px;">Berat : {{ $OrderDtl->Material->jwl_wgt_gold }} Gram</p>
                                                <p style="margin: 2mm 0; font-size: 11px;">Kemurnian : {{ $masterService->GetMatlJewelPurityString($OrderDtl->Material->jwl_carat) }}</p>
                                                <p style="margin: 2mm 0; font-size: 11px;">Bahan : {{ $OrderDtl->Material->descr }}</p>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Remarks Section -->
                                    <table style="width: 100%; border-collapse: collapse; max-height: 15mm;">
                                        <tr>
                                            <td style="font-size: 13px; font-weight: bold; border-bottom: 1px solid #ddd; padding: 1mm 0;" colspan="2">KETERANGAN:</td>
                                        </tr>
                                        <tr>
                                            <td style="width: 60%; font-size: 10px; vertical-align: top; padding: 1mm 0;">
                                                <ul style="margin: 0; padding-left: 6mm; line-height: 1.2;">
                                                    @foreach ($printRemarks as $remark)
                                                        @if ($remark['checked'])
                                                            <li style="margin-bottom: 0.5mm;">{{ $remark['label'] }}</li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            </td>
                                            <td style="width: 40%; text-align: right; vertical-align: top; padding: 1mm 0;">
                                                @if ($isShowPrice)
                                                    <p style="font-size: 16px; font-weight: bold; color: #333; margin: 0 0 1mm 0;">{{ rupiah(ceil($OrderDtl->price)) }}</p>
                                                    <p style="font-size: 9px; font-style: italic; color: #666; margin: 0; line-height: 1.1;">{{ terbilang($OrderDtl->price) }}</p>
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
    <style>
        /* Reset default margins and paddings */
        @page {
            size: A5 landscape;
            margin: 0mm;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>

    <script>
        function printInvoice() {
            window.print();
        }
    </script>
</div>
