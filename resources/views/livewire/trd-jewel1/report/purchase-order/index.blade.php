<div>
    @php
    use App\Services\TrdJewel1\Master\MasterService;

    $masterService = new MasterService();
    @endphp

    <!-- Print CSS Styles -->
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                font-family: 'Calibri', Arial, sans-serif;
                font-size: 12px;
                line-height: 1.3;
            }

            .d-print-none {
                display: none !important;
            }

            #print {
                display: block !important;
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 8px !important;
                page-break-inside: avoid;
            }

            .page-break {
                page-break-before: always !important;
            }

            .print-table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10px !important;
                margin: 0 !important;
            }

            .print-table th,
            .print-table td {
                border: 1px solid #000 !important;
                padding: 4px 6px !important;
                text-align: center !important;
                vertical-align: middle !important;
                word-wrap: break-word !important;
                height: auto !important;
                min-height: 130px !important;
            }

            .print-table th {
                background-color: #f8f9fa !important;
                font-weight: bold !important;
                font-size: 11px !important;
            }

            .print-table td {
                font-size: 9px !important;
            }

            .print-table .col-no { width: 4% !important; }
            .print-table .col-code { width: 10% !important; }
            .print-table .col-foto { width: 12% !important; }
            .print-table .col-descr { width: 30% !important; }
            .print-table .col-modal { width: 14% !important; }
            .print-table .col-jual { width: 30% !important; }

            .print-table img {
                max-width: 120px !important;
                max-height: 120px !important;
                object-fit: contain !important;
                display: block !important;
                margin: 0 auto !important;
            }

            .print-table .col-foto {
                padding: 2px !important;
                height: 135px !important;
            }

            .print-header {
                text-align: center;
                margin-bottom: 15px;
                font-weight: bold;
                font-size: 16px;
            }

            .print-header small {
                font-size: 11px !important;
                font-weight: normal !important;
                color: #666 !important;
            }

            .print-table tr {
                height: 140px !important;
                page-break-inside: avoid !important;
            }
        }

        /* Screen styles */
        @media screen {
            #print {
                display: block;
            }

            .print-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 14px;
                margin: 0;
            }

            .print-table th,
            .print-table td {
                border: 1px solid #dee2e6;
                padding: 6px 10px;
                text-align: center;
                vertical-align: middle;
                word-wrap: break-word;
                height: auto;
                min-height: 130px;
            }

            .print-table th {
                background-color: #f8f9fa;
                font-weight: bold;
                font-size: 14px;
                height: auto;
            }

            .print-table td {
                font-size: 13px;
            }

            .print-table .col-no { width: 4%; }
            .print-table .col-code { width: 10%; }
            .print-table .col-foto { width: 12%; }
            .print-table .col-descr { width: 30%; }
            .print-table .col-modal { width: 14%; }
            .print-table .col-jual { width: 30%; }

            .print-table img {
                max-width: 120px;
                max-height: 120px;
                object-fit: contain;
                display: block;
                margin: 0 auto;
            }

            .print-table tr {
                height: 140px;
            }
        }
    </style>

    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}" class="d-print-none">
        <x-ui-expandable-card id="ReportFilterCard" title="Filter" :isOpen="true">
                <div class="card-body">
                    <div class="row">
                        <x-ui-dropdown-select label="Cari Kategori Barang" model="category" :options="$materialCategories1" action="Edit" />
                        <x-ui-text-field label="Kode Awal:" model="startCode" type="number" action="Edit" />
                        <x-ui-text-field label="Kode Akhir:" model="endCode" type="number" action="Edit"/>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <div>
                        <x-ui-button clickEvent="search" button-name="Search" loading="true" action="Edit" cssClass="btn-primary" />
                        <button type="button" class="btn btn-light text-capitalize border-0" onclick="printReport()">
                            <i class="fas fa-print text-primary"></i> Print
                        </button>
                </div>

        </x-ui-expandable-card>

        <!-- Print Area -->
        <div id="print">

            <table class="print-table">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-code">Code</th>
                        <th class="col-foto">Foto</th>
                        <th class="col-descr">Descr</th>
                        <th class="col-modal">Modal</th>
                        <th class="col-jual">Jual</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $index => $res)
                        @php
                            // Handle both object and array data types
                            $item = is_object($res) ? $res : (object) $res;
                        @endphp
                        <tr>
                            <td class="col-no">{{ $index + 1 }}</td>
                            <td class="col-code">{{ $item->material_code }}</td>
                            <td class="col-foto">
                                @if(!empty($item->file_url))
                                    @php
                                        $imageUrl = config('app.storage_url') . "/TrdJewel1" . '/' . ltrim($item->file_url, '/');
                                    @endphp
                                    <img src="{{ $imageUrl }}" alt="Material" style="width: 120px; height: 120px; object-fit: contain;">
                                @endif
                            </td>
                            <td class="col-descr" style="text-align: left;">
                                @if(!empty($item->category2))
                                    <strong>{{ $masterService->GetMatlCategory1String($item->category) }}
                                    {{ $masterService->GetMatlCategory2String($item->category2) }}</strong>
                                @endif

                                @if(!empty($item->material_gold))
                                    <br>{{ numberFormat($item->material_gold, 2) }} Gram
                                @endif

                                @if(!empty($item->material_carat))
                                    <br>{{ $masterService->GetMatlJewelPurityString($item->material_carat) }}
                                @endif

                                @if(!empty($item->material_descr))
                                    <br>{{ $item->material_descr }}
                                @endif
                            </td>
                            <td class="col-modal">{{ dollar($item->price) }}</td>
                            <td class="col-jual" style="text-align: left;">
                                @if(!empty($item->no_nota))
                                    <strong>No Nota:</strong> {{ $item->no_nota }}<br>
                                    <strong>Customer:</strong> {{ $item->partner_name }}<br>
                                    <strong>Harga Jual:</strong> {{ rupiah($item->selling_price) }}<br>
                                    <strong>Tanggal:</strong> {{ $item->tr_date }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Screen View Table (Hidden when printing) -->


    </x-ui-page-card>
</div>

<script>
    function printReport() {
        // Pastikan semua gambar telah dimuat sebelum print
        const images = document.querySelectorAll('#print img');
        let loadedImages = 0;

        if (images.length === 0) {
            // Tidak ada gambar, langsung print
            window.print();
            return;
        }

        const checkAllImagesLoaded = () => {
            loadedImages++;
            if (loadedImages === images.length) {
                // Semua gambar telah dimuat, sekarang print
                setTimeout(() => {
                    window.print();
                }, 100);
            }
        };

        images.forEach(img => {
            if (img.complete) {
                checkAllImagesLoaded();
            } else {
                img.addEventListener('load', checkAllImagesLoaded);
                img.addEventListener('error', checkAllImagesLoaded); // Handle error case
            }
        });
    }
</script>
