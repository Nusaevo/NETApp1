<div>
    @if (!$isDialogBoxComponent)
        <div class="gallery-header">
            <div class="button-group">
                @if (isset($permissions['create']) && $permissions['create'])
                    <x-ui-image-button hideStorageButton="true" action="Edit"
                        class="btn btn-secondary"></x-ui-image-button>
                @endif

                <button id="btnDeleteSelected" class="btn btn-danger gallery-btn-delete-selected" style="display: none;"
                    onclick="deleteSelectedImages()">
                    <i class="bi bi-trash"></i> Delete Selected
                    <span wire:loading>
                        <span class="spinner-border spinner-border-sm align-middle" role="status"
                            aria-hidden="true"></span>
                    </span>
                </button>
            </div>
        </div>
    @endif

    <div class="main-content gallery-main-content {{ $isDialogBoxComponent ? 'dialog-box-body' : '' }}">
        @foreach ($attachments as $key => $attachment)
            <div class="list-gallery-item gallery-list-item">
                <div class="image-container gallery-image-container">
                    <input type="checkbox" class="gallery-checkbox" data-image-id="{{ $attachment->id }}"
                        data-image-url="{{ $attachment->getUrl() }}" onchange="toggleDeleteButton()">
                    <img src="{{ $attachment->getUrl() }}" alt="Gallery Image"
                        class="photo-box-image gallery-photo-box-image">
                    <!-- Photo File Name Below Image -->
                    <div class="gallery-file-name">
                        <small>{{ $attachment->name }}</small>
                    </div>

                    @if (!$isDialogBoxComponent)
                        @if (isset($permissions['delete']) && $permissions['delete'])
                            <button class="gallery-btn-delete-image" onclick="deleteImage('{{ $attachment->id }}')"
                                data-image-id="{{ $attachment->id }}">X</button>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if (!$isDialogBoxComponent)
        <div class="pagination-container gallery-pagination-container">
            @include('components.ui-pagination', ['paginator' => $attachments])
        </div>
    @endif

    @if ($isDialogBoxComponent)
        <x-ui-footer>
            <x-ui-button jsClick="submitSelectedImages()" clickEvent="" button-name="Submit" loading="true"
                action="Edit" cssClass="btn-primary" iconPath="save.svg" />
        </x-ui-footer>
    @endif

    <script>
        async function readImageAsByteArray(url) {
            const response = await fetch(url);
            const blob = await response.blob();
            const arrayBuffer = await new Response(blob).arrayBuffer();
            const byteArray = new Uint8Array(arrayBuffer);
            return Array.from(byteArray);
        }

        function selectImage(imageId) {
            Livewire.dispatch('selectImage', imageId);
        }

        function deleteImage(imageId) {
            if (confirm('Are you sure you want to delete this image?')) {
                Livewire.dispatch('deleteImage', {
                    imageId: imageId
                });
            }
        }


        function deleteSelectedImages() {
            const checkboxes = document.querySelectorAll('.gallery-checkbox:checked');
            const imageIds = Array.from(checkboxes).map(checkbox => checkbox.dataset.imageId);

            if (imageIds.length > 0) {
                Livewire.dispatch('deleteSelectedImages', {
                    imageIds: imageIds
                });
            } else {
                alert('No images selected for deletion.');
            }
        }

        function toggleDeleteButton() {
            const checkboxes = document.querySelectorAll('.gallery-checkbox:checked');
            const deleteButton = document.getElementById('btnDeleteSelected');
            if (deleteButton) {
                if (checkboxes.length > 0) {
                    deleteButton.style.display = 'inline-block';
                } else {
                    deleteButton.style.display = 'none';
                }
            }
        }

        function submitSelectedImages() {
            const checkboxes = document.querySelectorAll('.gallery-checkbox:checked');
            const attachmentIds = Array.from(checkboxes).map(checkbox => checkbox.dataset.imageId);

            if (attachmentIds.length > 0) {
                Livewire.dispatch('submitAttachmentsFromStorage', {
                    attachmentIds: attachmentIds
                });
                resetSelectedImages();
            } else {
                alert('No images selected');
            }
        }


        function resetSelectedImages() {
            const checkboxes = document.querySelectorAll('.gallery-checkbox:checked');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            toggleDeleteButton();
        }
    </script>
</div>
