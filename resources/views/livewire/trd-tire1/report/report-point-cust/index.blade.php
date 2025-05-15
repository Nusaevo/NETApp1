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
                </div>
            </div>
        </div>
        {{-- End Filter Frame --}}

        <div id="print">
            <div>
                <br>
                <link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}">
                <div class="card">
                    <div class="card-body">
                        <div class="container mb-5 mt-3">
                            <div style="max-width:2480px; margin:auto; padding:20px;">
                                <h4>TOKO BAN CAHAYA TERANG - SURABAYA</h4>
                                <h3 style="text-decoration:underline; text-align:left;">
                                    DATA PENJUALAN GT RADIAL per Customer
                                </h3>
                                <p style="text-align:left; margin-bottom:0;">
                                    <strong>Nama Program : {{ $category }}</strong>
                                </p>
                                <p style="text-align:left; margin-bottom:20px;">
                                    Periode:
                                    {{ $startCode ? \Carbon\Carbon::parse($startCode)->format('d-M-Y') : '-' }}
                                    s/d {{ $endCode ? \Carbon\Carbon::parse($endCode)->format('d-M-Y') : '-' }}
                                </p>
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
                                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000; border-left:1px solid #000;">
                                                No. Nota
                                            </th>
                                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000;">
                                                Kode Brg.
                                            </th>
                                            <th style="text-align:left; padding:4px 8px; border-bottom:1px solid #000;">
                                                Nama Barang
                                            </th>
                                            <th
                                                style="text-align:center; padding:4px 8px; border-bottom:1px solid #000;
                       border-left:1px dashed #000; border-right:1px dashed #000;">
                                                Total Ban
                                            </th>
                                            <th
                                                style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px dashed #000;">
                                                Point
                                            </th>
                                            <th
                                                style="text-align:center; padding:4px 8px; border-bottom:1px solid #000; border-right:1px solid #000;">
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
                                                    <td
                                                        style="padding:4px 8px; text-align:left;">
                                                        {{ $row->kode_brg }}</td>
                                                    <td style="padding:4px 8px;">
                                                        {{ $row->nama_barang }}</td>
                                                    <td
                                                        style="padding:4px 8px; text-align:center; border-left:1px dashed #000; border-right:1px dashed #000;">
                                                        {{ (fmod($row->total_ban, 1) == 0) ? number_format($row->total_ban, 0) : number_format($row->total_ban, 2) }}
                                                    </td>
                                                    <td
                                                        style="padding:4px 8px; text-align:center; border-right:1px dashed #000;">
                                                        {{ (fmod($row->point, 1) == 0) ? number_format($row->point, 0) : number_format($row->point, 2) }}
                                                    </td>
                                                    <td
                                                        style="padding:4px 8px; border-right:1px solid #000; text-align:center;">
                                                        {{ (fmod($row->total_point, 1) == 0) ? number_format($row->total_point, 0) : number_format($row->total_point, 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            {{-- Summary per customer --}}
                                            <tr>
                                                <td colspan="2"
                                                    style="padding:6px 8px; font-weight:bold; border-left:1px solid #000; text-align:left;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">
                                                    {{ $no++ }}
                                                    {{ $group['details'][0]->no_nota ?? '-' }}
                                                    {{-- partner_code --}}
                                                    {{-- Spacer untuk customer ke kanan --}}
                                                    <span style="display:inline-block; min-width: 200px;"></span>
                                                    <span style="float:right;">{{ $group['customer'] }}</span>
                                                </td>
                                                <td style={{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}"></td>
                                                <td
                                                    style="padding:6px 8px; font-weight:bold; text-align:center; border-right:1px dashed #000; border-left:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">
                                                    {{ (fmod($group['total_ban'], 1) == 0) ? number_format($group['total_ban'], 0) : number_format($group['total_ban'], 2) }}
                                                </td>
                                                <td style= border-right:1px dashed #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}"></td>
                                                <td
                                                    style="padding:6px 8px; font-weight:bold; text-align:center; border-right:1px solid #000;{{ $loop->last ? ' border-bottom:1px solid #000;' : '' }}">
                                                    {{ (fmod($group['total_point'], 1) == 0) ? number_format($group['total_point'], 0) : number_format($group['total_point'], 2) }}
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
