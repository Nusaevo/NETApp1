<div>
    {{-- @php
        use App\Services\TrdJewel1\Master\MasterService;

        $masterService = new MasterService();
    @endphp --}}
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <x-ui-expandable-card id="ReportFilterCard" title="Filter" :isOpen="true">
            <div class="card-body">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-6">
                            <x-ui-text-field type="date" label="Tanggal Awal" model="startDate" action="Edit" />
                        </div>
                        <div class="col-md-6">
                            <x-ui-text-field type="date" label="Tanggal Akhir" model="endDate" action="Edit" />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <x-ui-text-field-search label="Merk" model="merk" :selectedValue="$merk" :options="$merkOptions"
                                action="Edit" />
                        </div>
                        <div class="col-md-6">
                            <x-ui-text-field-search label="Jenis" model="jenis" :selectedValue="$jenis" :options="$jenisOptions"
                                action="Edit" />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <x-ui-text-field-search type="int" label="Customer" model="customer" :selectedValue="$customer"
                                :options="$customerOptions" action="Edit" />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <x-ui-text-field type="number" label="Qty Awal" model="startQty" action="Edit" />
                        </div>
                        <div class="col-md-6">
                            <x-ui-text-field type="number" label="Qty Akhir" model="endQty" action="Edit" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <div>
                    <x-ui-button clickEvent="reset" button-name="Reset" loading="true" action="Edit"
                        cssClass="btn-light" />
                </div>
                <div class="ml-2">
                    <x-ui-button clickEvent="search" button-name="Search" loading="true" action="Edit"
                        cssClass="btn-primary" />
                </div>
                <button type="button" class="btn btn-light text-capitalize border-0" onclick="printReport()">
                    <i class="fas fa-print text-primary"></i> Print
                </button>
            </div>
        </x-ui-expandable-card>

        <div id="print">
            <x-ui-table id="LaporanPenerimaan">
                <x-slot name="headers">
                    <th style="text-align: center;">No</th>
                    <th style="text-align: center;">Tanggal</th>
                    <th style="text-align: center;">Kode Barang</th>
                    <th style="text-align: center;">Kategori</th>
                    <th style="text-align: center;">Keterangan</th>
                    <th style="text-align: center;">Warna</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: center;">Harga</th>
                    <th style="text-align: center;">Total</th>
                </x-slot>

                <x-slot name="rows">
                    @foreach ($results as $res)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $res->tr_date }}</td>
                            <td>{{ $res->material_code }}</td>
                            <td>{{ $res->category }}</td>
                            <td>{{ $res->brand }} {{ $res->type_code }}</td>
                            <td>{{ $res->color_code }} - {{ $res->color_name }}</td>
                            <td style="text-align: right;">{{ number_format($res->qty, 0) }}</td>
                            <td style="text-align: right;">{{ rupiah($res->price) }}</td>
                            <td style="text-align: right;">{{ rupiah($res->total) }}</td>
                        </tr>
                    @endforeach
                </x-slot>
            </x-ui-table>
        </div>


    </x-ui-page-card>
</div>
