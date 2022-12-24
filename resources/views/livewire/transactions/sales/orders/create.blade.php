
 <div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div>
        <a href="{{ route('sales.order.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2"><i class="bi bi-arrow-left-circle fs-2 me-2"></i> Kembali</a>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>

    <div  id="kt_content_container" class="container-xxl mb-5" wire:ignor>
        <div class="card shadow-sm">
            <form wire:submit.prevent='store' class="form w-100">
                <div class="card-header cursor-pointer rotate">
                    <h3 class="card-title">Konfirmasi Penjualan (SO)</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>
                <div wire:ignore>
                <div class="card-body">
                    <div class="mb-10">
                        <label class="form-label required">Tanggal penjualan</label>
                        <input type="date" wire:model.defer="input_headers.date" class="form-control @error('input_headers.date') is-invalid @enderror" placeholder="Pilih tanggal pembelian"/>
                        @error('input_headers.date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-10">
                        <label class="required form-label">Payment</label>
                        <select class="form-select @error('input_headers.payment_id') is-invalid @enderror" wire:model.lazy="input_headers.payment_id">
                            <option selected value="" >-- Pilih Salah Satu --</option>
                            @foreach($payment as $payment)
                                <option value="{{$payment->id}}">{{$payment->name}}</option>
                            @endforeach
                        </select>
                        @error('input_headers.payment_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    {{-- <div class="mb-10">
                        <div class="row">
                            <div class="col-sm-12 col-md-6 col-lg-6">
                                <label class="form-label required">Gudang</label>
                                <select class="form-select @error('input_headers.warehouse') is-invalid @enderror" wire:model.lazy="input_headers.warehouse">
                                    <option selected value="" >-- Pilih Gudang --</option>
                                    @foreach($warehouse as $warehouse)
                                        <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-12 col-md-6 col-lg-6">
                                <label class="form-label required">Payment</label>
                                <select class="form-select @error('input_headers.warehouse') is-invalid @enderror" wire:model.lazy="input_headers.payment">
                                    <option selected value="" >-- Pilih pembayaran --</option>
                                    @foreach($payment as $payment)
                                        <option value="{{$payment->id}}">{{$payment->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div> --}}

                    <div class="mb-10" >
                        <label class="form-label required">Customer</label>
                        <div class="form-control">
                            <select class="customersearch form-control p-3" name="customer"></select>
                        </div>
                    </div>
                    <button href="#" wire:click.prevent="addInput()" class="btn btn-success mb-5"  >Tambah Item</button>

                </div>
                </div>
                <div class="card-body">
                    <div class="mb-10" >
                        <label class="form-label">Kategori Harga Customer : {{$category->name ?? ''}} </label>
                        <input type="hidden" wire:model.defer="input_headers.category_id" class=" @error('input_headers.category_id') is-invalid @enderror" />
                    </div>
                    <div class="post d-flex flex-column-fluid" id="kt_post">
                        <div id="kt_content_container" class="container-xxl">
                                    <div class="table-responsive mt-10">
                                        <table id="tbl" class="table table-striped table-hover gy-7 gs-7">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                                    <th class="min-w-300px">Barang</th>
                                                    <th class="min-w-10px">Harga</th>
                                                    <th class="min-w-5px">Qty</th>
                                                    <th class="min-w-15px">Sub Total</th>
                                                    <th class="min-w-10px">Gudang</th>
                                                    <th class="min-w-10px">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody >
                                                <div>
                                                    @foreach($inputs as $key => $input)
                                                        <tr id="div-{{$key}}">
                                                        <td class="border">
                                                            <div >
                                                                <input type="hidden" wire:model.defer='input_items.{{ $key }}.item_unit_id'>
                                                                    <div wire:ignore class="input-form ">
                                                                        <select  class="form-select itemsearch form-control p-2" id="item-{{$key}}" >
                                                                                {{-- @if(isset($input_items[$key]['item_name']))
                                                                                <option value='input_items.{{ $key }}.item_unit_id'>{{$input_items[$key]['item_name']}}</option>
                                                                                @endif --}}
                                                                        </select>
                                                                    </div>
                                                            </div>
                                                        </td>
                                                        <td class="border">
                                                            <div class="input-form @error('input_items.{{ $key}}.price') has-error @enderror">
                                                                <input type="number" wire:model.defer='input_items.{{ $key }}.price' class="form-control" wire:change="$emit('sales_order_create_change_price',{{ $key }} , $event.target.value)" placeholder="7500" required min="0" max="9999999999.99">
                                                                @error('input_items.{{ $key}}.price')
                                                                    <div class="pristine-error text-primary-3 mt-1">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </td>
                                                        <td class="border">
                                                            <div class="input-form @error('input_items.{{ $key}}.qty') has-error @enderror">
                                                                <input type="number" wire:model.defer='input_items.{{ $key }}.qty' class="form-control"  wire:change="$emit('sales_order_create_change_qty',{{ $key }} , $event.target.value)" placeholder="10" required min="0" max="9999999999.99">
                                                                @error('input_items.{{ $key}}.qty')
                                                                    <div class="pristine-error text-primary-3 mt-1">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </td>
                                                        <td class="border">
                                                            <div class="input-form @error('input_items.{{ $key}}.total') has-error @enderror">
                                                                <input type="text" wire:model.defer='input_items.{{ $key }}.total' class="form-control" placeholder="10" required min="0" max="9999999999.99" disabled>
                                                                @error('input_items.{{ $key}}.total')
                                                                    <div class="pristine-error text-primary-3 mt-1">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </td>

                                                        <td class="border">
                                                            <div class="input-form @error('input_items.{{ $key}}.warehouse_id') has-error @enderror">
                                                                <select class="form-select @error('input_items.{{ $key}}.warehouse_id') is-invalid @enderror" wire:model="input_items.{{$key}}.warehouse_id">
                                                                    <option selected value="" >--</option>
                                                                    @foreach($warehouse as $warehouses)
                                                                        <option value="{{$warehouses->id}}">{{$warehouses->name}}</option>
                                                                    @endforeach
                                                                </select>
                                                                @error('input_items.{{ $key}}.warehouse_id')
                                                                    <div class="pristine-error text-primary-3 mt-1">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </td>

                                                        <td>
                                                        <a  onclick="refresh({{ $key }})"  class="btn btn-primary btn-sm"><i class="bi bi-trash fs-2 me-2"></i></a>
                                                        </td>
                                                        </tr>
                                                    @endforeach
                                                </div>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                    </div>                <div class="card-footer">
                    @include('layout.customs.button-submit')
                </div>
            </form>
        </div>
    </div>

    @include('layout.customs.modal-delete',[
        'custom_delete_title' => 'Apakah Anda yakin ingin membatalkan Sales Request ini?',
        'action' => 'delete',
        'destroy_listener' => 'sales_order_create_destroy'
    ])


    <script type="text/javascript">
            function refresh(key){
                const element = document.getElementById('div-'+key);
                element.innerHTML = '';
                livewire.emit('sales_order_create_remove_input', key);
            }

            $('.customersearch').select2({
            placeholder: 'Select Customer',
            ajax: {
                url: '/search-customer',
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {
                                text: item.name,
                                id: item.id
                            }
                        })
                    };
                },
                cache: true
            }
            });

            $('.customersearch').on('change', function (e) {
                livewire.emit('sales_order_create_change_customer', e.target.value);
            });


            $(document).ready(function () {
                window.addEventListener('reApplySelect2', event => {
                    $('.itemsearch').select2({
                    placeholder: 'Select Item',
                    ajax: {
                        url: '/search-item',
                        dataType: 'json',
                        delay: 250,
                        processResults: function (data) {
                            return {
                                results: $.map(data, function (item) {
                                    return {
                                        text: item.name,
                                        id: item.id
                                    }
                                })
                            };
                        },
                        cache: true
                    }
                    });
                    $('.itemsearch').on('change', function (e) {
                        //divide by 2 because there are 2 classes in 1 select item
                        const itemClass = Array.from(document.querySelectorAll('.itemsearch'));
                        var index = itemClass.indexOf(e.target) / 2;
                        livewire.emit('sales_order_create_change_item',e.target.id, e.target.value,index);
                    });
                });
            });


    </script>
</div>
