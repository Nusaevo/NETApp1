<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        {{-- Filter Frame --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="container mb-2 mt-2">
                    <div class="row align-items-start">
                        <!-- Grid Kiri: Filter Fields (Maksimal 3 dropdown) -->
                        <div class="col-md-10">
                            <div class="row align-items-end">
                                <!-- Row 1: Date fields -->
                                <div class="col-md-4">
                                    <x-ui-text-field label="Tanggal Awal:" model="startCode" type="date" action="Edit" />
                                </div>
                                <div class="col-md-4">
                                    <x-ui-text-field label="Tanggal Akhir:" model="endCode" type="date" action="Edit" />
                                </div>
                                <div class="col-md-4">
                                    <x-ui-text-field label="Nomor Nota:" model="filterTrCode" type="text" action="Edit" />
                                </div>
                            </div>

                            <div class="row align-items-end mt-3">
                                <!-- Row 2: Dropdown filters (maksimal 3) -->
                                <div class="col-md-4">
                                    <x-ui-dropdown-search label="Customer" model="filterPartner"
                                        optionValue="id" :query="$ddPartner['query']" :optionLabel="$ddPartner['optionLabel']" :placeHolder="$ddPartner['placeHolder']"
                                        :selectedValue="$filterPartner" required="false" action="Edit" enabled="true"
                                        type="int" onChanged="onPartnerChanged"/>
                                </div>
                                <div class="col-md-4">
                                    <x-ui-dropdown-search label="Kode Barang" model="filterMaterialId"
                                        optionValue="id" :query="$materialQuery" optionLabel="code,name" placeHolder="Ketik untuk cari barang..."
                                        :selectedValue="$filterMaterialId" required="false" action="Edit" enabled="true"
                                        type="int" onChanged="onMaterialChanged" />
                                </div>
                                <div class="col-md-4">
                                    <x-ui-dropdown-select label="Status" model="filterStatus" :options="$this->getStatusOptions()" action="Edit" />
                                </div>
                            </div>

                            <div class="row align-items-end mt-3">
                                <!-- Row 3: Tipe Penjualan -->
                                <div class="col-md-4">
                                    <x-ui-dropdown-select label="Tipe Penjualan" model="filterSalesType" :options="$this->getSalesTypeOptions()" action="Edit" />
                                </div>
                                <div class="col-md-8">
                                    <!-- Empty space for future filters if needed -->
                                </div>
                            </div>
                        </div>

                        <!-- Grid Kanan: Tombol Action (Kecil) -->
                        <div class="col-md-2">
                            <div class="d-flex flex-column gap-2">
                                <x-ui-button clickEvent="search" button-name="View" loading="true" action="Edit"
                                    cssClass="btn-primary w-100" />
                                <button type="button" class="btn btn-light text-capitalize border-0 w-100"
                                    onclick="printReport()">
                                    <i class="fas fa-print text-primary"></i> Print
                                </button>
                                <x-ui-button clickEvent="downloadExcel" button-name="Download Excel" loading="true" action="Edit"
                                    cssClass="btn-success w-100" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- End Filter Frame --}}

        <div id="print">
            <div>
                <style>
                    @media print {
                        body {
                            background: #fff !important;
                            font-family: 'Calibri', Arial, sans-serif !important;
                            transform: rotate(0deg);
                        }
                        html {
                            transform: rotate(0deg);
                        }
                        #print .card {
                            box-shadow: none !important;
                            border: none !important;
                            background: transparent !important;
                        }
                        #print .card-body {
                            padding: 0 !important;
                            margin: 0 !important;
                        }
                        #print .container {
                            margin: 0 auto !important;
                            padding: 0 !important;
                            max-width: none !important;
                        }
                        #print table {
                            margin-left: auto !important;
                            margin-right: auto !important;
                            border-collapse: collapse !important;
                            width: 100% !important;
                        }
                        #print th, #print td {
                            padding: 4px 6px !important;
                            font-size: 11px !important;
                            border: none !important;
                            vertical-align: middle !important;
                            color: #000 !important;
                        }
                        #print td[style*="border-top"] {
                            border-top: 1px solid #000 !important;
                        }
                        #print th {
                            background: transparent !important;
                            font-weight: bold !important;
                            text-align: left !important;
                        }
                        #print h3, #print h4 {
                            margin: 0 !important;
                            font-weight: bold !important;
                            color: #000 !important;
                        }
                        #print p {
                            margin: 5px 0 !important;
                        }
                        @page {
                            margin: 0.5cm 1cm 1cm 1cm;
                            size: A4 landscape;
                            width: 330mm;
                            height: 210mm;
                        }
                        @media print and (orientation: landscape) {
                            @page {
                                margin: 0.5cm 1cm 1cm 1cm;
                                size: A4 landscape;
                            }
                        }
                        .btn, .card-header, .card-footer, .page-info {
                            display: none !important;
                        }
                        #print {
                            font-family: 'Calibri', Arial, sans-serif !important;
                            font-size: 14px !important;
                            color: #000 !important;
                        }
                        #print * {
                            color: #000 !important;
                        }
                        #print .container {
                            margin-top: 0 !important;
                            padding-top: 0 !important;
                        }
                        #print div[style*="max-width:2480px"] {
                            padding-top: 0px !important;
                            padding: 0px 20px 20px 20px !important;
                            max-width: 100% !important;
                        }
                        #print table {
                            width: 100% !important;
                            max-width: 100% !important;
                        }
                        #print {
                            width: 100% !important;
                            max-width: 100% !important;
                        }
                        #print .container {
                            width: 100% !important;
                            max-width: 100% !important;
                        }
                    }
                </style>
                <div class="card print-page">
                    <div class="card-body">
                        <div class="container mb-3">
                            <div style="max-width:2480px; margin:auto; padding:5px 20px 20px 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <h3 style="font-weight:bold; margin:0;">
                                        DAFTAR NOTA JUAL
                                    </h3>
                                    <span style="font-size: 12px; color: #666;">
                                        Page 1 of 1
                                    </span>
                                </div>
                                <p style="text-align:left; margin-bottom:5px; font-size: 12px;">
                                    Periode: {{ $startCode ? \Carbon\Carbon::parse($startCode)->format('d-M-Y') : '-' }}
                                    s/d {{ $endCode ? \Carbon\Carbon::parse($endCode)->format('d-M-Y') : '-' }}
                                </p>
                                @if($filterPartner || $filterStatus || $filterMaterialId || $filterSalesType || $filterTrCode)
                                    <p style="text-align:left; margin-bottom:20px; font-size: 12px;">
                                        @php
                                            $filters = [];
                                            if($filterPartner) {
                                                $filters[] = \App\Models\TrdTire1\Master\Partner::find($filterPartner)->name ?? 'Customer Tidak Ditemukan';
                                            }
                                            if($filterMaterialId) {
                                                $material = \App\Models\TrdTire1\Master\Material::find($filterMaterialId);
                                                $filters[] = 'Kode: ' . ($material ? $material->code : 'Material Tidak Ditemukan');
                                            }
                                            if($filterStatus) {
                                                $filters[] = ucfirst(str_replace('_', ' ', $filterStatus));
                                            }
                                            if($filterTrCode) {
                                                $filters[] = 'Nota: ' . $filterTrCode;
                                            }
                                        @endphp
                                        {{ implode(' | ', $filters) }}
                                    </p>
                                @endif

                                @php
                                    // Format tanggal untuk display
                                    function formatDate($date) {
                                        if (!$date) return '';
                                        return \Carbon\Carbon::parse($date)->format('d-M-y');
                                    }
                                @endphp

                                <table style="width:100%; border-collapse:collapse; font-family: 'Calibri', Arial, sans-serif;">
                                    <thead>
                                        <tr style="border-bottom: 1px solid #000;">
                                            <th style="text-align:left; padding:4px 6px; font-weight:bold; font-size:11px;">No. Nota</th>
                                            <th style="text-align:left; padding:4px 6px; font-weight:bold; font-size:11px;">Tgl Nota</th>
                                            <th style="text-align:left; padding:4px 6px; font-weight:bold; font-size:11px;">Nama Customer</th>
                                            <th style="text-align:left; padding:4px 6px; font-weight:bold; font-size:11px;">Kode</th>
                                            <th style="text-align:left; padding:4px 6px; font-weight:bold; font-size:11px;">Nama Barang</th>
                                            <th style="text-align:right; padding:4px 6px; font-weight:bold; font-size:11px;">Qty</th>
                                            <th style="text-align:right; padding:4px 6px; font-weight:bold; font-size:11px;">Harga</th>
                                            <th style="text-align:right; padding:4px 6px; font-weight:bold; font-size:11px;">% Disc</th>
                                            <th style="text-align:right; padding:4px 6px; font-weight:bold; font-size:11px;">Total</th>
                                            <th style="text-align:left; padding:4px 6px; font-weight:bold; font-size:11px;">S</th>                                            <th style="text-align:left; padding:4px 6px; font-weight:bold; font-size:11px;">Wajib Pajak</th>
                                            <th style="text-align:left; padding:4px 6px; font-weight:bold; font-size:11px; width: 60px;">Tgl Kirim</th>
                                            <th style="text-align:left; padding:4px 6px; font-weight:bold; font-size:11px; width: 60px;">Tgl Tagih</th>
                                            <th style="text-align:left; padding:4px 6px; font-weight:bold; font-size:11px; width: 60px;">Tgl Lunas</th>
                                            {{-- <th style="text-align:left; padding:4px 6px; font-weight:bold; font-size:11px;">Wajib Pajak</th> --}}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($results as $nota)
                                            @php
                                                $isFirstItem = true;
                                                $subTotalQty = 0;
                                                $subTotalAmount = 0;
                                        @endphp
                                            @foreach ($nota['items'] as $item)
                                            @php
                                                    $subTotalQty += $item['qty'];
                                                    $subTotalAmount += $item['total'];
                                            @endphp
                                            <tr>
                                                    @if ($isFirstItem)
                                                        <td style="text-align:left; padding:4px 6px; font-size:11px;">{{ $nota['no_nota'] }}</td>
                                                        <td style="text-align:left; padding:4px 6px; font-size:11px;">{{ formatDate($nota['tgl_nota']) }}</td>
                                                        <td style="text-align:left; padding:4px 6px; font-size:11px;">{{ $item['customer_name'] }}</td>
                                                    @else
                                                        <td style="padding:4px 6px;"></td>
                                                        <td style="padding:4px 6px;"></td>
                                                        <td style="padding:4px 6px;"></td>
                                                    @endif
                                                    <td style="text-align:left; padding:4px 6px; font-size:11px;">{{ $item['kode'] }}</td>
                                                    <td style="text-align:left; padding:4px 6px; font-size:11px;">{{ $item['nama_barang'] }}</td>
                                                    <td style="text-align:right; padding:4px 6px; font-size:11px;">{{ number_format($item['qty'], 0) }}</td>
                                                    <td style="text-align:right; padding:4px 6px; font-size:11px;">{{ number_format($item['harga'], 0, ',', '.') }}</td>
                                                    <td style="text-align:right; padding:4px 6px; font-size:11px;">{{ number_format($item['disc'], 0) }}</td>
                                                    <td style="text-align:right; padding:4px 6px; font-size:11px;">{{ number_format($item['total'], 0, ',', '.') }}</td>
                                                    @if ($isFirstItem)
                                                    <td style="text-align:left; padding:4px 6px; font-size:11px;">{{ $item['s'] }}</td>
                                                    <td style="text-align:left; padding:4px 6px; font-size:11px;">{{ $item['wajib_pajak'] }}</td>
                                                        <td style="text-align:left; padding:4px 6px; font-size:11px; width: 60px;">{{ formatDate($item['t_kirim']) }}</td>
                                                        <td style="text-align:left; padding:4px 6px; font-size:11px; width: 60px;">{{ formatDate($item['tgl_tagih']) }}</td>
                                                        <td style="text-align:left; padding:4px 6px; font-size:11px; width: 60px;">{{ formatDate($item['tgl_lunas']) }}</td>
                                                        {{-- <td style="text-align:left; padding:4px 6px; font-size:11px;">{{ $item['wajib_pajak'] }}</td> --}}
                                                    @else
                                                        <td style="padding:4px 6px;"></td>
                                                        <td style="padding:4px 6px;"></td>
                                                        <td style="padding:4px 6px;"></td>
                                                        <td style="padding:4px 6px;"></td>
                                                        <td style="padding:4px 6px;"></td>
                                                    @endif
                                                </tr>
                                                @php $isFirstItem = false; @endphp
                                                @endforeach
                                            {{-- Sub Total Row --}}
                                            <tr>
                                                <td style="padding:4px 6px;"></td>
                                                <td style="padding:4px 6px;"></td>
                                                <td style="padding:4px 6px;"></td>
                                                <td style="padding:4px 6px;"></td>
                                                <td style="text-align:right; padding:4px 6px; font-weight:bold; font-size:11px;">
                                                    Sub Total ({{ $nota['no_nota'] }}):
                                                </td>
                                                <td style="text-align:right; padding:4px 6px; font-weight:bold; font-size:11px; border-top: 1px solid #000;">{{ number_format($subTotalQty, 0) }}</td>
                                                <td style="padding:4px 6px; border-top: 1px solid #000;"></td>
                                                <td style="padding:4px 6px; border-top: 1px solid #000;"></td>
                                                <td style="text-align:right; padding:4px 6px; font-weight:bold; font-size:11px; border-top: 1px solid #000;">{{ number_format($subTotalAmount, 0, ',', '.') }}</td>
                                                <td colspan="5" style="padding:4px 6px;"></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                {{-- Footer --}}
                                {{-- <div style="margin-top: 20px; font-size: 10px; color: #666;">
                                    <div style="float: left;">E:\Users\Tirta\OneDrive\Documents\CT\RptJualPerNota.rpt</div>
                                    <div style="text-align: center;">Hal: 1</div>
                                    <div style="float: right;">08/18/2025</div>
                                    <div style="clear: both;"></div>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-ui-page-card>
</div>
