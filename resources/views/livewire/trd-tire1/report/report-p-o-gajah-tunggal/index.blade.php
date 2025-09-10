<x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
    <div class="card mb-4">
        <div class="card-body">
            <div class="container mb-2 mt-2">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <x-ui-text-field label="Tanggal Awal" model="startPrintDate" type="date" action="Edit" />
                    </div>
                    <div class="col-md-3">
                        <x-ui-text-field label="Tanggal Akhir" model="endPrintDate" type="date" action="Edit" />
                    </div>
                    <div class="col-md-3">
                        <x-ui-dropdown-select label="Kode Program" model="selectedRewardCode" :options="$rewardOptions" action="Edit" onChanged="onSrCodeChanged" />
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

    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <!-- Card hanya tampil di layar, tidak saat print -->
    <div class="card d-print-none" style="width: 100%; margin: 20px 0; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 0px 1.5px rgba(0,0,0,0.03); border-radius: 10px; padding: 20px;">
        <div class="report-box" style="width: 100%; margin: auto;">
            <h3 style="text-decoration:underline; text-align:left;">
                {!! $menuName !!}
            </h3>
            <div style="text-align:left; margin-bottom:10px; font-size: 16px;">
                <strong>Kode Program : {{ $selectedRewardCode }}</strong>
            </div>
            <div style="text-align:left; margin-bottom:20px; font-size: 14px;">
                Periode: {{ $startPrintDate && $endPrintDate ? (\Carbon\Carbon::parse($startPrintDate)->format('d-M-Y') . ' s/d ' . \Carbon\Carbon::parse($endPrintDate)->format('d-M-Y')) : '-' }}
            </div>
            <div style="overflow-x: auto;">
                <table style="width:100%; border-collapse:collapse; font-size: 14px; min-width: 1000px;">
                    <thead>
                        <tr>
                            <th colspan="4" style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; border-right:1px dashed #000; border-left:1px solid #000; border-top: 1px solid #000; background-color: #f8f9fa;">Nama / Alamat Pelanggan</th>
                            <th colspan="1" style="text-align:center; padding:8px 12px; border-right:1px dashed #000; border-top: 1px solid #000; background-color: #f8f9fa;"></th>
                            <th colspan="1" style="text-align:center; padding:8px 12px; border-right:1px dashed #000; border-top: 1px solid #000; background-color: #f8f9fa;"></th>
                            <th colspan="1" style="text-align:center; padding:8px 12px; border-right:1px solid #000; border-top: 1px solid #000; background-color: #f8f9fa;"></th>
                        </tr>
                        <tr>
                            <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; border-left:1px solid #000; background-color: #f8f9fa;">Tgl SJ</th>
                            <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; background-color: #f8f9fa;">No. Nota</th>
                            <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; background-color: #f8f9fa;">Kode Brg.</th>
                            <th style="text-align:left; padding:8px 12px; border-bottom:1px solid #000; background-color: #f8f9fa;">Nama Barang</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-left:1px dashed #000; border-right:1px dashed #000; background-color: #f8f9fa;">Total Ban</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-right:1px dashed #000; background-color: #f8f9fa;">Point</th>
                            <th style="text-align:center; padding:8px 12px; border-bottom:1px solid #000; border-right:1px solid #000; background-color: #f8f9fa;">Total Point</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $group)
                            @foreach ($group['details'] as $row)
                                <tr style="background-color: {{ $loop->parent->index % 2 == 0 ? '#ffffff' : '#f8f9fa' }};">
                                    <td style="padding:8px 12px; border-left:1px solid #000;">{{ $row['tgl_sj'] ? \Carbon\Carbon::parse($row['tgl_sj'])->format('d M Y') : '' }}</td>
                                    <td style="padding:8px 12px;">{{ $row['no_nota'] }}</td>
                                    <td style="padding:8px 12px; text-align:left;">{{ $row['kode_brg'] }}</td>
                                    <td style="padding:8px 12px;">{{ $row['nama_barang'] }}</td>
                                    <td style="padding:8px 12px; text-align:center; border-left:1px dashed #000; border-right:1px dashed #000;">{{ fmod($row['total_ban'], 1) == 0 ? number_format($row['total_ban'], 0) : number_format($row['total_ban'], 2) }}</td>
                                    <td style="padding:8px 12px; text-align:center; border-right:1px dashed #000;">{{ fmod($row['point'], 1) == 0 ? number_format($row['point'], 0) : number_format($row['point'], 2) }}</td>
                                    <td style="padding:8px 12px; border-right:1px solid #000; text-align:center;">{{ fmod($row['total_point'], 1) == 0 ? number_format($row['total_point'], 0) : number_format($row['total_point'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr style="font-weight: bold;">
                                <td colspan="3" style="padding:10px 12px; border-left:1px solid #000; text-align:left;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">
                                    <span style="display:inline-block; min-width: 200px;"></span>
                                    <span style="float:right;">{{ $group['customer'] }}</span>
                                </td>
                                <td style="{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}"></td>
                                <td style="padding:10px 12px; text-align:center; border-right:1px dashed #000; border-left:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">{{ fmod($group['total_ban'], 1) == 0 ? number_format($group['total_ban'], 0) : number_format($group['total_ban'], 2) }}</td>
                                <td style=" border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}"></td>
                                <td style="padding:10px 12px; text-align:center; border-right:1px solid #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">{{ fmod($group['total_point'], 1) == 0 ? number_format($group['total_point'], 0) : number_format($group['total_point'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Area print tetap tampil saat print -->
    <div id="print" class="d-none d-print-block p-20">
        <div style="max-width: 1200px; margin: 0 auto; font-family: 'Calibri'; font-size: 14px;">
            <div class="report-box" style="max-width: 1200px; margin: auto; padding: 20px;">
                <div style="text-align:left; margin-bottom:0;">
                    <strong>Kode Program : {{ $selectedRewardCode }}</strong>
                </div>
                <div style="text-align:left; margin-bottom:20px;">
                    Periode: {{ $startPrintDate && $endPrintDate ? (\Carbon\Carbon::parse($startPrintDate)->format('d-M-Y') . ' s/d ' . \Carbon\Carbon::parse($endPrintDate)->format('d-M-Y')) : '-' }}
                </div>
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th colspan="4" style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000; border-left:1px solid #000; border-top: 1px solid #000;">Nama / Alamat Pelanggan</th>
                            <th colspan="1" style="text-align:center; padding:4px 8px; border-right:1px dashed #000; border-top: 1px solid #000;"></th>
                            <th colspan="1" style="text-align:center; padding:4px 8px; border-right:1px dashed #000; border-top: 1px solid #000;"></th>
                            <th colspan="1" style="text-align:center; padding:4px 8px; border-right:1px solid #000; border-top: 1px solid #000;"></th>
                        </tr>
                        <tr>
                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; border-left:1px solid #000;">Tgl SJ</th>
                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000;">No. Nota</th>
                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000;">Kode Brg.</th>
                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000;">Nama Barang</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-left:1px dashed #000; border-right:1px dashed #000;">Total Ban</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000;">Point</th>
                            <th style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px solid #000;">Total Point</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $group)
                            @foreach ($group['details'] as $row)
                                <tr>
                                    <td style="padding:4px 8px; border-left:1px solid #000;">{{ $row['tgl_sj'] ? \Carbon\Carbon::parse($row['tgl_sj'])->format('d M Y') : '' }}</td>
                                    <td style="padding:4px 8px;">{{ $row['no_nota'] }}</td>
                                    <td style="padding:4px 8px; text-align:left;">{{ $row['kode_brg'] }}</td>
                                    <td style="padding:4px 8px;">{{ $row['nama_barang'] }}</td>
                                    <td style="padding:4px 8px; text-align:center; border-left:1px dashed #000; border-right:1px dashed #000;">{{ fmod($row['total_ban'], 1) == 0 ? number_format($row['total_ban'], 0) : number_format($row['total_ban'], 2) }}</td>
                                    <td style="padding:4px 8px; text-align:center; border-right:1px dashed #000;">{{ fmod($row['point'], 1) == 0 ? number_format($row['point'], 0) : number_format($row['point'], 2) }}</td>
                                    <td style="padding:4px 8px; border-right:1px solid #000; text-align:center;">{{ fmod($row['total_point'], 1) == 0 ? number_format($row['total_point'], 0) : number_format($row['total_point'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="3" style="padding:6px 8px;  font-weight:bold; border-left:1px solid #000; text-align:left;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">
                                    <span style="display:inline-block; min-width: 200px;"></span>
                                    <span style="float:right;">{{ $group['customer'] }}</span>
                                </td>
                                <td style="{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}"></td>
                                <td style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px dashed #000; border-left:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">{{ fmod($group['total_ban'], 1) == 0 ? number_format($group['total_ban'], 0) : number_format($group['total_ban'], 2) }}</td>
                                <td style=" border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}"></td>
                                <td style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px solid #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">{{ fmod($group['total_point'], 1) == 0 ? number_format($group['total_point'], 0) : number_format($group['total_point'], 2) }}</td>
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
