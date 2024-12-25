
<div>
<x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">

    <div class="table-container">
        @livewire($currentRoute.'.index-data-table')
    </div>

    <x-ui-dialog-box id="syncModal" title="Image Sync Process">
        <x-slot name="body">
            <div id="sync-progress">
                <p>Syncing images... Please wait.</p>
                <div class="progress">
                    <div id="sync-progress-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <p id="sync-status-text" class="mt-2">Starting...</p>
            </div>
            <hr>
            <h6>Synced Images:</h6>
            <ul id="synced-images-list" style="max-height: 200px; overflow-y: auto;">
                <!-- Daftar gambar akan diperbarui dengan JavaScript -->
            </ul>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="sync-close-btn" disabled>Close</button>
        </x-slot>
    </x-ui-dialog-box>
    @push('scripts')
    <script>
    document.addEventListener('livewire:init', () => {
            Livewire.on('openSyncModal', () => {
                const syncModal = new bootstrap.Modal(document.getElementById('syncModal'));
                syncModal.show();
                document.getElementById('sync-progress-bar').style.width = '0%';
                document.getElementById('sync-status-text').innerText = 'Starting...';
                document.getElementById('synced-images-list').innerHTML = '';
                document.getElementById('failed-images-list').innerHTML = '';
                document.getElementById('sync-close-btn').disabled = true;
            });

            Livewire.on('updateSyncProgress', (progress) => {
                const progressBar = document.getElementById('sync-progress-bar');
                progressBar.style.width = progress + '%';
                progressBar.setAttribute('aria-valuenow', progress);
            });

            Livewire.on('pushSyncedImage', (image) => {
                const syncedList = document.getElementById('synced-images-list');
                const listItem = document.createElement('li');
                listItem.textContent = `Material ID: ${image.material_id}, File: ${image.file_name}`;
                syncedList.appendChild(listItem);
            });

            Livewire.on('pushFailedImage', (image) => {
                const failedList = document.getElementById('failed-images-list');
                const listItem = document.createElement('li');
                listItem.textContent = `File: ${image.file_name}, Error: ${image.error}`;
                failedList.appendChild(listItem);
            });

            Livewire.on('syncComplete', () => {
                document.getElementById('sync-close-btn').disabled = false;
            });
        });
    </script>

    @endpush

</x-ui-page-card>
</div>
