<div>
    <x-ui-page-card title="Upload Materials via Excel">
        <div>
            <form wire:submit.prevent="uploadExcel" enctype="multipart/form-data">
                <div class="card-body">
                    <div class="row">
                        <!-- Standard file input field for uploading the Excel file -->
                        <div class="form-group">
                            <label for="file">Upload Excel</label>
                            <input type="file" id="file" wire:model="file" class="form-control">
                            @error('file') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Upload</button>
                </div>
            </form>

            <!-- Success or error notifications -->
            @if (session()->has('message'))
                <div class="alert alert-success">
                    {{ session('message') }}
                </div>
            @endif
        </div>

    </x-ui-page-card>
</div>
