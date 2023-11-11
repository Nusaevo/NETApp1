
<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div>
        <a href="{{ route('sales_order_final.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2"><i class="bi bi-arrow-left-circle fs-2 me-2"></i>Kembali </a>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>

    <div  id="kt_content_container" class="container-xxl mb-5" wire:ignor>
        <div class="card shadow-sm">
            <form wire:submit.prevent='store' class="form w-100">
                <div class="card-header cursor-pointer rotate">
                    <h3 class="card-title">Ubah status untuk mengedit nota kembali</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>
                <div wire:ignore>
                <div class="card-body">
                    <div class="mb-10">
                        <label class="required form-label">Status</label>
                        <select class="form-select" wire:model.defer="input_headers.is_finished">
                            <option value="0" {{ $input_headers['is_finished'] == 0 ? 'selected' : '' }}>Belum Selesai</option>
                            <option value="1" {{ $input_headers['is_finished'] == 1 ? 'selected' : '' }}>Selesai</option>
                        </select>
                    </div>
                    @include('layout.customs.button-submit')
                    <div class="mb-10">
                        <label class="form-label">Nota</label>
                        <input class='form-control' type="text" value="#{{ $this->sales_order->id}}" disabled>
                    </div>
                    <div class="mb-10">
                        <label class="form-label">Tanggal penjualan</label>
                        <input class='form-control' type="text" value="{{ $this->sales_order->transaction_date}}" disabled>
                    </div>

                    <div class="mb-10" >
                        <label class="form-label ">Customer</label>
                        <input class='form-control' type="text" value="{{ $this->sales_order->customer->name}}" disabled>
                    </div>

                    <div class="mb-10">
                        <label class="required form-label">Payment</label>
                        <select class="form-select @error('input_headers.payment_id') is-invalid @enderror" wire:model.lazy="input_headers.payment_id" disabled>
                            @if ($this->input_headers['payment_id'] ==  $this->sales_order->payment_id)
                                @foreach($payment as $payment)
                                    <option value="{{$payment->id}}" selected>{{$payment->name}}</option>
                                @endforeach
                            @else
                                @foreach($payment as $payment)
                                    <option value="{{$payment->id}}">{{$payment->name}}</option>
                                @endforeach
                            @endif
                        </select>
                        @error('input_headers.payment_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                </div>
                <div class="card-body">
                    <div class="mb-10" >
                        <label class="form-label">Kategori Harga Customer : {{$sales_order->customer->price_category->name ?? ''}} </label>
                        <input type="hidden" wire:model.defer="input_headers.category_id" class=" @error('input_headers.category_id') is-invalid @enderror" />
                    </div>
                    <div class="post d-flex flex-column-fluid" id="kt_post">
                        <div id="kt_content_container" class="container-xxl">
                                    <div class="table-responsive mt-10">
                                        <table id="tbl" class="table table-striped table-hover gy-7 gs-7">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                                    <th class="min-w-50px">No</th>
                                                    <th class="min-w-300px">Barang</th>
                                                    <th class="min-w-10px">Harga</th>
                                                    <th class="min-w-5px">Qty</th>
                                                    <th class="min-w-15px">Sub Total</th>
                                                    <th class="min-w-10px">Diskon</th>
                                                </tr>
                                            </thead>
                                            <tbody >
                                                <div>
                                                    @foreach($inputs as $key => $input)
                                                        <tr id="div-{{$key}}">
                                                            <td class="border">
                                                                {{$key+1}}
                                                            </td>
                                                            <td class="border">
                                                                <div >
                                                                    <input type="hidden" wire:model.defer='input_items.{{ $key }}.item_unit_id'>
                                                                        <div wire:ignore class="input-form ">
                                                                            <select  class="form-select itemsearch form-control p-2" id="item-{{$key}}" >
                                                                                    @if(isset($input_items[$key]['item_name']))
                                                                                    <option value='input_items.{{ $key }}.item_unit_id'>{{$input_items[$key]['item_name']}}</option>
                                                                                    @endif
                                                                            </select>
                                                                        </div>
                                                                </div>
                                                            </td>
                                                        <td class="border">
                                                                <input type="number" wire:model.defer='input_items.{{ $key }}.price' class="form-control @error("input_items.$key.price") is-invalid @enderror"  wire:change="$emit('sales_order_detail_change_price',{{ $key }} , $event.target.value)" placeholder="7500" required disabled>
                                                                @error("input_items.$key.price") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                        </td>
                                                        <td class="border">
                                                                <input type="number" wire:model.defer='input_items.{{ $key }}.qty' class="form-control @error("input_items.$key.qty") is-invalid @enderror"  wire:change="$emit('sales_order_detail_change_qty',{{ $key }} , $event.target.value)" placeholder="10" required disabled>
                                                                  @error("input_items.$key.qty") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                        </td>
                                                        <td class="border">
                                                                <input type="text" wire:model.defer='input_items.{{ $key }}.total' class="form-control" placeholder="10" required disabled>
                                                        </td>
                                                        <td class="border">
                                                            <input type="number" wire:model.defer='input_items.{{ $key }}.discount' class="form-control" required disabled>
                                                        </td>
                                                        <td>
                                                        </td>
                                                        </tr>
                                                    @endforeach
                                                    <tr>
                                                        <td align="right" colspan="4">Total Harga</td>
                                                        <td align="left" colspan="3"> {{ rupiah($total_amount) }}</td>
                                                    </tr>
                                                </div>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                    </div><div class="card-footer">
                </div>
            </form>
        </div>
    </div>

</div>
