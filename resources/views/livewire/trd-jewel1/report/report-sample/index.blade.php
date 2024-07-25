<div>
    <x-ui-page-card title="Report Sample" >
        <x-ui-expandable-card id="ReportFilterCard" title="Filter" :isOpen="true">
            <form wire:submit.prevent="search">
                    <div class="card-body">
                        <x-ui-text-field label="Cari Nama Barang" model="inputs.name" type="text" action="Edit"  span='Full'/>

                        {{-- <x-ui-dropdown-select label="Item Category"
                        clickEvent="refreshItemCategory"
                        model="inputs.category_item_id"
                        :options="$categories"
                        :selectedValue="$inputs['category_item_id']"
                        required="true"
                        action="Edit"
                        span='Full'
                        onChanged="resetResult"/> --}}

                        <x-ui-text-field label="Tanggal Mulai:" model="dateStart" type="date" action="Edit" span='Half' />
                        <x-ui-text-field label="Tanggal Akhir:" model="dateEnd" type="date" action="Edit" span='Half'/>

                    </div>

                    <div class="card-footer d-flex justify-content-end">
                        <div>
                            <x-ui-button clickEvent="search" button-name="Search" loading="true" action="Edit" cssClass="btn-primary" />
                        </div>
                    </div>
            </form>
            <x-ui-table id="ReportTable">
                {{-- <x-slot name="title">
                    Detail
                </x-slot> --}}
                <x-slot name="headers">
                    <th class="min-w-300px">Barang</th>
                    {{-- <th class="min-w-100px">Jumlah Terjual</th>
                    <th>Total Pendapatan</th>
                    <th>Nomor Nota Jual</th>
                    <th>Pelanggan</th> --}}
                </x-slot>

                <x-slot name="rows">
                    @foreach($results as $res)
                    <tr>
                        <td>{{ $res->descr }}</td>
                        {{-- <td>{{ number_format($item->total_qty, 0)}}</td>
                        <td>{{ rupiah($item->total_penjualan) }}</td>
                        <td>{{ $res->nomor_nota_jual }}</td>
                        <td>{{ $res->pelanggan }}</td> --}}
                    </tr>
                    @endforeach
                </x-slot>

            </x-ui-table>
        </x-ui-expandable-card>
    </x-ui-page-card>
</div>
