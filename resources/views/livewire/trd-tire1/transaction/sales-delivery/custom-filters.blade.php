{{-- Debug info --}}
<!-- Custom Filters Loaded Successfully -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-gear"></i> Pengaturan Pengiriman
                </h5>
            </div>
            <div class="card-body">
                <!-- Filter Controls & Action Buttons - Single Row -->
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label for="tanggal_kirim" class="form-label">Tanggal Kirim</label>
                        <input type="date"
                               class="form-control"
                               id="tanggal_kirim"
                               wire:model="tanggalKirim"
                               max="{{ date('Y-m-d') }}">
                        @error('tanggalKirim')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Warehouse</label>
                        <select wire:model="warehouse" class="form-select">
                            <option value="">Pilih Warehouse</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh['value'] }}">{{ $wh['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="button"
                                class="btn btn-success"
                                wire:click="setDeliveryDate"
                                wire:loading.attr="disabled"
                                @if($this->getSelectedItemsCount() == 0) disabled @endif>
                            <span wire:loading.remove wire:target="setDeliveryDate">
                                <i class="bi bi-truck"></i> Kirim
                            </span>
                            <span wire:loading wire:target="setDeliveryDate">
                                <i class="bi bi-arrow-repeat spin"></i> Processing...
                            </span>
                        </button>
                    </div>
                    <div class="col-md-auto">
                        <button type="button"
                                class="btn btn-warning"
                                wire:click="cancelDeliveryDate"
                                wire:loading.attr="disabled"
                                @if($this->getSelectedItemsCount() == 0) disabled @endif>
                            <span wire:loading.remove wire:target="cancelDeliveryDate">
                                <i class="bi bi-x-octagon"></i> Batal Kirim
                            </span>
                            <span wire:loading wire:target="cancelDeliveryDate">
                                <i class="bi bi-arrow-repeat spin"></i> Processing...
                            </span>
                        </button>
                    </div>
                    <div class="col-md-auto">
                        <button type="button"
                                class="btn btn-danger"
                                wire:click="cancel"
                                wire:loading.attr="disabled"
                                @if($this->getSelectedItemsCount() == 0) disabled @endif>
                            <span wire:loading.remove wire:target="cancel">
                                <i class="bi bi-ban"></i> Cancel
                            </span>
                            <span wire:loading wire:target="cancel">
                                <i class="bi bi-arrow-repeat spin"></i> Processing...
                            </span>
                        </button>
                    </div>
                    <div class="col-md-auto">
                        <button type="button"
                                class="btn btn-info"
                                wire:click="unCancel"
                                wire:loading.attr="disabled"
                                @if($this->getSelectedItemsCount() == 0) disabled @endif>
                            <span wire:loading.remove wire:target="unCancel">
                                <i class="bi bi-arrow-counterclockwise"></i> Un-Cancel
                            </span>
                            <span wire:loading wire:target="unCancel">
                                <i class="bi bi-arrow-repeat spin"></i> Processing...
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Help Text with Selection Info -->
                <div class="mt-3 flex-grow-1 d-flex align-items-center justify-content-between">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Pilih satu atau lebih nota dari tabel di bawah, atur tanggal kirim dan warehouse,
                        kemudian klik tombol aksi yang diinginkan.
                    </small>
                    <div class="d-flex gap-2 align-items-center">
                        @if($this->getSelectedItemsCount() > 0)
                            <span class="badge bg-info">
                                <i class="fas fa-check-square me-1"></i>
                                {{ $this->getSelectedItemsCount() }} item dipilih
                            </span>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    wire:click="clearSelections"
                                    title="Clear Selection">
                                <i class="fas fa-times"></i>
                                Clear
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .badge {
        font-size: 0.875em !important;
    }
</style>

