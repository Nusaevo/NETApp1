<div>
    @php
        use App\Services\TrdJewel1\Master\MasterService;
        $masterService = new MasterService();
    @endphp
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        {{-- Filter Frame --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="container mb-2 mt-2">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <x-ui-dropdown-select label="Code" model="category" :options="$codeSalesreward" action="Edit"
                                onChanged="onSrCodeChanged" />
                        </div>
                        <div class="col-md-3">
                            <x-ui-text-field label="Tanggal Awal:" model="startCode" type="date" action="Edit" />
                        </div>
                        <div class="col-md-3">
                            <x-ui-text-field label="Tanggal Akhir:" model="endCode" type="date" action="Edit" />
                        </div>
                        <div class="col-md-2">
                            <x-ui-button clickEvent="search" button-name="View" loading="true" action="Edit"
                                cssClass="btn-primary w-100 mb-2" />
                            <button type="button" class="btn btn-light text-capitalize border-0 w-100 mb-2"
                                onclick="printReport()">
                                <i class="fas fa-print text-primary"></i> Print
                            </button>
                            <x-ui-button clickEvent="downloadExcel" button-name="Excel" loading="true" action="Edit"
                                cssClass="btn-success w-100" />
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <x-ui-option model="point_flag" label="Semua Point" :options="['isPoint' => 'Ya']" type="checkbox"
                                layout="horizontal" :action="$actionValue" :checked="$point_flag" />
                            {{-- @dump($point_flag) --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- End Filter Frame --}}

        <div>
            <br>
            {{-- <link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}"> --}}
            <style>
                @media print {
                    .card {
                        border: none !important;
                        box-shadow: none !important;
                        background: transparent !important;
                    }

                    .card-body {
                        padding: 0 !important;
                    }

                    .container {
                        max-width: none !important;
                        padding: 0 !important;
                        margin: 0 !important;
                    }

                    #print {
                        font-family: 'Calibri' !important;
                        font-size: 14px !important;
                    }

                    @page {
                        margin: 1cm 0.5cm 1cm 0.5cm;
                    }

                }
            </style>
            <div id="print">
                <div class="card">
                    <div class="card-body">
                        <div class="container mb-5 mt-3">
                            <div
                                style="max-width:2480px; margin:auto; font-family: 'Calibri'; font-size: 14px;">
                                <h4 style="font-weight: bold;">TOKO BAN CAHAYA TERANG - SURABAYA</h4>
                                <h3 style="text-decoration:underline; text-align:left; font-weight: bold; margin-top: -10px;">
                                    {!! $menuName !!}
                                </h3>
                                <p style="text-align:left; margin-bottom:0;">
                                    <strong>Kode Program : {{ $category }}</strong>
                                </p>
                                <p style="text-align:left; margin-bottom:0;">
                                    Periode:
                                    {{ $startCode ? \Carbon\Carbon::parse($startCode)->format('d-M-Y') : '-' }}
                                    s/d {{ $endCode ? \Carbon\Carbon::parse($endCode)->format('d-M-Y') : '-' }}
                                </p>
                                {{-- <div class="page-info">
                                    Page 1 of 1
                                </div> --}}
                                <table style="width:100%; border-collapse:collapse;">
                                    <thead>
                                        {{-- baris grup header --}}
                                        <tr>
                                            <th colspan="4"
                                                style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000; border-left:1px solid #000; border-top: 1px solid #000;">
                                                Nama / Alamat Pelanggan
                                            </th>
                                            <th colspan="1"
                                                style="text-align:center; padding:4px 8px; border-right:1px dashed #000; border-top: 1px solid #000;">
                                            </th>
                                            <th colspan="1"
                                                style="text-align:center; padding:4px 8px; border-right:1px dashed #000; border-top: 1px solid #000;">
                                            </th>
                                            <th colspan="1"
                                                style="text-align:center; padding:4px 8px; border-right:1px solid #000; border-top: 1px solid #000;">
                                            </th>
                                        </tr>
                                        {{-- baris sub-header --}}
                                        <tr>
                                            <th
                                                style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; border-left:1px solid #000; width: 100px; min-width: 100px; max-width: 100px;">
                                                Tgl. Nota
                                            </th>
                                            <th
                                                style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; width: 12%;">
                                                No. Nota
                                            </th>
                                            <th
                                                style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; width: 15%;">
                                                Kode Brg.
                                            </th>
                                            <th
                                                style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; min-width: 200px; white-space: nowrap;">
                                                Nama Barang
                                            </th>
                                            <th
                                                style="text-align:center; padding:4px 8px; border-bottom:1px solid #000;
                       border-left:1px dashed #000; border-right:1px dashed #000; width: 13%;">
                                                Total Ban
                                            </th>
                                            <th
                                                style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000; width: 12%;">
                                                Point
                                            </th>
                                            <th
                                                style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px solid #000; width: 13%;">
                                                Total Point
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $no = 1; @endphp
                                        @foreach ($results as $group)
                                            {{-- Detail per nota --}}
                                            @foreach ($group['details'] as $row)
                                                <tr>
                                                    <td
                                                        style="padding:4px 8px; border-left:1px solid #000; width: 100px; min-width: 100px; max-width: 100px; white-space: nowrap;">
                                                        {{ $row->tgl_nota ? \Carbon\Carbon::parse($row->tgl_nota)->format('d-M-Y') : '-' }}
                                                    </td>
                                                    <td style="padding:4px 8px;">
                                                        {{ $row->no_nota }}</td>
                                                    <td style="padding:4px 8px; text-align:left;">
                                                        {{ $row->kode_brg }}</td>
                                                    <td style="padding:4px 8px; white-space: nowrap;">
                                                        {{ $row->nama_barang }}</td>
                                                    <td
                                                        style="padding:4px 8px; text-align:center; border-left:1px dashed #000; border-right:1px dashed #000;">
                                                        {{ fmod($row->total_ban, 1) == 0 ? number_format($row->total_ban, 0) : number_format($row->total_ban, 2) }}
                                                    </td>
                                                    <td
                                                        style="padding:4px 8px; text-align:center; border-right:1px dashed #000;">
                                                        {{ fmod($row->point, 1) == 0 ? number_format($row->point, 0) : number_format($row->point, 2) }}
                                                    </td>
                                                    <td
                                                        style="padding:4px 8px; border-right:1px solid #000; text-align:center;">
                                                        {{ fmod($row->total_point, 1) == 0 ? number_format($row->total_point, 0) : number_format($row->total_point, 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            {{-- Summary per customer --}}
                                            <tr>
                                                <td colspan="4"
                                                    style="padding:6px 8px;  font-weight:bold; border-left:1px solid #000; text-align:center;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">
                                                    {{ $group['customer'] }}
                                                </td>
                                                <td
                                                    style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px dashed #000; border-left:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">
                                                    {{ fmod($group['total_ban'], 1) == 0 ? number_format($group['total_ban'], 0) : number_format($group['total_ban'], 2) }}
                                                </td>
                                                <td
                                                    style=" border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">
                                                </td>
                                                <td
                                                    style="padding:6px 8px;  font-weight:bold; text-align:center; border-right:1px solid #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">
                                                    {{ fmod($group['total_point'], 1) == 0 ? number_format($group['total_point'], 0) : number_format($group['total_point'], 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
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
        // Print the report
        window.print();
    }
</script>
