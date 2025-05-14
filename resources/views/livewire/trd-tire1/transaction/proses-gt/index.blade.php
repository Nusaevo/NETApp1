<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <div class="table-container">
            @livewire($currentRoute . '.index-data-table')
        </div>
    </x-ui-page-card>

    <!-- Modal untuk Proses GT -->
    <x-ui-dialog-box id="modalProsesGT" title="Proses GT">
        <x-slot name="header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </x-slot>
        <x-slot name="body">
            <div class="form-group">
                <div class="row ">
                    <div class="col">
                        <x-ui-text-field label="Nomor Nota GT" model="gt_tr_code" type="text" :action="$actionValue"
                            required="true" enabled="true" clickEvent="setNotaGT" buttonName="Set Nota GT" />
                    </div>
                    <div class="col">
                        <x-ui-dropdown-select label="Customer Point" model="gt_partner_code" :options="$partners"
                            required="true" />
                    </div>
                    <div class="col-auto">
                        <x-ui-button clickEvent="fillCustomerPoint" button-name="Customer Nota"
                            cssClass="btn-secondary btn-sm" />
                    </div>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-ui-button clickEvent="submitProsesGT" button-name="Proses" loading="true" :action="$actionValue"
                cssClass="btn-primary" />
        </x-slot>
    </x-ui-dialog-box>

    <!-- Modal untuk Proses Nota -->
    <x-ui-dialog-box id="modalProsesNota" title="Proses Nota">
        <x-slot name="body">
            <div class="form-group">
                <div class="row">
                    <x-ui-dropdown-select label="SR Code" model="sr_code" :options="$sr_codes" required="true"
                        onChanged="onSrCodeChanged"  :action="$actionValue"/>
                    <x-ui-text-field label="Tanggal Nota Awal" model="start_date" type="date" required="true" />
                    <x-ui-text-field label="Tanggal Nota Akhir" model="end_date" type="date" required="true" />
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-ui-button clickEvent="submitProsesNota" button-name="Proses" loading="true" cssClass="btn-primary" />
        </x-slot>
    </x-ui-dialog-box>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', function() {
            // Listener untuk membuka modal
            Livewire.on('open-modal-proses-gt', event => {
                $('#modalProsesGT').modal('show');
            });

            // Listener untuk menutup modal
            Livewire.on('close-modal-proses-gt', event => {
                $('#modalProsesGT').modal('hide');
            });

            // Handle modal hidden event
            $('#modalProsesGT').on('hidden.bs.modal', function() {
                @this.set('gt_tr_code', '');
                @this.set('gt_partner_code', []);
            });

            // Listener untuk membuka modal Proses Nota
            Livewire.on('open-modal-proses-nota', event => {
                $('#modalProsesNota').modal('show');
            });

            // Listener untuk menutup modal Proses Nota
            Livewire.on('close-modal-proses-nota', event => {
                $('#modalProsesNota').modal('hide');
            });
        });
    </script>
@endpush
