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
                            <x-ui-dropdown-search label="Supplier" model="filterPartner" optionValue="id"
                                :query="$ddPartner['query']" :optionLabel="$ddPartner['optionLabel']" :placeHolder="$ddPartner['placeHolder']" :selectedValue="$filterPartner" required="false"
                                action="Edit" enabled="true" type="int" onChanged="onPartnerChanged" />
                        </div>
                        <div class="col-md-2">
                            <x-ui-dropdown-search label="Brand" model="filterBrand" optionValue="brand"
                                :query="$ddBrand['query']" optionLabel="{brand}" :placeHolder="$ddBrand['placeHolder']" :selectedValue="$filterBrand"
                                required="false" action="Edit" enabled="true" type="string" searchOnSpace="true" />
                        </div>
                        <div class="col-md-2">
                            <x-ui-button clickEvent="search" button-name="View" loading="true" action="Edit"
                                cssClass="btn-primary w-100 mb-2" />
                            <button type="button" class="btn btn-light text-capitalize border-0 w-100 mb-2"
                                onclick="printReport()">
                                <i class="fas fa-print text-primary"></i> Print
                            </button>
                            <x-ui-button clickEvent="downloadExcel" button-name="Download Excel" loading="true"
                                action="Edit" cssClass="btn-success w-100" />
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
                    margin-bottom: 6px;
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
                    margin-bottom: 16px;
                    font-size: 12px;
                }

                #print .nota-block {
                    /* border: 1px solid #999; */
                    margin-bottom: 18px;
                }

                #print table {
                    border-collapse: collapse;
                    width: 100%;
                }

                #print th,
                #print td {
                    padding: 6px 8px;
                    font-size: 11px;
                    vertical-align: middle;
                    border: none;
                }

                /* Header kuning seperti contoh gambar */
                #print .head-yellow {
                    background: #fff6b1;
                    border: 1px solid #c9b84a;
                    border-bottom: 2px solid #8d7e2a;
                    font-weight: bold;
                    text-align: center;
                }

                #print .head-yellow.value {
                    font-weight: bold;
                }

                /* Baris judul kolom detail */
                #print .detail-head th {
                    /* text-align: right; */
                    font-weight: bold;
                    font-size: 11px;
                    border-top: 1px solid #000;
                    padding-top: 8px;
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
                        padding: 0 10px !important;
                        max-width: none !important;
                    }

                    #print table {
                        margin-left: auto !important;
                        margin-right: auto !important;
                        width: 100% !important;
                    }

                    #print th,
                    #print td {
                        padding: 4px 6px !important;
                        font-size: 11px !important;
                        border: none !important;
                        vertical-align: middle !important;
                    }

                    #print td[style*="border-top"] {
                        border-top: 1px solid #000 !important;
                    }

                    #print h3,
                    #print h4 {
                        margin: 10px 0 !important;
                        font-weight: bold !important;
                    }

                    #print p {
                        margin: 5px 0 !important;
                    }

                    @page {
                        margin: 1cm 1cm 2cm 1cm;
                        size: F4 landscape;
                    }
                }
            </style>

            <div class="report-container">
                <div class="report-title">
                    <h3>Laporan Order Barang</h3>
                    @if (!empty($allResults))
                        <span id="print-total" style="font-size:12px;color:#666; display: none;">
                            Total: {{ count($allResults) }} Nota
                        </span>
                    @endif
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
                    // Formatter tanggal pendek (sesuai contoh "04-Jan-25")
                    function formatDate($date)
                    {
                        if (!$date) {
                            return '';
                        }
                        return \Carbon\Carbon::parse($date)->format('d-M-y');
                    }
                @endphp

                @foreach ($results ?? [] as $nota)
                    @php
                        $subTotalAmount = 0;
                        foreach ($nota['items'] ?? [] as $it) {
                            $subTotalAmount += $it['total'] ?? 0;
                        }
                        $ppn = round($subTotalAmount * 0.11, 0);
                        $grand = $subTotalAmount + $ppn;
                    @endphp

                    <div class="nota-block">
                        <table>
                            <thead>
                                <!-- Baris judul kolom header kuning (8 kolom total) -->
                                <tr
                                    style="border-top:1px solid #000; border-right:1px solid #000; border-left:1px solid #000; border-bottom:none;">
                                    <td class="head-yellow" style="min-width:110px;">No. Nota</td>
                                    <td class="head-yellow" style="min-width:110px;">T. Order</td>
                                    <td class="head-yellow" colspan="3">Nama Supplier</td>
                                    <td class="head-yellow">Total</td>
                                    <td class="head-yellow">PPN</td>
                                    <td class="head-yellow">Total Nota</td>
                                </tr>

                                <!-- Baris nilai header kuning (8 kolom total, sama persis) -->
                                <tr
                                    style="border-top:none; border-right:1px solid #000; border-left:1px solid #000; border-bottom:1px solid #000;">
                                    <td class="head-yellow value text-center">{{ $nota['no_nota'] ?? '-' }}</td>
                                    <td class="head-yellow value text-center">
                                        {{ isset($nota['tgl_nota']) ? formatDate($nota['tgl_nota']) : '' }}</td>
                                    <td class="head-yellow value text-center" colspan="3">
                                        {{ $nota['nama_customer'] ?? '-' }}</td>
                                    <td class="head-yellow value text-right">
                                        {{ number_format($subTotalAmount, 0, ',', '.') }}</td>
                                    <td class="head-yellow value text-right">{{ number_format($ppn, 0, ',', '.') }}</td>
                                    <td class="head-yellow value text-right">{{ number_format($grand, 0, ',', '.') }}
                                    </td>
                                </tr>


                                {{-- Judul kolom detail --}}
                                <tr class="detail-head" style="border-bottom:1px solid #000;">
                                    <th class="text-left">Kode Brg.</th>
                                    <th class="text-left">Nama Barang</th>
                                    <th class="text-right">Order</th>
                                    <th class="text-right">Harga</th>
                                    <th class="text-right">Disc.</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($nota['items'] ?? [] as $item)
                                    <tr>
                                        <td class="text-left">{{ $item['kode'] ?? '' }}</td>
                                        <td class="text-left">{{ $item['nama_barang'] ?? '' }}</td>
                                        <td class="text-right">{{ number_format($item['qty'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-right">{{ number_format($item['harga'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="text-right">{{ number_format($item['disc'] ?? 0, 2, ',', '.') }}
                                        </td>
                                        <td class="text-right">{{ number_format($item['total'] ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

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

        {{-- Pagination Controls --}}
        @if(isset($paginator) && $paginator && method_exists($paginator, 'hasPages') && $paginator->hasPages())
            <div class="card mt-4 pagination-controls">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <label for="perPage" class="mb-0">Items per page:</label>
                                <select wire:model.live="perPage" id="perPage" class="form-select" style="width: auto;">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="text-muted">
                                    Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} results
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-end mb-0">
                                    {{-- Previous Page Link --}}
                                    @if ($paginator->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">&laquo;</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <button type="button" class="page-link" wire:click="gotoPage({{ $paginator->currentPage() - 1 }})" rel="prev">&laquo;</button>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @php
                                        $currentPage = $paginator->currentPage();
                                        $lastPage = $paginator->lastPage();
                                        $window = 2;

                                        $start = max(1, $currentPage - $window);
                                        $end = min($lastPage, $currentPage + $window);

                                        if ($currentPage <= $window + 1) {
                                            $end = min($lastPage, (2 * $window) + 2);
                                        } elseif ($currentPage >= $lastPage - $window) {
                                            $start = max(1, $lastPage - (2 * $window) - 1);
                                        }
                                    @endphp

                                    {{-- First Page --}}
                                    @if ($start > 1)
                                        <li class="page-item">
                                            <button type="button" class="page-link" wire:click="gotoPage(1)">1</button>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                    @endif

                                    {{-- Page Numbers in Window --}}
                                    @for ($page = $start; $page <= $end; $page++)
                                        @if ($page == $currentPage)
                                            <li class="page-item active" aria-current="page">
                                                <span class="page-link">{{ $page }}</span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <button type="button" class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                            </li>
                                        @endif
                                    @endfor

                                    {{-- Last Page --}}
                                    @if ($end < $lastPage)
                                        @if ($end < $lastPage - 1)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                        <li class="page-item">
                                            <button type="button" class="page-link" wire:click="gotoPage({{ $lastPage }})">{{ $lastPage }}</button>
                                        </li>
                                    @endif

                                    {{-- Next Page Link --}}
                                    @if ($paginator->hasMorePages())
                                        <li class="page-item">
                                            <button type="button" class="page-link" wire:click="gotoPage({{ $paginator->currentPage() + 1 }})" rel="next">&raquo;</button>
                                        </li>
                                    @else
                                        <li class="page-item disabled">
                                            <span class="page-link">&raquo;</span>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </x-ui-page-card>

    {{-- Store all data for print and modify print function --}}
    @if (!empty($allResults))
        <script>
            // Store all results for print
            window.reportAllDataPO = @json($allResults);

            function printReport() {
                const reportContainer = document.querySelector('#print .report-container');
                const printTotal = document.querySelector('#print .report-title span');

                if (!reportContainer || !window.reportAllDataPO) {
                    window.print();
                    return;
                }

                // Save current HTML
                const currentHtml = reportContainer.innerHTML;

                // Replace with all data for print
                let html = `
                    <div class="report-title">
                        <h3>Laporan Order Barang</h3>
                        <span style="font-size:12px;color:#666;">Total: ${window.reportAllDataPO.length} Nota</span>
                    </div>
                    <p class="period">
                        Periode: {{ $startCode ? \Carbon\Carbon::parse($startCode)->format('d-M-Y') : '-' }}
                        s/d {{ $endCode ? \Carbon\Carbon::parse($endCode)->format('d-M-Y') : '-' }}
                    </p>
                    @if ($filterPartner || $filterBrand)
                        <p class="filters-line">
                            @php
                                $filters = [];
                                if ($filterPartner) {
                                    $filters[] = \App\Models\TrdTire1\Master\Partner::find($filterPartner)->name ?? 'Supplier Tidak Ditemukan';
                                }
                                if ($filterBrand) {
                                    $filters[] = 'Brand: ' . $filterBrand;
                                }
                            @endphp
                            {{ implode(' | ', $filters) }}
                        </p>
                    @endif
                `;

                window.reportAllDataPO.forEach(nota => {
                    let subTotalAmount = 0;
                    nota.items.forEach(item => {
                        subTotalAmount += parseFloat(item.total) || 0;
                    });
                    const ppn = Math.round(subTotalAmount * 0.11);
                    const grand = subTotalAmount + ppn;

                    html += '<div class="nota-block"><table><thead>';
                    html += '<tr style="border-top:1px solid #000; border-right:1px solid #000; border-left:1px solid #000; border-bottom:none;">';
                    html += '<td class="head-yellow" style="min-width:110px;">No. Nota</td>';
                    html += '<td class="head-yellow" style="min-width:110px;">T. Order</td>';
                    html += '<td class="head-yellow" colspan="3">Nama Supplier</td>';
                    html += '<td class="head-yellow">Total</td>';
                    html += '<td class="head-yellow">PPN</td>';
                    html += '<td class="head-yellow">Total Nota</td>';
                    html += '</tr>';

                    html += '<tr style="border-top:none; border-right:1px solid #000; border-left:1px solid #000; border-bottom:1px solid #000;">';
                    html += '<td class="head-yellow value text-center">' + nota.no_nota + '</td>';
                    html += '<td class="head-yellow value text-center">' + (nota.tgl_nota ? new Date(nota.tgl_nota).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) : '') + '</td>';
                    html += '<td class="head-yellow value text-center" colspan="3">' + nota.nama_customer + '</td>';
                    html += '<td class="head-yellow value text-right">' + subTotalAmount.toLocaleString('id-ID') + '</td>';
                    html += '<td class="head-yellow value text-right">' + ppn.toLocaleString('id-ID') + '</td>';
                    html += '<td class="head-yellow value text-right">' + grand.toLocaleString('id-ID') + '</td>';
                    html += '</tr>';

                    html += '<tr class="detail-head" style="border-bottom:1px solid #000;">';
                    html += '<th class="text-left">Kode Brg.</th>';
                    html += '<th class="text-left">Nama Barang</th>';
                    html += '<th class="text-right">Order</th>';
                    html += '<th class="text-right">Harga</th>';
                    html += '<th class="text-right">Disc.</th>';
                    html += '<th class="text-right">Total</th>';
                    html += '</tr></thead><tbody>';

                    nota.items.forEach(item => {
                        html += '<tr>';
                        html += '<td class="text-left">' + item.kode + '</td>';
                        html += '<td class="text-left">' + item.nama_barang + '</td>';
                        html += '<td class="text-right">' + parseFloat(item.qty).toLocaleString('id-ID') + '</td>';
                        html += '<td class="text-right">' + parseFloat(item.harga).toLocaleString('id-ID') + '</td>';
                        html += '<td class="text-right">' + parseFloat(item.disc).toFixed(2).replace('.', ',') + '</td>';
                        html += '<td class="text-right">' + parseFloat(item.total).toLocaleString('id-ID') + '</td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table></div>';
                });

                reportContainer.innerHTML = html;
                window.print();

                // Restore paginated data after print
                setTimeout(() => {
                    reportContainer.innerHTML = currentHtml;
                }, 100);
            }
        </script>
    @else
        <script>
            function printReport() {
                window.print();
            }
        </script>
    @endif
</div>
