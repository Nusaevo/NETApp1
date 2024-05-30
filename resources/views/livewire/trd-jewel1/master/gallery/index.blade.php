<x-ui-page-card title="Gallery">
    <div>
        @include('layout.customs.notification')
    </div>

    <div class="gallery-header">
        <button id="btnCamera" class="btn btn-secondary gallery-btn-upload">Upload Photo</button>
        <button id="btnDeleteSelected" class="btn btn-danger gallery-btn-delete-selected" style="display: none;" onclick="deleteSelectedImages()">Delete Selected</button>
        <input type="file" id="imageInput" accept="image/*" style="display: none;" multiple onchange="handleFileUpload(event)">
    </div>

    <div class="main-content gallery-main-content">
        @foreach($attachments as $key => $attachment)
            <div class="list-gallery-item gallery-list-item">
                <div class="image-container gallery-image-container">
                    <input type="checkbox" class="gallery-checkbox" onchange="toggleDeleteButton()">
                    <img src="{{ $attachment->getUrl() }}" alt="Gallery Image" class="photo-box-image gallery-photo-box-image">
                    <button class="gallery-btn-delete-image" onclick="deleteImage('{{ $attachment->id }}')" data-image-id="{{ $attachment->id }}">X</button>
                </div>
            </div>
        @endforeach
    </div>
    <div class="pagination-container gallery-pagination-container">
        @include('components.ui-pagination', ['paginator' => $attachments])
    </div>
</x-ui-page-card>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('btnCamera').addEventListener('click', triggerFileInput);
    });

    function triggerFileInput() {
        document.getElementById('imageInput').click();
    }

    function handleFileUpload(event) {
        const files = event.target.files;
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                Livewire.emit('imagesCaptured', e.target.result);
            };
            reader.readAsDataURL(file);
        });
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
        if (checkboxes.length > 0) {
            deleteButton.style.display = 'inline-block';
        } else {
            deleteButton.style.display = 'none';
        }
    }

    function deleteSelectedImages() {
        const checkboxes = document.querySelectorAll('.gallery-checkbox:checked');
        checkboxes.forEach(checkbox => {
            const imageId = checkbox.closest('.gallery-list-item').querySelector('.gallery-btn-delete-image').dataset.imageId;
            Livewire.emit('deleteImage', imageId);
        });
    }
</script>
