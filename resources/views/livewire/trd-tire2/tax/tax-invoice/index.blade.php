<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <div class="table-container">
            @livewire($currentRoute . '.index-data-table')
        </div>
    </x-ui-page-card>

        <!-- Modal untuk Input Nomor Faktur -->
    <x-ui-dialog-box id="modalNomorFaktur" title="Input Nomor Faktur">
        <x-slot name="header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </x-slot>
        <x-slot name="body">
            <div class="form-group">
                @if($actionType !== 'delete')
                <div class="row">
                    <div class="col-md-12">
                        <label for="taxDocNum">Nomor Faktur</label>
                        <input type="number"
                            class="form-control @error('taxDocNum') is-invalid @enderror"
                            id="taxDocNum"
                            wire:model="taxDocNum"
                            placeholder="Masukkan nomor faktur">
                        @error('taxDocNum')
                            <div class="invalid-feedback">
                                {{ $message ?? 'Nomor faktur harus diisi' }}
                            </div>
                        @enderror
                    </div>
                </div>
                @else
                <div class="alert alert-warning">
                    <strong>Konfirmasi:</strong> Apakah Anda yakin ingin menghapus nomor faktur untuk nota-nota berikut?
                </div>
                @endif

                <x-ui-table id="selectedItemsTable" padding="0px" margin="0px">
                    <x-slot name="headers">
                        <th>Nomor Nota</th>
                        <th>Nama Customer</th>
                        <th>Total Amount</th>
                    </x-slot>
                    <x-slot name="rows">
                        @foreach ($selectedItems as $item)
                            <tr>
                                <td>{{ $item['nomor_nota'] }}</td>
                                <td>{{ $item['nama'] }}</td>
                                <td>{{ $item['total_amt'] }}</td>
                            </tr>
                        @endforeach
                    </x-slot>
                </x-ui-table>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-ui-button clickEvent="submitNomorFaktur" button-name="{{ $actionType === 'delete' ? 'Hapus' : 'Simpan' }}" loading="true" :action="$actionValue"
                cssClass="{{ $actionType === 'delete' ? 'btn-danger' : 'btn-primary' }}" />
        </x-slot>
    </x-ui-dialog-box>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', function() {
            // Listener untuk membuka modal nomor faktur
            Livewire.on('open-modal-nomor-faktur', event => {
                $('#modalNomorFaktur').modal('show');
            });

            // Listener untuk menutup modal nomor faktur
            Livewire.on('close-modal-nomor-faktur', event => {
                $('#modalNomorFaktur').modal('hide');
            });

            // Handle modal hidden event
            $('#modalNomorFaktur').on('hidden.bs.modal', function() {
                @this.set('taxDocNum', '');
            });

            // Listener untuk refresh datatable
            Livewire.on('refreshDatatable', event => {
                @this.$wire.$refresh();
            });

            Livewire.on('openPrintPdf', event => {
                window.location.href = '{{ route('TrdTire1.Tax.TaxInvoice.PrintPdf', ['action' => 'Print']) }}?orders=' + JSON.stringify(event.orders);
            });
        });
    </script>
@endpush
