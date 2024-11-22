<div>
    <div>
        <x-ui-page-card title="Upload Materials via Excel">
            <!-- File Upload Form -->
            <div>
                <form wire:submit.prevent="uploadExcel">
                    <div class="d-flex align-items-center">
                        <!-- Input File -->
                        <div class="flex-grow-1">
                            <input type="file" wire:model="file" accept=".xlsx,.xls" class="form-control">
                            @error('file')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Upload Button -->
                        <div class="ms-3">
                            <button type="submit" class="btn btn-primary" wire:click.prevent="uploadExcel" wire:loading.attr="disabled" wire:target="uploadExcel">
                                <span wire:loading.remove wire:target="uploadExcel">Upload Excel</span>
                                <span wire:loading wire:target="uploadExcel">
                                    <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
                                    Uploading...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>


                <!-- Success/Failure Messages -->
                @if (session()->has('message'))
                    <div class="alert alert-success mt-3">{{ session('message') }}</div>
                @endif

                @if (session()->has('error'))
                    <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                @endif
            </div>



            <!-- Table Section -->
            <div class="table-container mt-4">
                <div wire:poll.5s="pollRefresh">
                    @livewire('trd-retail1.master.template.index-data-table')
                </div>
            </div>
        </x-ui-page-card>
    </div>

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
