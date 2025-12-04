@php
    $selectedCount = count($this->selectedRows ?? []);
    $hasSelection = $selectedCount > 0;
@endphp

<div class="row mb-3">
    <div class="col-12">
        <div class="card border-info shadow-sm">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt me-2"></i>Pengisian Nomor Faktur</span>
                <span class="badge bg-dark text-white fw-semibold">
                    {{ $selectedCount }} dipilih
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                    <small class="text-muted">
                        Pilih satu atau lebih surat jalan pada tabel, isi nomor faktur dan tanggal faktur langsung di sini, lalu simpan.
                    </small>
                    <div class="btn-group">
                        <button type="button"
                                class="btn btn-sm btn-primary"
                                wire:click="saveTaxInvoiceNumbers"
                                wire:loading.attr="disabled"
                                @disabled(!$hasSelection)>
                            <i class="bi bi-save me-1"></i> Simpan Nomor Faktur
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-danger"
                                wire:click="deleteTaxInvoiceNumbers"
                                wire:loading.attr="disabled"
                                @disabled(!$hasSelection)>
                            <i class="bi bi-eraser me-1"></i> Hapus Nomor Faktur
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                wire:click="clearSelections"
                                wire:loading.attr="disabled"
                                @disabled(!$hasSelection)>
                            <i class="bi bi-x-circle me-1"></i> Clear
                        </button>
                    </div>
                </div>

                @if(!$hasSelection)
                    <div class="alert alert-secondary mb-0 py-2">
                        Belum ada data yang dipilih. Centang surat jalan di kolom paling kiri untuk mulai mengisi nomor faktur.
                    </div>
                @else
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label mb-1 small text-muted">Nomor Faktur (digunakan untuk semua nota terpilih)</label>
                            <input type="text"
                                   class="form-control form-control-sm"
                                   placeholder="Masukkan nomor faktur"
                                   wire:model.defer="taxDocNumInput"
                                   maxlength="20"
                                   inputmode="numeric"
                                   pattern="[0-9]*">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label mb-1 small text-muted">Tanggal Faktur (digunakan untuk semua nota terpilih)</label>
                            <input type="date"
                                   class="form-control form-control-sm"
                                   placeholder="Masukkan tanggal faktur"
                                   wire:model.defer="taxDocDateInput">
                        </div>
                    </div>

                @endif
            </div>
        </div>
    </div>
</div>

