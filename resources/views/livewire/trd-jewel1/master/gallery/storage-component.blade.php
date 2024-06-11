<x-ui-page-card title="Gallery">
    @if (!$isDialogBoxComponent)
    <div class="gallery-header">
        <div class="button-group">
            <x-image-button hideStorageButton="true" class="btn btn-secondary"></x-image-button>

            <button id="btnDeleteSelected" class="btn btn-danger gallery-btn-delete-selected" style="display: none;" onclick="deleteSelectedImages()">
                <i class="bi bi-trash"></i> Delete Selected
            </button>
        </div>
    </div>
    @endif

    <div class="main-content gallery-main-content {{ $isDialogBoxComponent ? 'dialog-box-body' : '' }}">
        @foreach($attachments as $key => $attachment)
            <div class="list-gallery-item gallery-list-item">
                <div class="image-container gallery-image-container">
                    <input type="checkbox" class="gallery-checkbox" data-image-id="{{ $attachment->id }}" data-image-url="{{ $attachment->getUrl() }}" onchange="toggleDeleteButton()">
                    <img src="{{ $attachment->getUrl() }}" alt="Gallery Image" class="photo-box-image gallery-photo-box-image">
                    @if (!$isDialogBoxComponent)
                    <button class="gallery-btn-delete-image" onclick="deleteImage('{{ $attachment->id }}')" data-image-id="{{ $attachment->id }}">X</button>
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
        <x-ui-button jsClick="submitSelectedImages()" clickEvent="" button-name="Submit" loading="true" action="Edit" cssClass="btn-primary" iconPath="save.svg" />
    </x-ui-footer>
    @endif

    <!-- Submit Images Progress Modal -->
    <div id="submitProgressModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submitting Selected Images</h5>
                </div>
                <div class="modal-body">
                    <progress id="submitUploadProgress" value="0" max="100" style="width: 100%;"></progress>
                    <div id="submitProgressText" style="text-align: center; margin-top: 10px;"></div>
                </div>
            </div>
        </div>
    </div>
</x-ui-page-card>

<script>
    async function readImageAsByteArray(url) {
        const response = await fetch(url);
        const blob = await response.blob();
        const arrayBuffer = await new Response(blob).arrayBuffer();
        const byteArray = new Uint8Array(arrayBuffer);
        return Array.from(byteArray);
    }

    function selectImage(imageId) {
        Livewire.emit('selectImage', imageId);
    }

    function deleteImage(imageId) {
        if (confirm('Are you sure you want to delete this image?')) {
            Livewire.emit('deleteImage', imageId);
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

    async function submitSelectedImages() {
        const checkboxes = document.querySelectorAll('.gallery-checkbox:checked');
        const totalFiles = checkboxes.length;
        let uploadedFiles = 0;

        const progressModal = new bootstrap.Modal(document.getElementById('submitProgressModal'));
        progressModal.show();

        const imageByteArrays = await Promise.all(
            Array.from(checkboxes).map(async checkbox => {
                const imageUrl = checkbox.dataset.imageUrl;
                const byteArray = await readImageAsByteArray(imageUrl);
                uploadedFiles++;
                const progress = (uploadedFiles / totalFiles) * 100;
                document.getElementById('submitUploadProgress').value = progress;
                document.getElementById('submitProgressText').textContent = `Submitting ${uploadedFiles} of ${totalFiles} images`;
                return byteArray;
            })
        );

        console.log('Selected Image Byte Arrays:', imageByteArrays);

        if (imageByteArrays.length > 0) {
            Livewire.emit('submitImages', imageByteArrays);
            resetSelectedImages();
            setTimeout(() => {
                progressModal.hide();
            }, 2000);
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
