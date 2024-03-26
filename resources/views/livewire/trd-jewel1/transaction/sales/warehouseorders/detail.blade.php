
 <div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div>
        <a href="{{ route('sales_order_warehouse.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2"><i class="bi bi-arrow-left-circle fs-2 me-2"></i> Kembali</a>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>

    <div  id="kt_content_container" class="container-xxl mb-5" wire:ignor>
        <div class="card shadow-sm">
            <form wire:submit.prevent='store' class="form w-100">
                <div class="card-header cursor-pointer rotate">
                    <h3 class="card-title">Konfirmasi Gudang (wO)</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>
                <div wire:ignore>
                <div class="card-body">
                    <div class="mb-10">
                        <label class="form-label">Nota</label>
                        <input class='form-control' type="text" value="#{{ $this->sales_order->id}}" disabled>
                    </div>
                    <div class="mb-10">
                        <label class="form-label">Tanggal penjualan</label>
                        <input class='form-control' type="text" value="{{ $this->sales_order->transaction_date}}" disabled>
                    </div>

                    <div class="mb-10">
                        <label class="form-label">Payment</label>
                        <input class='form-control' type="text" value="{{ $this->sales_order->payment->name}}" disabled>
                    </div>

                    <div class="mb-10" >
                        <label class="form-label ">Customer</label>
                        <input class='form-control' type="text" value="{{ $this->sales_order->customer->name}}" disabled>
                    </div>

                </div>
                </div>
                <div class="card-body">
                    <div class="mb-10" >
                        <label class="form-label">Kategori Harga Customer : {{ $this->sales_order->customer->price_category->name}}</label>
                        <input type="hidden" wire:model.defer="input_headers.category_id" class=" @error('input_headers.category_id') is-invalid @enderror" />
                    </div>
                    <div class="post d-flex flex-column-fluid" id="kt_post">
                        <div id="kt_content_container" class="container-xxl">
                                    <div class="table-responsive mt-10">
                                        <table id="tbl" class="table table-striped table-hover gy-7 gs-7">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                                    <th class="min-w-300px">Barang</th>
                                                    <th class="min-w-5px">Satuan</th>
                                                    <th class="min-w-5px">Qty tersedia</th>
                                                    <th class="min-w-5px">Qty Jual</th>
                                                    <th class="min-w-5px">Qty Ambil</th>
                                                    <th class="min-w-10px">Gudang</th>
                                                </tr>
                                            </thead>
                                            <tbody >
                                                <div>
                                                    @foreach($inputs as $key => $input)
                                                        <tr>
                                                        <td class="border">
                                                            <div >
                                                                <input type="hidden" wire:model.defer='input_items.{{ $key }}.item_unit_id'>
                                                                    <div class="input-form ">
                                                                        <select class="form-select itemsearch form-control p-2" id="item-{{$key}}" >
                                                                                @if(isset($input_items[$key]['item_name']))
                                                                                <option value='input_items.{{ $key }}.item_unit_id'>{{$input_items[$key]['item_name']}}</option>
                                                                                @endif
                                                                        </select>
                                                                    </div>
                                                            </div>
                                                        </td>

                                                        <td class="border">
                                                            <div class="input-form @error('input_items.{{ $key}}.unit_name') has-error @enderror">
                                                                <input type="text" wire:model.defer='input_items.{{ $key }}.unit_name' class="form-control" disabled>
                                                                @error('input_items.{{ $key}}.unit_name')
                                                                    <div class="pristine-error text-primary-3 mt-1">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </td>

                                                        <td class="border">
                                                            <div class="input-form @error('input_items.{{ $key}}.qty_avail') has-error @enderror">
                                                                <input type="number" wire:model.defer='input_items.{{ $key }}.qty_avail' class="form-control"  min="0" max="9999999999.99" disabled>
                                                            </div>
                                                        </td>
                                                        <td class="border">
                                                            <div class="input-form @error('input_items.{{ $key}}.qty_sell') has-error @enderror">
                                                                <input type="number" wire:model.defer='input_items.{{ $key }}.qty_sell' class="form-control"  min="0" max="9999999999.99" disabled>
                                                            </div>
                                                        </td>

                                                        <td class="border">
                                                            <div class="input-form @error('input_items.{{ $key}}.qty_pick') has-error @enderror">
                                                                <input type="number" wire:model.defer='input_items.{{ $key }}.qty_pick' class="form-control" placeholder="10" required min="0" max="{{ $input_items[$key]['qty_sell'] }}" >
                                                                @error('input_items.{{ $key}}.qty_pick')
                                                                    <div class="pristine-error text-primary-3 mt-1">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </td>

                                                        <td class="border">
                                                            <div class="input-form @error('input_items.{{ $key}}.warehouse_id') has-error @enderror">
                                                                <select class="form-select @error('input_items.{{ $key}}.warehouse_id') is-invalid @enderror" wire:change="$emit('sales_order_detail_change_warehouse',{{ $key }} , $event.target.value)" wire:model="input_items.{{$key}}.warehouse_id" >
                                                                    @foreach($warehouse as $warehouses)
                                                                        <option value="{{$warehouses->id}}">{{$warehouses->name}}</option>
                                                                    @endforeach
                                                                </select>
                                                                @error('input_items.{{ $key}}.warehouse_id')
                                                                    <div class="pristine-error text-primary-3 mt-1">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </td>
                                                        </tr>
                                                    @endforeach
                                                </div>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                    </div>

                </div>
                <div class="card-footer">
                    @include('layout.customs.button-submit')
                </div>

        </div>
    </div>

    @include('layout.customs.modal-delete',[
        'custom_delete_title' => 'Apakah Anda yakin ingin membatalkan Sales Request ini?',
        'action' => 'delete',
        'destroy_listener' => 'sales_order_create_destroy'
    ])


</div>
