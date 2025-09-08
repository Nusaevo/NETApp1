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
                            <button type="button" class="btn btn-light text-capitalize border-0 w-100"
                                onclick="printReport()">
                                <i class="fas fa-print text-primary"></i> Print
                            </button>
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
                    .page-info {
                        display: none;
                        bottom: 1px
                    }

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
                        margin: 1cm 1cm 2cm 1cm;

                        @bottom-right {
                            content: "Page " counter(page) " of " counter(pages);
                            font-size: 12px;
                            color: #666;
                            font-family: Arial, sans-serif;
                            margin-bottom: 0.5cm;
                        }
                    }
                }

                @media screen {
                    .page-info {
                        position: fixed;
                        bottom: 40px;
                        right: 20px;
                        font-size: 12px;
                        color: #666;
                        background: rgba(255, 255, 255, 0.9);
                        padding: 5px 10px;
                        border-radius: 3px;
                        border: 1px solid #ddd;
                        z-index: 1000;
                    }
                }
            </style>
            <div id="print">
                <div class="card">
                    <div class="card-body">
                        <div class="container mb-5 mt-3">
                            <div style="max-width:2480px; margin:auto; padding:20px; font-family: 'Calibri'; font-size: 14px;">
                                <h4 style="font-weight: bold;">TOKO BAN CAHAYA TERANG - SURABAYA</h4>
                                <h3 style="text-decoration:underline; text-align:left; font-weight: bold;">
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
                                <div class="page-info">
                                    Page 1 of 1
                                </div>
                                <table style="width:100%; border-collapse:collapse;">
                                    <thead>
                                        {{-- baris grup header --}}
                                        <tr>
                                            <th colspan="3"
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
                                                style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; border-left:1px solid #000; width: 15%;">
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
                                                    <td style="padding:4px 8px; border-left:1px solid #000;">
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
                                                <td colspan="3"
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

    // Update page info for screen display only
    document.addEventListener('DOMContentLoaded', function() {
        updatePageInfo();
    });

    function updatePageInfo() {
        // Get the print content
        const printContent = document.getElementById('print');
        if (!printContent) return;

        // Calculate content height
        const contentHeight = printContent.scrollHeight;

        // A4 page height in pixels (approximate)
        const pageHeight = 900;

        // Calculate total pages
        const totalPages = Math.max(1, Math.ceil(contentHeight / pageHeight));

        // Update page info for screen display only
        const pageInfoElement = document.querySelector('.page-info');
        if (pageInfoElement) {
            pageInfoElement.textContent = `Page 1 of ${totalPages}`;
        }
    }

    // Update page info when Livewire updates
    document.addEventListener('livewire:load', function() {
        updatePageInfo();
    });

    document.addEventListener('livewire:update', function() {
        setTimeout(updatePageInfo, 100);
    });
</script>
