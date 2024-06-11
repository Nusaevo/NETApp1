<div class="image-button-container">
    <button id="btnAdd" class="btn btn-secondary" onclick="toggleAddPopup()">
        <i class="bi bi-plus-circle"></i>
         Add
    </button>

    <!-- Popup for Add Options -->
    <div id="addPopup" class="popup shadow-lg p-3 mb-5 bg-white rounded" style="display: none;">
        <button class="btn btn-secondary w-100 text-left mb-2" onclick="triggerFileInput('camera')">
            <i class="bi bi-camera-fill"></i>
            Take Photo
        </button>
        <button class="btn btn-secondary w-100 text-left mb-2" onclick="triggerFileInput('gallery')">
            <i class="bi bi-image-fill"></i>
            From Gallery
        </button>
        @if (isset($hideStorageButton) && $hideStorageButton == 'false')
        <button class="btn btn-secondary w-100 text-left" data-bs-target="#storageDialogBox" data-bs-toggle="modal">
            <i class="bi bi-box-arrow-in-right"></i>
            Net Storage
        </button>
        @endif
    </div>
    <input type="file" id="imageInput" accept="image/*" style="display: none;" multiple onchange="handleFileUpload(event)">

    <!-- Progress Modal -->
    <div id="progressModal" class="modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Uploading Images</h5>
                </div>
                <div class="modal-body">
                    <progress id="uploadProgress" value="0" max="100" style="width: 100%;"></progress>
                    <div id="progressText" style="text-align: center; margin-top: 10px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function toggleAddPopup() {
        const popup = document.getElementById('addPopup');
        popup.style.display = popup.style.display === 'none' ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(event) {
            const isClickInside = document.getElementById('btnAdd').contains(event.target) || document.getElementById('addPopup').contains(event.target);
            if (!isClickInside) {
                document.getElementById('addPopup').style.display = 'none';
            }
        });
    });

    function triggerFileInput(type) {
        const input = document.getElementById('imageInput');
        input.removeAttribute('capture');
        if (type === 'camera') {
            input.setAttribute('capture', 'environment');
        }
        input.click();
    }

    function handleFileUpload(event) {
        const files = event.target.files;
        const totalFiles = files.length;
        let uploadedFiles = 0;

        const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
        progressModal.show();

        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                Livewire.emit('captureImages', e.target.result);

                uploadedFiles++;
                const progress = (uploadedFiles / totalFiles) * 100;
                document.getElementById('uploadProgress').value = progress;
                document.getElementById('progressText').textContent = `Uploading ${uploadedFiles} of ${totalFiles} images`;

                if (uploadedFiles === totalFiles) {
                    setTimeout(() => {
                        progressModal.hide();
                    }, 2000);
                }
            };
            reader.readAsDataURL(file);
        });
    }
</script>
