<x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
    <div class="card mb-4">
        <div class="card-body">
            <div class="container mb-2 mt-2">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <x-ui-dropdown-select label="Kode Program" model="selectedRewardCode" :options="$rewardOptions" action="Edit" onChanged="onSrCodeChanged" />
                    </div>
                    <div class="col-md-3">
                        <x-ui-text-field label="Tanggal Awal" model="startPrintDate" type="date" action="Edit" />
                    </div>
                    <div class="col-md-3">
                        <x-ui-text-field label="Tanggal Akhir" model="endPrintDate" type="date" action="Edit" />
                    </div>
                    <div class="col-md-2">
                        <x-ui-button clickEvent="search" button-name="View" loading="true" action="Edit" cssClass="btn-primary w-100 mb-2" />
                        <button type="button" class="btn btn-light text-capitalize border-0 w-100" onclick="printReport()">
                            <i class="fas fa-print text-primary"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}"> --}}

    <style>
        @media print {
            @page {
                size: A4;
                margin: 10mm 8mm 10mm 8mm;
            }
            body {
                margin: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            /* Semua warna menjadi hitam saat print */
            * {
                color: #000 !important;
                background-color: #fff !important;
            }
            /* Icon menjadi hitam */
            .fas, .fa, i {
                color: #000 !important;
            }
            #print .report-box {
                padding: 8px !important;
            }
            #print h3 {
                margin: 0 0 6px 0 !important;
                font-size: 18px !important;
            }
            #print table {
                font-size: 14px !important;
            }
            /* Perbesar semua font di area print */
            #print {
                font-size: 16px !important;
            }
            #print th, #print td {
                padding-top: 3px !important;
                padding-bottom: 3px !important;
            }
            #print thead { display: table-header-group; }
            #print tfoot { display: table-footer-group; }
            #print tr { page-break-inside: avoid; }
            /* Pastikan border bottom header muncul di setiap halaman */
            #print tr.header2,
            #print thead tr:last-child {
                border-bottom: 1px solid #000 !important;
            }
            #print thead tr:last-child th {
                border-bottom: 1px solid #000 !important;
            }
            /* Hapus border bottom dari setiap baris data (tidak ada border antar item) */
            #print table tbody tr:not(.summary-row) td {
                border-bottom: none !important;
            }
            /* Baris summary tetap ada border bottom */
            #print table tbody tr[style*="font-weight"] td {
                border-bottom: 1px solid #000 !important;
            }
            /* Pastikan border bottom muncul di akhir setiap halaman dengan menambahkan pada setiap baris summary */
            #print table tbody tr[style*="font-weight: bold"] td {
                border-bottom: 1px solid #000 !important;
            }
            /* Untuk baris terakhir di setiap halaman, tambahkan border bottom */
            #print table tbody tr {
                page-break-inside: avoid;
            }
            /* Pastikan border kiri dan kanan tetap ada */
            #print table tbody td:first-child {
                border-left: 1px solid #000 !important;
            }
            #print table tbody td:last-child {
                border-right: 1px solid #000 !important;
            }
            /* Tambahkan border bottom pada baris terakhir dari seluruh tabel */
            #print table tbody tr:last-child td {
                border-bottom: 1px solid #000 !important;
            }
            /* Pastikan setiap baris summary memiliki border bottom lengkap */
            #print table tbody tr[style*="font-weight"] td {
                border-bottom: 1px solid #000 !important;
            }
            /* Atur lebar kolom saat print */
            /* Kolom Tgl SJ (kolom 1) - sempit */
            #print table th:nth-child(1),
            #print table td:nth-child(1) {
                width: 8% !important;
                max-width: 8% !important;
                white-space: nowrap !important;
            }
            /* Kolom No Nota (kolom 2) - sempit */
            #print table th:nth-child(2),
            #print table td:nth-child(2) {
                width: 8% !important;
                max-width: 8% !important;
            }
            /* Kolom Kode Brg. (kolom 3) - sempit */
            #print table th:nth-child(3),
            #print table td:nth-child(3) {
                width: 12% !important;
                max-width: 12% !important;
            }
            /* Kolom Nama Barang (kolom 4) - LEBAR (mengambil sisa space) */
            #print table th:nth-child(4),
            #print table td:nth-child(4) {
                width: 50% !important;
                min-width: 50% !important;
            }
            /* Kolom Total Ban (kolom 5) - sempit */
            #print table th:nth-child(5),
            #print table td:nth-child(5) {
                width: 8% !important;
                max-width: 8% !important;
                white-space: nowrap !important;
            }
            /* Kolom Point (kolom 6) - sempit */
            #print table th:nth-child(6),
            #print table td:nth-child(6) {
                width: 8% !important;
                max-width: 8% !important;
                white-space: nowrap !important;
            }
            /* Kolom Total Point (kolom 7) - sempit */
            #print table th:nth-child(7),
            #print table td:nth-child(7) {
                width: 8% !important;
                max-width: 8% !important;
                white-space: nowrap !important;
            }
        }
    </style>

    <!-- Card hanya tampil di layar, tidak saat print -->
    <div class="card d-print-none" style="width: 100%; margin: 20px 0; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 0px 1.5px rgba(0,0,0,0.03); border-radius: 10px; padding: 20px;">
        <div class="report-box" style="width: 100%; margin: auto;">
            <h3 style="text-decoration:underline; text-align:left;">
                {!! $menuName !!}
            </h3>
            <div style="text-align:left; margin-bottom:20px;">
                <strong>PROGRAM {{ $selectedRewardCode }}</strong> Periode: {{ $startPrintDate && $endPrintDate ? (\Carbon\Carbon::parse($startPrintDate)->format('d-M-Y') . ' s/d ' . \Carbon\Carbon::parse($endPrintDate)->format('d-M-Y')) : '-' }}
            </div>
            @php
                $isIrcBrand = !empty($results) && isset($results[0]['is_irc']) ? $results[0]['is_irc'] : false;
            @endphp
            <div style="overflow-x: auto;">
                <table style="width:100%; border-collapse:collapse; font-size: 14px; min-width: 1000px;">
                    <thead>
                        @if($isIrcBrand)
                        {{-- Format IRC: Ban Luar, Ban Dalam, Total Ban, Point BL, Point BD, Total Point, Sisa BD --}}
                        <tr>
                            <th colspan="4" style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; border-right:1px dashed #000; border-left:1px solid #000; border-top: 1px solid #000; background-color: #f8f9fa;">Nama / Alamat Pelanggan</th>
                            <th colspan="3" style="text-align:center; padding:8px 12px; border-right:1px dashed #000; border-top: 1px solid #000; background-color: #f8f9fa;"></th>
                            <th colspan="2" style="text-align:center; padding:8px 12px; border-right:1px dashed #000; border-top: 1px solid #000; background-color: #f8f9fa;"></th>
                            <th colspan="1" style="text-align:center; padding:8px 12px; border-right:1px dashed #000; border-top: 1px solid #000; background-color: #f8f9fa;"></th>
                            <th colspan="1" style="text-align:center; padding:8px 12px; border-right:1px solid #000; border-top: 1px solid #000; background-color: #f8f9fa;"></th>
                        </tr>
                        <tr class="header2">
                            <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; border-left:1px solid #000; background-color: #f8f9fa;">Tgl SJ</th>
                            <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; background-color: #f8f9fa;">No. Nota</th>
                            <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; background-color: #f8f9fa;">Kode Brg.</th>
                            <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; background-color: #f8f9fa;">Nama Barang</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-left:1px dashed #000; border-right:1px dashed #000; background-color: #f8f9fa;">Ban Luar</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-right:1px dashed #000; background-color: #f8f9fa;">Ban Dalam</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-right:1px dashed #000; background-color: #f8f9fa;">Total Ban</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-right:1px dashed #000; background-color: #f8f9fa;">Point BL</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-right:1px dashed #000; background-color: #f8f9fa;">Point BD</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-right:1px dashed #000; background-color: #f8f9fa;">Total Point</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-right:1px solid #000; background-color: #f8f9fa;">Sisa BD</th>
                        </tr>
                        @else
                        {{-- Format Non-IRC: Total Ban, Point, Total Point --}}
                        <tr>
                            <th colspan="4" style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; border-right:1px dashed #000; border-left:1px solid #000; border-top: 1px solid #000; background-color: #f8f9fa;">Nama / Alamat Pelanggan</th>
                            <th colspan="1" style="text-align:center; padding:8px 12px; border-right:1px dashed #000; border-top: 1px solid #000; background-color: #f8f9fa;"></th>
                            <th colspan="1" style="text-align:center; padding:8px 12px; border-right:1px dashed #000; border-top: 1px solid #000; background-color: #f8f9fa;"></th>
                            <th colspan="1" style="text-align:center; padding:8px 12px; border-right:1px solid #000; border-top: 1px solid #000; background-color: #f8f9fa;"></th>
                        </tr>
                        <tr class="header2">
                            <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; border-left:1px solid #000; background-color: #f8f9fa;">Tgl SJ</th>
                            <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; background-color: #f8f9fa;">No. Nota</th>
                            <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; background-color: #f8f9fa;">Kode Brg.</th>
                            <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; background-color: #f8f9fa;">Nama Barang</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-left:1px dashed #000; border-right:1px dashed #000; background-color: #f8f9fa;">Total Ban</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-right:1px dashed #000; background-color: #f8f9fa;">Point</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-right:1px solid #000; background-color: #f8f9fa;">Total Point</th>
                        </tr>
                        @endif
                    </thead>
                    <tbody>
                        @foreach ($results as $group)
                            @php($prevNota = null)
                            @foreach ($group['details'] as $row)
                                <tr style="background-color: {{ $loop->parent->index % 2 == 0 ? '#ffffff' : '#f8f9fa' }};">
                                    <td style="padding:8px 12px; border-left:1px solid #000;">{{ $prevNota !== $row['no_nota'] ? ($row['tgl_sj'] ? \Carbon\Carbon::parse($row['tgl_sj'])->format('d M Y') : '') : '' }}</td>
                                    <td style="padding:8px 12px;">{{ $prevNota !== $row['no_nota'] ? $row['no_nota'] : '' }}</td>
                                    <td style="padding:8px 12px; text-align:left;">{{ $row['kode_brg'] }}</td>
                                    <td style="padding:8px 12px;">{{ $row['nama_barang'] }}</td>
                                    @if($group['is_irc'] ?? false)
                                    {{-- Format IRC --}}
                                    <td style="padding:8px 12px; text-align:center; border-left:1px dashed #000; border-right:1px dashed #000;">@if($row['ban_luar'] ?? 0){{ number_format($row['ban_luar'], 0) }}@endif</td>
                                    <td style="padding:8px 12px; text-align:center; border-right:1px dashed #000;">@if($row['ban_dalam'] ?? 0){{ number_format($row['ban_dalam'], 0) }}@endif</td>
                                    <td style="padding:8px 12px; text-align:center; border-right:1px dashed #000;">@if($row['total_ban'] ?? 0){{ number_format($row['total_ban'], 0) }}@endif</td>
                                    <td style="padding:8px 12px; text-align:center; border-right:1px dashed #000;">@if($row['point_bl'] ?? 0){{ number_format($row['point_bl'], 0) }}@endif</td>
                                    <td style="padding:8px 12px; text-align:center; border-right:1px dashed #000;">@if($row['point_bd'] ?? 0){{ number_format($row['point_bd'], 0) }}@endif</td>
                                    <td style="padding:8px 12px; text-align:center; border-right:1px dashed #000;">@if($row['total_point'] ?? 0){{ number_format($row['total_point'], 0) }}@endif</td>
                                    <td style="padding:8px 12px; border-right:1px solid #000; text-align:center;">@if($row['sisa_bd'] ?? 0){{ number_format($row['sisa_bd'], 0) }}@endif</td>
                                    @else
                                    {{-- Format Non-IRC --}}
                                    <td style="padding:8px 12px; text-align:center; border-left:1px dashed #000; border-right:1px dashed #000;">{{ fmod($row['total_ban'], 1) == 0 ? number_format($row['total_ban'], 0) : number_format($row['total_ban'], 2) }}</td>
                                    <td style="padding:8px 12px; text-align:center; border-right:1px dashed #000;">{{ fmod($row['point'] ?? 0, 1) == 0 ? number_format($row['point'] ?? 0, 0) : number_format($row['point'] ?? 0, 2) }}</td>
                                    <td style="padding:8px 12px; border-right:1px solid #000; text-align:center;">{{ fmod($row['total_point'], 1) == 0 ? number_format($row['total_point'], 0) : number_format($row['total_point'], 2) }}</td>
                                    @endif
                                </tr>
                                @php($prevNota = $row['no_nota'])
                            @endforeach
                            <tr style="font-weight: bold;">
                                <td colspan="3" style="padding:10px 12px; border-left:1px solid #000; text-align:left;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">
                                    <span style="display:inline-block; min-width: 200px;"></span>
                                    <span style="float:right;">{{ $group['customer'] }}</span>
                                </td>
                                <td style="{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}"></td>
                                @if($group['is_irc'] ?? false)
                                {{-- Format IRC Total --}}
                                <td style="padding:10px 12px; text-align:center; border-right:1px dashed #000; border-left:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['ban_luar'] ?? 0){{ number_format($group['ban_luar'], 0) }}@endif</td>
                                <td style="padding:10px 12px; text-align:center; border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['ban_dalam'] ?? 0){{ number_format($group['ban_dalam'], 0) }}@endif</td>
                                <td style="padding:10px 12px; text-align:center; border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['total_ban'] ?? 0){{ number_format($group['total_ban'], 0) }}@endif</td>
                                <td style="padding:10px 12px; text-align:center; border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['point_bl'] ?? 0){{ number_format($group['point_bl'], 0) }}@endif</td>
                                <td style="padding:10px 12px; text-align:center; border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['point_bd'] ?? 0){{ number_format($group['point_bd'], 0) }}@endif</td>
                                <td style="padding:10px 12px; text-align:center; border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['total_point'] ?? 0){{ number_format($group['total_point'], 0) }}@endif</td>
                                <td style="padding:10px 12px; text-align:center; border-right:1px solid #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['sisa_bd'] ?? 0){{ number_format($group['sisa_bd'], 0) }}@endif</td>
                                @else
                                {{-- Format Non-IRC Total --}}
                                <td style="padding:10px 12px; text-align:center; border-right:1px dashed #000; border-left:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">{{ fmod($group['total_ban'], 1) == 0 ? number_format($group['total_ban'], 0) : number_format($group['total_ban'], 2) }}</td>
                                <td style=" border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}"></td>
                                <td style="padding:10px 12px; text-align:center; border-right:1px solid #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">{{ fmod($group['total_point'], 1) == 0 ? number_format($group['total_point'], 0) : number_format($group['total_point'], 2) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Area print tetap tampil saat print -->
    <div id="print" class="d-none d-print-block p-20">
        <div style="max-width: 1200px; margin: 0 auto; font-family: 'Calibri'; font-size: 16px;">
            <div class="report-box" style="max-width: 1200px; margin: auto; padding: 20px;">
                <h3 style="text-decoration:underline; text-align:left;">
                    {!! $menuName !!}
                </h3>
                <div style="text-align:left; margin-bottom:20px;">
                    <strong>PROGRAM {{ $selectedRewardCode }}</strong> Periode: {{ $startPrintDate && $endPrintDate ? (\Carbon\Carbon::parse($startPrintDate)->format('d-M-Y') . ' s/d ' . \Carbon\Carbon::parse($endPrintDate)->format('d-M-Y')) : '-' }}
                </div>
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        @if($isIrcBrand)
                        {{-- Format IRC: Ban Luar, Ban Dalam, Total Ban, Point BL, Point BD, Total Point, Sisa BD --}}
                        <tr>
                            <th colspan="4" style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000; border-left:1px solid #000; border-top: 1px solid #000;">Nama / Alamat Pelanggan</th>
                            <th colspan="3" style="text-align:center; padding:4px 8px; border-right:1px dashed #000; border-top: 1px solid #000;"></th>
                            <th colspan="2" style="text-align:center; padding:4px 8px; border-right:1px dashed #000; border-top: 1px solid #000;"></th>
                            <th colspan="1" style="text-align:center; padding:4px 8px; border-right:1px dashed #000; border-top: 1px solid #000;"></th>
                            <th colspan="1" style="text-align:center; padding:4px 8px; border-right:1px solid #000; border-top: 1px solid #000;"></th>
                        </tr>
                        <tr class="header2">
                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; border-left:1px solid #000;">Tgl SJ</th>
                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000;">No. Nota</th>
                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000;">Kode Brg.</th>
                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000;">Nama Barang</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-left:1px dashed #000; border-right:1px dashed #000;">Ban Luar</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000;">Ban Dalam</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000;">Total Ban</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000;">Point BL</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000;">Point BD</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000;">Total Point</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px solid #000;">Sisa</th>
                        </tr>
                        @else
                        {{-- Format Non-IRC: Total Ban, Point, Total Point --}}
                        <tr>
                            <th colspan="4" style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000; border-left:1px solid #000; border-top: 1px solid #000;">Nama / Alamat Pelanggan</th>
                            <th colspan="1" style="text-align:center; padding:4px 8px; border-right:1px dashed #000; border-top: 1px solid #000;"></th>
                            <th colspan="1" style="text-align:center; padding:4px 8px; border-right:1px dashed #000; border-top: 1px solid #000;"></th>
                            <th colspan="1" style="text-align:center; padding:4px 8px; border-right:1px solid #000; border-top: 1px solid #000;"></th>
                        </tr>
                        <tr class="header2">
                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; border-left:1px solid #000;">Tgl SJ</th>
                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000;">No. Nota</th>
                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000;">Kode Brg.</th>
                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000;">Nama Barang</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-left:1px dashed #000; border-right:1px dashed #000;">Total Ban</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000;">Point</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px solid #000;">Total Point</th>
                        </tr>
                        @endif
                    </thead>
                    <tbody>
                        @foreach ($results as $group)
                            @php($prevNota = null)
                            @foreach ($group['details'] as $row)
                                <tr>
                                    <td style="padding:4px 8px; border-left:1px solid #000;">{{ $prevNota !== $row['no_nota'] ? ($row['tgl_sj'] ? \Carbon\Carbon::parse($row['tgl_sj'])->format('d M Y') : '') : '' }}</td>
                                    <td style="padding:4px 8px;">{{ $prevNota !== $row['no_nota'] ? $row['no_nota'] : '' }}</td>
                                    <td style="padding:4px 8px; text-align:left;">{{ $row['kode_brg'] }}</td>
                                    <td style="padding:4px 8px;">{{ $row['nama_barang'] }}</td>
                                    @if($group['is_irc'] ?? false)
                                    {{-- Format IRC --}}
                                    <td style="padding:4px 8px; text-align:center; border-left:1px dashed #000; border-right:1px dashed #000;">@if($row['ban_luar'] ?? 0){{ number_format($row['ban_luar'], 0) }}@endif</td>
                                    <td style="padding:4px 8px; text-align:center; border-right:1px dashed #000;">@if($row['ban_dalam'] ?? 0){{ number_format($row['ban_dalam'], 0) }}@endif</td>
                                    <td style="padding:4px 8px; text-align:center; border-right:1px dashed #000;">@if($row['total_ban'] ?? 0){{ number_format($row['total_ban'], 0) }}@endif</td>
                                    <td style="padding:4px 8px; text-align:center; border-right:1px dashed #000;">@if($row['point_bl'] ?? 0){{ number_format($row['point_bl'], 0) }}@endif</td>
                                    <td style="padding:4px 8px; text-align:center; border-right:1px dashed #000;">@if($row['point_bd'] ?? 0){{ number_format($row['point_bd'], 0) }}@endif</td>
                                    <td style="padding:4px 8px; text-align:center; border-right:1px dashed #000;">@if($row['total_point'] ?? 0){{ number_format($row['total_point'], 0) }}@endif</td>
                                    <td style="padding:4px 8px; border-right:1px solid #000; text-align:center;">@if($row['sisa_bd'] ?? 0){{ number_format($row['sisa_bd'], 0) }}@endif</td>
                                    @else
                                    {{-- Format Non-IRC --}}
                                    <td style="padding:4px 8px; text-align:center; border-left:1px dashed #000; border-right:1px dashed #000;">{{ fmod($row['total_ban'], 1) == 0 ? number_format($row['total_ban'], 0) : number_format($row['total_ban'], 2) }}</td>
                                    <td style="padding:4px 8px; text-align:center; border-right:1px dashed #000;">{{ fmod($row['point'] ?? 0, 1) == 0 ? number_format($row['point'] ?? 0, 0) : number_format($row['point'] ?? 0, 2) }}</td>
                                    <td style="padding:4px 8px; border-right:1px solid #000; text-align:center;">{{ fmod($row['total_point'], 1) == 0 ? number_format($row['total_point'], 0) : number_format($row['total_point'], 2) }}</td>
                                    @endif
                                </tr>
                                @php($prevNota = $row['no_nota'])
                            @endforeach
                            <tr>
                                <td colspan="3" style="padding:6px 8px;  font-weight:bold; border-left:1px solid #000; text-align:left;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">
                                    <span style="display:inline-block; min-width: 200px;"></span>
                                    <span style="float:right;">{{ $group['customer'] }}</span>
                                </td>
                                <td style="{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}"></td>
                                @if($group['is_irc'] ?? false)
                                {{-- Format IRC Total --}}
                                <td style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px dashed #000; border-left:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['ban_luar'] ?? 0){{ number_format($group['ban_luar'], 0) }}@endif</td>
                                <td style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['ban_dalam'] ?? 0){{ number_format($group['ban_dalam'], 0) }}@endif</td>
                                <td style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['total_ban'] ?? 0){{ number_format($group['total_ban'], 0) }}@endif</td>
                                <td style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['point_bl'] ?? 0){{ number_format($group['point_bl'], 0) }}@endif</td>
                                <td style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['point_bd'] ?? 0){{ number_format($group['point_bd'], 0) }}@endif</td>
                                <td style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['total_point'] ?? 0){{ number_format($group['total_point'], 0) }}@endif</td>
                                <td style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px solid #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">@if($group['sisa_bd'] ?? 0){{ number_format($group['sisa_bd'], 0) }}@endif</td>
                                @else
                                {{-- Format Non-IRC Total --}}
                                <td style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px dashed #000; border-left:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">{{ fmod($group['total_ban'], 1) == 0 ? number_format($group['total_ban'], 0) : number_format($group['total_ban'], 2) }}</td>
                                <td style=" border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}"></td>
                                <td style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px solid #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">{{ fmod($group['total_point'], 1) == 0 ? number_format($group['total_point'], 0) : number_format($group['total_point'], 2) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function printReport() {
            setTimeout(function() {
                window.print();
            }, 1000);
        }
    </script>
</x-ui-page-card>
