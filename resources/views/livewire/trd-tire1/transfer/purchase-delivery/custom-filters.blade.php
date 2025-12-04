<div x-data="{
    selectedCount: 0,
    updateCount() {
        try {
            const tbody = document.querySelector('table tbody, tbody');
            let count = 0;

            if (tbody) {
                const checkboxes = tbody.querySelectorAll('input[type=\"checkbox\"]:checked');
                count = checkboxes.length;
            } else {
                const allCheckboxes = document.querySelectorAll('input[type=\"checkbox\"][wire\\:key*=\"selectedItems\"]:checked');
                const headerCheckbox = document.querySelector('thead input[type=\"checkbox\"][wire\\:key*=\"selectedItems\"]');
                count = allCheckboxes.length;
                if (headerCheckbox && headerCheckbox.checked) {
                    count = Math.max(0, count - 1);
                }
            }

            this.selectedCount = count;
        } catch (e) {
            this.selectedCount = 0;
        }
    }
}"
x-init="
    updateCount();
    const interval = setInterval(() => updateCount(), 250);
    const handleUpdate = () => setTimeout(() => updateCount(), 30);
    document.addEventListener('change', handleUpdate, true);
    document.addEventListener('click', handleUpdate, true);
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('message.processed', () => setTimeout(() => updateCount(), 100));
    }
    $el.addEventListener('alpine:destroy', () => {
        clearInterval(interval);
        document.removeEventListener('change', handleUpdate, true);
        document.removeEventListener('click', handleUpdate, true);
    });
"
wire:key="transfer-custom-filter">
    <div class="row mb-3">
        <div class="col-12 text-start">
            <button type="button"
                    class="btn btn-primary"
                    :class="selectedCount === 0 ? 'btn-secondary' : 'btn-primary'"
                    wire:click="transferKeCTMS"
                    wire:loading.attr="disabled"
                    :disabled="selectedCount === 0"
                    x-bind:disabled="selectedCount === 0">
                <span wire:loading.remove wire:target="transferKeCTMS">
                    <i class="bi bi-arrow-left-right me-1"></i> Transfer ke CTMS
                </span>
                <span wire:loading wire:target="transferKeCTMS">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Transferring...
                </span>
            </button>
        </div>
    </div>
</div>

