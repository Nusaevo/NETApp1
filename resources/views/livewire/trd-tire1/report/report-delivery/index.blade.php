<div>
    @php
    @endphp
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">

        <div class="card mb-4 no-print">
            <div class="card-body">
                <div class="container mb-2 mt-2">
                    <div class="row align-items-end">
                        <div class="col-md-2">
                            <x-ui-text-field label="Tanggal Awal:" model="startCode" type="date" action="Edit" />
                        </div>
                        <div class="col-md-2">
                            <x-ui-text-field label="Tanggal Akhir:" model="endCode" type="date" action="Edit" />
                        </div>
                        <div class="col-md-3">
                            <x-ui-dropdown-search label="Supplier" model="filterPartner" optionValue="id"
                                :query="$ddPartner['query']" :optionLabel="$ddPartner['optionLabel']" :placeHolder="$ddPartner['placeHolder']" :selectedValue="$filterPartner" required="false"
                                action="Edit" enabled="true" type="int" onChanged="onPartnerChanged" />
                        </div>
                        <div class="col-md-3">
                            <x-ui-dropdown-search label="Brand" model="filterBrand" optionValue="brand"
                                :query="$ddBrand['query']" optionLabel="{brand}" :placeHolder="$ddBrand['placeHolder']" :selectedValue="$filterBrand"
                                required="false" action="Edit" enabled="true" type="string" searchOnSpace="true" />
                        </div>
                        <div class="col-md-2">
                            <x-ui-button clickEvent="search" button-name="View" loading="true" action="Edit"
                                cssClass="btn-primary w-100 mb-2" />
                            <button type="button" class="btn btn-light text-capitalize border-0 w-100"
                                onclick="printReport()">
                                <i class="fas fa-print text-primary"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="print">
            <style>
                #print {
                    font-family: 'Calibri', Arial, sans-serif;
                    color: #000;
                    font-size: 12px;
                }

                #print .report-container {
                    max-width: 2480px;
                    margin: auto;
                    padding: 20px;
                }

                #print .report-title {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    /* margin-bottom: 6px; */
                }

                #print .report-title h3 {
                    font-weight: bold;
                    margin: 0;
                }

                #print .period {
                    text-align: left;
                    margin-bottom: 6px;
                    font-size: 12px;
                }

                #print .filters-line {
                    text-align: left;
                    margin-bottom: 4px;
                    font-size: 12px;
                }

                #print .nota-block {
                    /* border: 1px solid #999; */
                    /* margin-bottom: 25px; */
                }

                #print table {
                    border-collapse: collapse;
                    width: 100%;
                    border: 1px solid #000;
                }

                #print th,
                #print td {
                    padding: 6px 8px;
                    font-size: 11px;
                    vertical-align: middle;
                    border: 1px solid #000;
                }

                #print .head-yellow.value {
                    font-weight: bold;
                }

                /* Angka rata kanan */
                .text-right {
                    text-align: right;
                }

                .text-center {
                    text-align: center;
                }

                .text-left {
                    text-align: left;
                }

                @media print {
                    body {
                        background: #fff !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .no-print {
                        display: none !important;
                    }

                    #print .card,
                    #print .card-body {
                        box-shadow: none !important;
                        border: none !important;
                        padding: 0 !important;
                        background: transparent !important;
                    }

                    #print .report-container {
                        margin: 0 auto !important;
                        /* padding: 0 10px !important; */
                        max-width: none !important;
                    }

                    #print table {
                        margin-left: auto !important;
                        margin-right: auto !important;
                        width: 100% !important;
                    }

                    #print th,
                    #print td {
                        /* padding: 4px 6px !important; */
                        font-size: 11px !important;
                        border: 1px solid #000 !important;
                        vertical-align: middle !important;
                    }

                    #print th:not([style*="text-align: right"]),
                    #print td:not([style*="text-align: right"]) {
                        text-align: left !important;
                    }

                    #print h3,
                    #print h4 {
                        /* margin: 10px 0 !important; */
                        font-weight: bold !important;
                    }

                    #print p {
                        /* margin: 5px 0 !important; */
                    }

                    @page {
                        margin: 1cm 0cm 1cm 0cm;
                        /* size: F4 landscape; */
                    }
                }
            </style>

            <div class="report-container">
                <div class="report-title">
                    <h3>{!! $menuName !!}</h3>
                </div>

                <p class="period">
                    Periode:
                    {{ $startCode ? \Carbon\Carbon::parse($startCode)->format('d-M-Y') : '-' }}
                    s/d
                    {{ $endCode ? \Carbon\Carbon::parse($endCode)->format('d-M-Y') : '-' }}
                </p>

                @if ($filterPartner || $filterBrand)
                    <p class="filters-line">
                        @php
                            $filters = [];
                            if ($filterPartner) {
                                $filters[] =
                                    \App\Models\TrdTire1\Master\Partner::find($filterPartner)->name ??
                                    'Supplier Tidak Ditemukan';
                            }
                            if ($filterBrand) {
                                $filters[] = 'Brand: ' . $filterBrand;
                            }
                        @endphp
                        {{ implode(' | ', $filters) }}
                    </p>
                @endif

                @php
                    function formatDate($date)
                    {
                        if (!$date) {
                            return '';
                        }
                        return \Carbon\Carbon::parse($date)->format('d-M-Y');
                    }
                @endphp
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
                    <thead>
                        <tr>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: left; border: 1px solid #000;">Tgl. SJ</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: left; border: 1px solid #000;">Tgl. Terima</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: left; border: 1px solid #000;">No. Surat Jalan</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: left; border: 1px solid #000;">Nama Supplier</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: left; border: 1px solid #000;">Kode Brg.</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: left; border: 1px solid #000;">Nama Barang</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: right; border: 1px solid #000;">Qty</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: right; border: 1px solid #000;">Qty BD</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: right; border: 1px solid #000;">Qty BL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalQty = 0;
                            $totalQtyBD = 0;
                            $totalQtyBL = 0;
                        @endphp
                        @foreach ($results ?? [] as $nota)
                            @foreach ($nota['items'] ?? [] as $index => $item)
                                @php
                                    $isIRC = isset($item['brand']) && strtoupper($item['brand']) === 'IRC';
                                    $qtyBD = 0;
                                    $qtyBL = 0;

                                    if ($isIRC) {
                                        if (isset($item['category']) && stripos($item['category'], 'BAN DALAM') !== false) {
                                            $qtyBD = $item['qty'] ?? 0;
                                        }
                                        if (isset($item['category']) && stripos($item['category'], 'BAN LUAR') !== false) {
                                            $qtyBL = $item['qty'] ?? 0;
                                        }
                                    } else {
                                        $totalQty += $item['qty'] ?? 0;
                                    }

                                    $totalQtyBD += $qtyBD;
                                    $totalQtyBL += $qtyBL;
                                @endphp
                                <tr>
                                    @if ($index == 0)
                                        <td style="padding: 6px 8px; vertical-align: top; border: 1px solid #000; text-align: left;"
                                            rowspan="{{ count($nota['items']) }}">
                                            {{ isset($nota['tgl_sj']) ? formatDate($nota['tgl_sj']) : '' }}
                                        </td>
                                        <td style="padding: 6px 8px; vertical-align: top; border: 1px solid #000; text-align: left;"
                                            rowspan="{{ count($nota['items']) }}">
                                            {{ isset($nota['tgl_terima']) ? formatDate($nota['tgl_terima']) : '' }}
                                        </td>
                                        <td style="padding: 6px 8px; vertical-align: top; border: 1px solid #000; text-align: left;"
                                            rowspan="{{ count($nota['items']) }}">
                                            {{ $nota['no_nota'] ?? '-' }}
                                        </td>
                                        <td style="padding: 6px 8px; vertical-align: top; border: 1px solid #000; text-align: left;"
                                            rowspan="{{ count($nota['items']) }}">
                                            {{ $nota['nama_supplier'] ?? '-' }}
                                        </td>
                                    @endif
                                    <td style="padding: 6px 8px; border: 1px solid #000; text-align: left;">{{ $item['kode_brg'] ?? '' }}</td>
                                    <td style="padding: 6px 8px; border: 1px solid #000; text-align: left;">{{ $item['nama_barang'] ?? '' }}</td>
                                    <td style="padding: 6px 8px; text-align: right; border: 1px solid #000;">
                                        {{ $isIRC ? '' : number_format($item['qty'] ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td style="padding: 6px 8px; text-align: right; border: 1px solid #000;">
                                        {{ $qtyBD > 0 ? number_format($qtyBD, 0, ',', '.') : '' }}
                                    </td>
                                    <td style="padding: 6px 8px; text-align: right; border: 1px solid #000;">
                                        {{ $qtyBL > 0 ? number_format($qtyBL, 0, ',', '.') : '' }}
                                    </td>
                                </tr>
                            @endforeach

                        @endforeach
                        {{-- Baris Total --}}
                        <tr style="font-weight: bold; background-color: #f8f9fa; border-top: 2px solid #000;">
                            <td style="padding: 6px 8px; border: 1px solid #000;" colspan="6">TOTAL</td>
                            <td style="padding: 6px 8px; text-align: right; border: 1px solid #000;">
                                {{ $totalQty > 0 ? number_format($totalQty, 0, ',', '.') : '' }}
                            </td>
                            <td style="padding: 6px 8px; text-align: right; border: 1px solid #000;">
                                {{ $totalQtyBD > 0 ? number_format($totalQtyBD, 0, ',', '.') : '' }}
                            </td>
                            <td style="padding: 6px 8px; text-align: right; border: 1px solid #000;">
                                {{ $totalQtyBL > 0 ? number_format($totalQtyBL, 0, ',', '.') : '' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </x-ui-page-card>
    <script>
        function printReport() {
            window.print();
        }
    </script>
</div>
