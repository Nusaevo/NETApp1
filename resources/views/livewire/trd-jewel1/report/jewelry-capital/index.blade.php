<div>
    @php
        use App\Services\TrdJewel1\Master\MasterService;

        $masterService = new MasterService();
    @endphp
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <x-ui-expandable-card id="ReportFilterCard" title="Filter" :isOpen="true">
            <form wire:submit.prevent="search">
                <div class="card-footer d-flex justify-content-end">
                    <div>
                        <button type="button" class="btn btn-light text-capitalize border-0" onclick="printReport()">
                            <i class="fas fa-print text-primary"></i> Print
                        </button>
                    </div>
                </div>
            </form>
        </x-ui-expandable-card>
        <div id="print">
            <x-ui-table id="LaporanPenerimaan">
                <x-slot name="headers">
                    <th class="min-w-10px" style="text-align: center;">No</th>
                    <th class="min-w-300px" style="text-align: center;">Category</th>
                    <th class="min-w-100px" style="text-align: center;">Total Quantity</th>
                    <th class="min-w-100px" style="text-align: center;">Total Modal</th>
                </x-slot>

                <x-slot name="rows">
                    @foreach ($results as $res)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $res->category }}</strong>
                            </td>
                            <td>{{ number_format(currencyToNumeric($res->total_quantity), 0) }}</td>
                            <td>
                                @if ($res->category === 'SO')
                                    {{ rupiah(toNumberFormatter(currencyToNumeric($res->total_buying_price))) }}
                                @else
                                    {{ dollar(toNumberFormatter(currencyToNumeric($res->total_buying_price))) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-slot>
            </x-ui-table>
        </div>
    </x-ui-page-card>
</div>
