<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        {{-- Filter Frame --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="container mb-2 mt-2">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <x-ui-dropdown-select label="Brand" model="brand" :options="$brandOptions" action="Edit" />
                            {{-- @dump($brandOptions) --}}
                        </div>
                        <div class="col-md-2">
                            <x-ui-button clickEvent="search" button-name="View" loading="true" action="Edit"
                                cssClass="btn-primary w-100 mb-2" />
                            <button type="button" class="btn btn-light text-capitalize border-0 w-100 mb-2"
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
        {{-- End Filter Frame --}}

        <div id="print">
            <div>
                <br>
                <style>
                    .laporan-stok {
                        font-family: "Times New Roman", serif;
                    }
                    table.laporan-stok {
                        border-collapse: collapse;
                        width: 100%;
                        font-size: 14px;
                    }
                    table.laporan-stok th, table.laporan-stok td {
                        border: 1px solid black;
                        padding: 4px 8px;
                        text-align: center;
                    }
                    table.laporan-stok th {
                        background-color: #f2f2f2;
                    }
                    .left {
                        text-align: left !important;
                    }
                    .no-border {
                        border: none !important;
                    }

                    /* CSS untuk print */
                    @media print {
                        @page:first {
                            size: A4 landscape;
                            margin: 0 0 0 0;
                        }

                        @page {
                            size: A4 landscape;
                            margin: 10mm 0 0 0;
                        }

                        .card.mb-4,
                        x-ui-page-card {
                            display: none !important;
                        }

                        #print {
                            display: block !important;
                            width: 100vw;
                            height: 100vh;
                            margin: 0;
                            padding: 0;
                        }

                        body {
                            margin: 0;
                            padding: 0;
                            width: 100vw;
                            height: 100vh;
                        }

                        .print-page {
                            box-shadow: none !important;
                            border: none !important;
                            width: 100%;
                            height: 100%;
                            margin: 0;
                            padding: 0;
                        }

                        .container {
                            width: 100% !important;
                            max-width: none !important;
                            margin: 0 !important;
                        }

                        .laporan-stok {
                            width: 100% !important;
                            font-size: 12px !important;
                        }

                        .laporan-stok th,
                        .laporan-stok td {
                            padding: 2px 4px !important;
                        }
                    }
                </style>
                <div class="card print-page laporan-stok">
                    <div class="card-body">
                        <div class="container">
                            <div style="max-width:2480px;">
                                <div style="width:100%;">
                                    <div style="font-weight:bold; font-size:24px; text-decoration:underline; color:#142850; font-family:'Times New Roman',serif; margin-bottom: 8px;">
                                        GAJAH TUNGGAL
                                    </div>
                                    <div style="text-align:center;">
                                        <div style="font-weight:bold; font-size:24px; text-decoration:underline; color:#142850; font-family:'Times New Roman',serif;">
                                            Laporan Stok Barang
                                        </div>
                                        <div style="font-weight:bold; font-size:16px; color:#142850; font-family:'Times New Roman',serif;">
                                            per Tanggal: {{ \Carbon\Carbon::now()->format('d-M-Y') }}
                                        </div>
                                    </div>
                                </div>
                                <table class="laporan-stok">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th class="left">Nama Barang</th>
                                            <th>G01</th>
                                            <th>G02</th>
                                            <th>G04</th>
                                            <th>Total</th>
                                            <th>FGI</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalG01 = $totalG02 = $totalG04 = $totalFGI = $totalPoint = $grandTotal = 0;
                                        @endphp
                                        @forelse ($results as $row)
                                            @php
                                                $rowTotal = ($row->g01 ?? 0) + ($row->g02 ?? 0) + ($row->g04 ?? 0);
                                                $totalG01 += ($row->g01 ?? 0);
                                                $totalG02 += ($row->g02 ?? 0);
                                                $totalG04 += ($row->g04 ?? 0);
                                                $totalFGI += ($row->fgi ?? 0);
                                                $totalPoint += ($row->point ?? 0);
                                            @endphp
                                            <tr>
                                                <td>{{ $row->code ?? '' }}</td>
                                                <td class="left">{{ $row->name ?? '' }}</td>
                                                <td>{{ is_numeric($row->g01 ?? null) ? rtrim(rtrim(number_format($row->g01, 3, '.', ''), '0'), '.') : '' }}</td>
                                                <td>{{ is_numeric($row->g02 ?? null) ? rtrim(rtrim(number_format($row->g02, 3, '.', ''), '0'), '.') : '' }}</td>
                                                <td>{{ is_numeric($row->g04 ?? null) ? rtrim(rtrim(number_format($row->g04, 3, '.', ''), '0'), '.') : '' }}</td>
                                                <td>{{ is_numeric($rowTotal) ? rtrim(rtrim(number_format($rowTotal, 3, '.', ''), '0'), '.') : '' }}</td>
                                                <td>{{ is_numeric($row->fgi ?? null) ? round($row->fgi, 0) : '' }}</td>
                                                <td>{{ is_numeric($row->point ?? null) ? $row->point : '0' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="no-border">Tidak ada data untuk ditampilkan</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-ui-page-card>
</div>
<script>
function printReport() {
    // Sembunyikan elemen yang tidak ingin di-print
    const filterCard = document.querySelector('.card.mb-4');
    const pageCard = document.querySelector('x-ui-page-card');
    const printArea = document.getElementById('print');

    // Pastikan area print terlihat
    if (printArea) {
        printArea.style.display = 'block';
    }

    // Sembunyikan elemen yang tidak perlu di-print
    if (filterCard) filterCard.style.display = 'none';
    if (pageCard) pageCard.style.display = 'none';

    // Print hanya bagian yang diinginkan
    window.print();

    // Tampilkan kembali elemen setelah print
    setTimeout(() => {
        if (filterCard) filterCard.style.display = 'block';
        if (pageCard) pageCard.style.display = 'block';
    }, 100);
}
</script>

