<div>
    @php
    @endphp

    {{-- ============================ PAGE WRAPPER ============================ --}}
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">

        {{-- ============================ FILTERS ============================ --}}
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
                        <div class="col-md-2">
                            <x-ui-dropdown-search label="Customer" model="filterPartner"
                                optionValue="id" :query="$ddPartner['query']" :optionLabel="$ddPartner['optionLabel']" :placeHolder="$ddPartner['placeHolder']"
                                :selectedValue="$filterPartner" required="false" action="Edit" enabled="true"
                                type="int" onChanged="onPartnerChanged"/>
                        </div>
                        <div class="col-md-2">
                            <x-ui-dropdown-select label="Brand" model="filterBrand" :options="$this->getBrandOptions()" action="Edit" />
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
        {{-- ============================ /FILTERS ============================ --}}

        {{-- ============================ REPORT AREA ============================ --}}
        <div id="print">
            <style>
                /* ---------- BASE (SCREEN) ---------- */
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
                    /* margin-bottom: 6px; */
                    font-size: 12px;
                }

                #print .filters-line {
                    text-align: left;
                    /* margin-bottom: 16px; */
                    font-size: 12px;
                }

                #print .nota-block {
                    /* border: 1px solid #999; */
                    /* margin-bottom: 25px; */
                }

                #print table {
                    /* border-collapse: collapse; */
                    width: 100%;
                    /* border: 1px solid #000; */
                }

                #print th,
                #print td {
                    padding: 6px 8px;
                    font-size: 11px;
                    vertical-align: middle;
                    /* border: 1px solid #ddd; */
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

                /* ---------- PRINT STYLES ---------- */
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
                        border: none !important;
                        vertical-align: middle !important;
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
                        /* margin: 1cm 1cm 2cm 1cm; */
                        /* size: F4 landscape; */
                    }
                }
            </style>

            <div class="report-container">
                <div class="report-title">
                    <h3>Laporan Pembelian per Nota</h3>
                    <span style="font-size:12px;color:#666;">Page 1 of 1</span>
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
                    // Formatter tanggal sesuai contoh "30-Dec-2024"
                    function formatDate($date)
                    {
                        if (!$date) {
                            return '';
                        }
                        return \Carbon\Carbon::parse($date)->format('d-M-Y');
                    }
                @endphp

                {{-- ============================ TABLE REPORT ============================ --}}
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #000;">
                            <th style="padding: 6px 8px; font-weight: bold; text-align: left;">Tgl. Kirim</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: left;">No. Nota</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: left;">Nama Supplier</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: left;">Kode Brg.</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: left;">Nama Barang</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: right;">Qty</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: right;">Harga</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: right;">Disc.</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: right;">Total</th>
                            <th style="padding: 6px 8px; font-weight: bold; text-align: right;">Ppn</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results ?? [] as $nota)
                        @php
                            $subTotalAmount = 0;
                            $totalPpn = 0;
                            foreach ($nota['items'] ?? [] as $it) {
                                $subTotalAmount += $it['total'] ?? 0;
                                $totalPpn += $it['ppn'] ?? 0;
                            }
                            $grand = $subTotalAmount + $totalPpn;
                        @endphp

                        @foreach ($nota['items'] ?? [] as $index => $item)
                            <tr>
                                @if($index == 0)
                                    <td style="padding: 6px 8px; vertical-align: top;" rowspan="{{ count($nota['items']) }}">
                                        {{ isset($nota['tgl_kirim']) ? formatDate($nota['tgl_kirim']) : '' }}
                                    </td>
                                    <td style="padding: 6px 8px; vertical-align: top;" rowspan="{{ count($nota['items']) }}">
                                        {{ $nota['no_nota'] ?? '-' }}
                                    </td>
                                    <td style="padding: 6px 8px; vertical-align: top;" rowspan="{{ count($nota['items']) }}">
                                        {{ $nota['nama_supplier'] ?? '-' }}
                                    </td>
                                @endif
                                <td style="padding: 6px 8px;">{{ $item['kode_brg'] ?? '' }}</td>
                                <td style="padding: 6px 8px;">{{ $item['nama_barang'] ?? '' }}</td>
                                <td style="padding: 6px 8px; text-align: right;">{{ number_format($item['qty'] ?? 0, 0, ',', '.') }}</td>
                                <td style="padding: 6px 8px; text-align: right;">{{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</td>
                                <td style="padding: 6px 8px; text-align: right;">{{ number_format($item['disc'] ?? 0, 2, ',', '.') }}</td>
                                <td style="padding: 6px 8px; text-align: right;">{{ number_format($item['total'] ?? 0, 0, ',', '.') }}</td>
                                <td style="padding: 6px 8px; text-align: right;">{{ number_format($item['ppn'] ?? 0, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach

                        {{-- Baris subtotal untuk setiap nota --}}
                        <tr style="font-weight: bold; background-color: #f8f9fa;">
                            <td style="padding: 6px 8px;"></td>
                            <td style="padding: 6px 8px;"></td>
                            <td style="padding: 6px 8px;"></td>
                            <td style="padding: 6px 8px;"></td>
                            <td style="padding: 6px 8px; text-align: right;">Subtotal:</td>
                            <td style="padding: 6px 8px;"></td>
                            <td style="padding: 6px 8px;"></td>
                            <td style="padding: 6px 8px;"></td>
                            <td style="padding: 6px 8px; text-align: right;">{{ number_format($subTotalAmount, 0, ',', '.') }}</td>
                            <td style="padding: 6px 8px; text-align: right;">{{ number_format($totalPpn, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                {{-- ============================ /TABLE REPORT ============================ --}}

                {{-- Footer Crystal Report style (opsional) --}}
                {{--
                <div style="margin-top: 20px; font-size: 10px; color: #666;">
                    <div style="float: left;">E:\Users\...\RptJualPerNota.rpt</div>
                    <div style="text-align: center;">Hal: 1</div>
                    <div style="float: right;">{{ now()->format('m/d/Y') }}</div>
                    <div style="clear: both;"></div>
                </div>
                --}}
            </div>
        </div>
        {{-- ============================ /REPORT AREA ============================ --}}
    </x-ui-page-card>

    {{-- ============================ SCRIPTS ============================ --}}
    <script>
        function printReport() {
            window.print();
        }
    </script>
    {{-- ============================ /SCRIPTS ============================ --}}
</div>
