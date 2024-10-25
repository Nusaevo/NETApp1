<!-- resources/views/livewire/trd-jewel1/master/material/transaction-data-table.blade.php -->
<div>
    <div class="table-container">
        <x-ui-table id="transaction">
            <x-slot name="headers">
                <x-ui-th width="150">Date</x-ui-th>
                <x-ui-th width="150">Transaction ID</x-ui-th>
                <x-ui-th width="150">Transaction Type</x-ui-th>
                <x-ui-th width="150">Material Codes</x-ui-th>
                <x-ui-th width="150">Harga</x-ui-th>
            </x-slot>
            <x-slot name="rows">
                @foreach ($data as $index => $row)
                <tr wire:key="transaction-{{ $index }}">
                    <x-ui-td>{{ $row->tr_date }}</x-ui-td>
                    <x-ui-td>
                        @php
                            $trLink = '';
                            switch ($row->tr_type) {
                                case 'BB': // Buy Back
                                    $trLink = '<a href="' . route('TrdRetail2.Transaction.Buyback.Detail', [
                                        'action' => encryptWithSessionKey('Edit'),
                                        'objectId' => encryptWithSessionKey($row->id)
                                    ]) . '">' . $row->tr_id . '</a>';
                                    break;
                                case 'SO': // Sales Order
                                    $trLink = '<a href="' . route('TrdRetail2.Transaction.SalesOrder.Detail', [
                                        'action' => encryptWithSessionKey('Edit'),
                                        'objectId' => encryptWithSessionKey($row->id)
                                    ]) . '">' . $row->tr_id . '</a>';
                                    break;
                                case 'PO': // Purchase Order
                                    $trLink = '<a href="' . route('TrdRetail2.Procurement.PurchaseOrder.Detail', [
                                        'action' => encryptWithSessionKey('Edit'),
                                        'objectId' => encryptWithSessionKey($row->id)
                                    ]) . '">' . $row->tr_id . '</a>';
                                    break;
                                default:
                                    $trLink = $row->tr_id;
                                    break;
                            }
                        @endphp

                        {!! $trLink !!} <!-- Tampilkan link transaksi yang dihasilkan -->
                    </x-ui-td>

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
                        @php
                            if ($row->tr_type == 'BB') {
                                $details = \App\Models\TrdRetail2\Transaction\ReturnDtl::where('tr_id', $row->tr_id)
                                            ->where('tr_type', $row->tr_type)
                                            ->get();
                            } else {
                                $details = \App\Models\TrdRetail2\Transaction\OrderDtl::where('tr_id', $row->tr_id)
                                            ->where('tr_type', $row->tr_type)
                                            ->get();
                            }

                            $links = $details->map(function ($detail) {
                                return '<a href="' . route('TrdRetail2.Master.Material.Detail', [
                                    'action' => encryptWithSessionKey('Edit'),
                                    'objectId' => encryptWithSessionKey($detail->matl_id)
                                ]) . '">' . $detail->matl_code . '</a>';
                            })->implode(', ');
                        @endphp

                        {!! $links !!} <!-- Tampilkan link HTML yang dihasilkan -->
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
