<div>
    @if (isset($action) && $action !== 'View')
    <div class="image-button-container">
        <button id="btnAdd" class="btn btn-secondary" onclick="toggleAddPopup()">
            <span wire:loading.remove>
                <i class="bi bi-plus-circle"></i>
                Add
            </span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
            </span>
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
    </div>

    <script>
        function toggleAddPopup() {
            const popup = document.getElementById('addPopup');
            popup.style.display = popup.style.display === 'none' ? 'block' : 'none';
        }

        document.addEventListener('livewire:init', () => {
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
            Array.from(files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    Livewire.dispatch('captureImages', { imageData: e.target.result });
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
    @else
    <div class="image-button-container" style="visibility: hidden">
        <button id="btnAdd" class="btn btn-secondary" onclick="toggleAddPopup()">
            <span wire:loading.remove>
                <i class="bi bi-plus-circle"></i>
                Add
            </span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
            </span>
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
    </div>

    <script>
        function toggleAddPopup() {
            const popup = document.getElementById('addPopup');
            popup.style.display = popup.style.display === 'none' ? 'block' : 'none';
        }

        document.addEventListener('livewire:init', () => {
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
            Array.from(files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    Livewire.dispatch('captureImages', { imageData: e.target.result });
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
    @endif
</div>
