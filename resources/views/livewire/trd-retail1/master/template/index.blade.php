<div>
    <x-ui-page-card title="Upload Materials via Excel">
        <form enctype="multipart/form-data" class="upload-form">
            <div class="form-group">
                <input type="file" wire:model="file" class="form-control" accept=".xlsx, .xls">
                @error('file') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="d-flex justify-content-end mt-3">
                <!-- Disable the button and show loading until file upload completes -->
                <div class="d-flex justify-content-end mt-3">
                    <!-- Change button text to 'Loading...' during both file upload and processing stages -->
                    <button type="button" class="btn btn-primary" wire:click="uploadExcel" wire:target="file,uploadExcel" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="file,uploadExcel">Upload</span>
                        <span wire:loading wire:target="file,uploadExcel">Loading...</span>
                    </button>
                </div>
            </div>
        </form>

        <div class="table-container mt-4">
            @livewire($currentRoute . '.index-data-table')
        </div>
    </x-ui-page-card>

    <style>
        /* Style for the form container */
        .upload-form {
            padding: 20px; /* Add padding around the form */
            border-radius: 5px;
            background-color: #f8f9fa; /* Light background color */
        }

        /* Style for the error message */
        .error {
            color: #e3342f; /* Red color for error */
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }

        /* Optional: additional padding for the table container */
        .table-container {
            padding-top: 20px;
        }
    </style>
</div>
