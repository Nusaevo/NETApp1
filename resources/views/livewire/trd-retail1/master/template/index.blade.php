<div>
    <x-ui-page-card title="{!! $menuName !!}">
        <!-- File Upload Button -->
        <div class="form-group position-relative">
            <button id="btnUploadExcel" class="btn btn-secondary" onclick="triggerFileInput()">
                <span id="uploadText">
                    <i class="bi bi-upload"></i> Upload Excel
                </span>
                <span id="loadingText" style="display:none;">
                    <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
                    Loading...
                </span>
            </button>
            <!-- Hidden File Input -->
            <input type="file" id="excelInput" accept=".xlsx, .xls" style="display: none;"
                onchange="handleExcelUpload(event)">
        </div>

        <!-- Table Section -->
        <div class="table-container mt-4">
            <div wire:poll.5s="pollRefresh">
                @livewire('trd-retail1.master.template.index-data-table')
            </div>
        </div>
    </x-ui-page-card>

    @push('scripts')
        <script>
                function triggerFileInput() {
                    document.getElementById('excelInput').click();
                }

                function handleExcelUpload(event) {
                    const file = event.target.files[0];
                    if (file) {
                        if (!['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel'
                            ]
                            .includes(file.type)) {
                            alert('Invalid file type. Please upload an Excel file.');
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            toggleLoading(true);
                            // Manually dispatch the file data to Livewire
                            Livewire.dispatch('uploadExcel', {
                            fileData: e.target.result,
                            fileName: file.name
                        });
                        };
                        reader.readAsDataURL(file);
                    }
                }

                function resetFileInput() {
                    const input = document.getElementById('excelInput');
                    input.value = ''; // Clear the file input
                }

                function toggleLoading(show) {
                    const loadingText = document.getElementById('loadingText');
                    const uploadText = document.getElementById('uploadText');
                    if (show) {
                        loadingText.style.display = '';
                        uploadText.style.display = 'none';
                    } else {
                        loadingText.style.display = 'none';
                        uploadText.style.display = '';
                    }
                }

                // Handle reset after Livewire emits a custom browser event
                window.addEventListener('excelUploadComplete', function() {
                    toggleLoading(false); // Stop loading indicator
                    resetFileInput(); // Reset file input
                });
        </script>
    @endpush
</div>
