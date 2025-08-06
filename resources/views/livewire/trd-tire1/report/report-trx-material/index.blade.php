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
                            <x-ui-option model="point_flag" label="Semua Point" :options="['isPoint' => 'Ya']"
                                type="checkbox" layout="horizontal" :action="$actionValue" :checked="$point_flag"/>
                                {{-- @dump($point_flag) --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- End Filter Frame --}}

        <div id="print">
            <div>
                <br>
                {{-- <link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}"> --}}
                <div class="card">
                    <div class="card-body">
                        <div class="container mb-5 mt-3">
                            <div style="max-width:2480px; margin:auto; padding:20px;">
                                <h4>TOKO BAN CAHAYA TERANG - SURABAYA</h4>
                                <h3 style="text-decoration:underline; text-align:left;">
                                    {!! $menuName !!}
                                </h3>
                                <p style="text-align:left; margin-bottom:0;">
                                    <strong>Kode Program : {{ $category }}</strong>
                                </p>
                                <p style="text-align:left; margin-bottom:20px;">
                                    Periode:
                                    {{ $startCode ? \Carbon\Carbon::parse($startCode)->format('d-M-Y') : '-' }}
                                    s/d {{ $endCode ? \Carbon\Carbon::parse($endCode)->format('d-M-Y') : '-' }}
                                </p>
                                <table style="width:100%; border-collapse:collapse;">
                                    <thead>
                                        <tr>
                                            <th style="text-align:left; padding:4px 8px; border:1px solid #000;">
                                                Kode Brg.
                                            </th>
                                            <th style="text-align:left; padding:4px 8px; border:1px solid #000;">
                                                Nama Barang
                                            </th>
                                            <th style="text-align:center; padding:4px 8px; border:1px solid #000;">
                                                Point
                                            </th>
                                            <th style="text-align:center; padding:4px 8px; border:1px solid #000;">
                                                Qty Beli
                                            </th>
                                            <th style="text-align:center; padding:4px 8px; border:1px solid #000;">
                                                Point Beli
                                            </th>
                                            <th style="text-align:center; padding:4px 8px; border:1px solid #000;">
                                                Qty Jual
                                            </th>
                                            <th style="text-align:center; padding:4px 8px; border:1px solid #000;">
                                                Point Jual
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $no = 1; @endphp
                                        @foreach ($results as $row)
                                            <tr>
                                                <td style="padding:4px 8px; border:1px solid #000;">
                                                    {{ $row->kode_brg }}</td>
                                                <td style="padding:4px 8px; border:1px solid #000;">
                                                    {{ $row->nama_barang }}</td>
                                                <td style="padding:4px 8px; text-align:center; border:1px solid #000;">
                                                    {{ fmod($row->point, 1) == 0 ? number_format($row->point, 0) : number_format($row->point, 3) }}
                                                </td>
                                                <td style="padding:4px 8px; text-align:center; border:1px solid #000;">
                                                    {{ fmod($row->qty_beli, 1) == 0 ? number_format($row->qty_beli, 0) : number_format($row->qty_beli, 2) }}
                                                </td>
                                                <td style="padding:4px 8px; text-align:center; border:1px solid #000;">
                                                    {{ fmod($row->point_beli, 1) == 0 ? number_format($row->point_beli, 0) : number_format($row->point_beli, 2) }}
                                                </td>
                                                <td style="padding:4px 8px; text-align:center; border:1px solid #000;">
                                                    {{ fmod($row->qty_jual, 1) == 0 ? number_format($row->qty_jual, 0) : number_format($row->qty_jual, 2) }}
                                                </td>
                                                <td style="padding:4px 8px; text-align:center; border:1px solid #000;">
                                                    {{ fmod($row->point_jual, 1) == 0 ? number_format($row->point_jual, 0) : number_format($row->point_jual, 2) }}
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
