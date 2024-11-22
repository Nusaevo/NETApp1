<div>
    <x-ui-page-card title="Upload Materials via Excel">
        <!-- File Upload Button -->
        <div class="form-group position-relative">
            <!-- Button with Loading Indicator -->
            <button id="btnUploadExcel" class="btn btn-secondary" wire:loading.attr="disabled" wire:target="uploadExcel" onclick="triggerFileInput()">
                <span wire:loading.remove>
                    <i class="bi bi-upload"></i> Upload Excel
                </span>
                <span wire:loading>
                    <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
                    Loading...
                </span>
            </button>

            <!-- Hidden File Input -->
            <input type="file" id="excelInput" accept=".xlsx, .xls" style="display: none;" onchange="handleExcelUpload(event)">
        </div>

        <!-- Table Section -->
        <div class="table-container mt-4">
            <div wire:poll.5s="pollRefresh">
                @livewire('trd-retail1.master.template.index-data-table')
            </div>
        </div>
    </x-ui-page-card>

    <!-- JavaScript -->
    @push('scripts')
    <script>
        function triggerFileInput() {
            const input = document.getElementById('excelInput');
            input.click();
        }

        function handleExcelUpload(event) {
            const file = event.target.files[0];
            if (file) {
                if (!['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'].includes(file.type)) {
                    alert('Invalid file type. Please upload an Excel file.');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    // Dispatch file content to Livewire
                    Livewire.dispatch('uploadExcel', {
                        fileData: e.target.result,
                        fileName: file.name
                    });
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
    @endpush
</div>
