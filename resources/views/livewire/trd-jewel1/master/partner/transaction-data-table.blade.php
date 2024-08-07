<!-- resources/views/livewire/trd-jewel1/master/material/transaction-data-table.blade.php -->
<div>
    <div class="table-container">
        <x-ui-table id="transaction">
            <x-slot name="headers">
                <x-ui-th width="150">Customer/Supplier</x-ui-th>
                <x-ui-th width="150">Date</x-ui-th>
                <x-ui-th width="150">Transaction ID</x-ui-th>
                <x-ui-th width="150">Transaction Type</x-ui-th>
                <x-ui-th width="150">Harga</x-ui-th>
            </x-slot>
            <x-slot name="rows">
                @foreach ($data as $row)
                    <tr>
                        <x-ui-td>{{ $row->partner_name }}</x-ui-td>
                        <x-ui-td>{{ $row->tr_date }}</x-ui-td>
                        <x-ui-td>{{ $row->tr_id }}</x-ui-td>
                        <x-ui-td>
                            @switch($row->tr_type)
                                @case('BB')
                                    Buy Back
                                    @break
                                @case('SO')
                                    Nota Jual
                                    @break
                                @case('PO')
                                    Nota Beli
                                    @break
                                @default
                                    {{ $row->tr_type }}
                            @endswitch
                        </x-ui-td>
                        <x-ui-td>
                            @if ($row->tr_type == 'PO')
                                {{ dollar(currencyToNumeric($row->total_price)) }}
                            @else
                                {{ rupiah(currencyToNumeric($row->total_price)) }}
                            @endif
                        </x-ui-td>
                    </tr>
                @endforeach
            </x-slot>
        </x-ui-table>
    </div>
</div>
