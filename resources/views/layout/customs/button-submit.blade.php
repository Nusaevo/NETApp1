
<div class="card-footer d-flex justify-content-end">
    <button wire:loading.remove type="{{ $button_type ?? 'submit' }}" class="btn btn-primary me-10"> {{ $button_text ?? 'Simpan' }} </button>
    <button wire:loading type="button" class="btn btn-primary" data-kt-indicator="on">
        <span class="indicator-progress">
            {{ $button_text_loading ?? 'Harap Tunggu...' }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
        </span>
    </button>
</div>
