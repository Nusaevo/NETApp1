<div>
    @php
    use App\Services\TrdJewel1\Master\MasterService;

    $masterService = new MasterService();
    @endphp
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <x-ui-expandable-card id="ReportFilterCard" title="Filter" :isOpen="true">
            <form wire:submit.prevent="search">
                <div class="card-body">
                    <div class="row">
                        <x-ui-dropdown-select label="Cari Kategori Barang" model="category" :options="$materialCategories1" action="Edit" />
                        <x-ui-text-field label="Kode Awal:" model="startCode" type="number" action="Edit" />
                        <x-ui-text-field label="Kode Akhir:" model="endCode" type="number" action="Edit"/>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <div>
                        <x-ui-button clickEvent="search" button-name="Search" loading="true" action="Edit" cssClass="btn-primary" />
                        <button type="button" class="btn btn-light text-capitalize border-0" onclick="printInvoice()">
                            <i class="fas fa-print text-primary"></i> Print
                        </button>
                </div>
            </form>
        </x-ui-expandable-card>
        <div id="print">
            <x-ui-table id="LaporanPenerimaan">
                <x-slot name="headers">
                    <th class="min-w-10px" style="text-align: center;">No</th>
                    <th class="min-w-100" style="text-align: center;">Code</th>
                    <th class="min-w-200px" style="text-align: center;">Foto</th>
                    <th class="min-w-300px" style="text-align: center;">Descr</th>
                    <th class="min-w-100px" style="text-align: center;">Modal</th>
                    <th class="min-w-300px" style="text-align: center;">Jual</th>
                </x-slot>

                <x-slot name="rows">
                    @foreach($results as $res)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $res->material_code }}</td>
                            <td>
                                @php
                                    $imageUrl = $res->file_url
                                        ? config('app.storage_url') . '/' . ltrim($res->file_url, '/')
                                        : 'https://via.placeholder.com/200';

                                @endphp
                                <img src="{{ $imageUrl }}" alt="Material Photo" style="width: 100px; height: 110px;">
                            </td>


                            <td>
                                @if(!empty($res->category2))
                                <strong>{{ $masterService->GetMatlCategory1String($this->appCode, $res->category) }}
                                    {{ $masterService->GetMatlCategory2String($this->appCode, $res->category2) }}</strong>
                                @endif

                                @if(!empty($res->material_gold))
                                <br>{{ numberFormat($res->material_gold, 2) }} Gram
                                @endif

                                @if(!empty($res->material_carat))
                                <br>{{ $masterService->GetMatlJewelPurityString($this->appCode, $res->material_carat) }}
                                @endif

                                @if(!empty($res->material_descr))
                                    <br>{{ e($res->material_descr) }}
                                @endif
                            </td>
                            <td>{{ dollar(toNumberFormatter(currencyToNumeric($res->price)))  }}</td>
                            <td>
                                @if(!empty($res->no_nota))
                                No Nota : {{ $res->no_nota }}
                                <br>
                                Customer :  {{  $res->partner_name }}
                                <br>
                                Harga Jual : {{ rupiah(toNumberFormatter(currencyToNumeric($res->selling_price)))  }}
                                <br>
                                Tanggal :   {{  $res->tr_date }}
                                @endif

                            </td>
                        </tr>
                    @endforeach
                </x-slot>
            </x-ui-table>

        </div>


    </x-ui-page-card>
</div>
<style>
    @media print {
        body * {
            visibility: hidden;
        }

        #print, #print * {
            visibility: visible;
        }

        #print {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: auto;
            box-sizing: border-box;
        }

        .btn, .d-flex {
            display: none;
        }

        #LaporanPenerimaan {
            border: none;
            box-shadow: none;
            margin: 0;
            padding: 5mm 10mm;
            height: auto;
            page-break-after: always;
        }
    }

</style>
<script type="text/javascript">
    function printInvoice() {
        window.print();
    }
</script>
