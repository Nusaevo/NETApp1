<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">

        <div class="table-container">
            @livewire($currentRoute.'.index-data-table')
        </div>
    </x-ui-page-card>

    <!-- Modal untuk Set Tanggal Proses -->
    <x-ui-dialog-box id="openlProsesDate" title="Set Tanggal Proses" width="600px" eight="400px" onOpened="openPartnerDialogBox" onClosed="closePartnerDialogBox">
        <x-slot name="header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </x-slot>
        <x-slot name="body">
            <div class="form-group">
                <div class="row">
                    <div class="col-md-6">
                        <label for="prosesDate">Tanggal Proses</label>
                        <input label="Tanggal Proses" type="date"
                            class="form-control @error('print_date') is-invalid @enderror" id="prosesDate"
                            wire:model="print_date">
                        @error('print_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
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
            <x-ui-button clickEvent="submitProsesDate" button-name="Set Tanggal" loading="true" :action="$actionValue"
                cssClass="btn-primary" />
        </x-slot>
    </x-ui-dialog-box>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', function() {
            // Listener untuk membuka modal
            Livewire.on('openProsesDateModal', event => {
                $('#modalProsesDate').modal('show');
            });

            // Listener untuk menutup modal
            Livewire.on('closeProsesDateModal', event => {
                $('#modalProsesDate').modal('hide');
            });

            // Handle modal hidden event
            $('#modalProsesDate').on('hidden.bs.modal', function() {
                @this.set('print_date', '');
            });


        });
    </script>
@endpush
