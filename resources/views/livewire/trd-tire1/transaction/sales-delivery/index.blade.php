<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <div class="table-container">
            @livewire($currentRoute . '.index-data-table', ['selectedItems' => $selectedItems])
        </div>
    </x-ui-page-card>

    <!-- Modal untuk Set Tanggal Kirim -->
    <x-ui-dialog-box id="modalDeliveryDate" title="Set Tanggal Kirim">
        <x-slot name="header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </x-slot>
        <x-slot name="body">
            <div class="form-group">
                <div class="row">
                    <div class="col-md-6">
                        <label for="deliveryDate">Tanggal Kirim</label>
                        <input label="Tanggal Kirim" type="date"
                            class="form-control @error('tr_date') is-invalid @enderror" id="deliveryDate"
                            wire:model="tr_date">
                        @error('tr_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label tyope="hidden" for=""></label>
                        <x-ui-dropdown-select label="{{ $this->trans('wh_code') }}" model="inputs.wh_code"
                            :options="$warehouses" required="true" :action="$actionValue" />
                    </div>
                </div>
                <x-ui-table id="selectedItemsTable" padding="0px" margin="0px">
                    <x-slot name="headers">
                        <th>Nomor Nota</th>
                        <th>Nama</th>
                        <th>Kota</th>
                    </x-slot>
                    <x-slot name="rows">
                        @foreach ($selectedItems as $item)
                            <tr>
                                <td>{{ $item['nomor_nota'] }}</td>
                                <td>{{ $item['nama'] }}</td>
                                <td>{{ $item['kota'] }}</td>
                            </tr>
                        @endforeach
                    </x-slot>
                </x-ui-table>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-ui-button clickEvent="submitDeliveryDate" button-name="Kirim" loading="true" :action="$actionValue"
                cssClass="btn-primary" />
        </x-slot>
    </x-ui-dialog-box>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', function() {
            // Listener untuk membuka modal
            Livewire.on('open-modal-delivery-date', event => {
                $('#modalDeliveryDate').modal('show');
            });

            // Listener untuk menutup modal
            Livewire.on('close-modal-delivery-date', event => {
                $('#modalDeliveryDate').modal('hide');
            });

            // Handle modal hidden event
            $('#modalDeliveryDate').on('hidden.bs.modal', function() {
                @this.set('deliveryDate', '');
            });

            // Listener untuk submit delivery date
            Livewire.on('submitDeliveryDate', event => {
                @this.submitDeliveryDate();
            });
        });
    </script>
@endpush
