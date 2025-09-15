<div>
    @if (!$isComponent)
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
                @if (session('app_code') == 'TrdRetail1')
                    <button class="btn btn-primary"  wire:click="syncImages" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#syncModal">
                        <i class="bi bi-arrow-clockwise"></i> Sync Images
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm align-middle" role="status"
                                aria-hidden="true"></span>
                        </span>
                    </button>
                @endif
            </div>
        </div>
    @endif

    <div class="main-content gallery-main-content {{ $isComponent ? 'dialog-box-body' : '' }}">
        @if ($isComponent && !$loadImages)
            <!-- Bootstrap loading state -->
            <div class="d-flex justify-content-center align-items-center" style="min-height: 300px;">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h6 class="text-muted">Loading images...</h6>
                    <p class="text-muted small mb-0">Please wait</p>
                </div>
            </div>
        @else
            @forelse ($attachments as $key => $attachment)
                <div class="list-gallery-item gallery-list-item">
                    <div class="image-container gallery-image-container card">
                        <input type="checkbox" class="gallery-checkbox form-check-input" data-image-id="{{ $attachment->id }}"
                            data-image-url="{{ $attachment->getUrl() }}" onchange="toggleDeleteButton()">
                        <x-ui-image src="{{ $attachment->getUrl() }}" alt="Captured Image" width="300px" height="300px" />

                        <!-- Photo File Name Below Image -->
                        <div class="gallery-file-name">
                            <small class="fw-medium">{{ $attachment->name }}</small>
                        </div>

                        @if (!$isComponent)
                            @if (isset($permissions['delete']) && $permissions['delete'])
                                <button class="gallery-btn-delete-image btn btn-danger btn-sm" onclick="deleteImage('{{ $attachment->id }}')"
                                    data-image-id="{{ $attachment->id }}" title="Delete Image">
                                    <i class="bi bi-x"></i>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            @empty
                @if ($loadImages)
                    <div class="col-12">
                        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                            <div class="text-center">
                                <i class="bi bi-images text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-2">No images found</h5>
                                <p class="text-muted small mb-0">Your storage gallery is empty</p>
                            </div>
                        </div>
                    </div>
                @endif
            @endforelse
        @endif
    </div>

    @if (!$isComponent)
        <div class="pagination-container gallery-pagination-container">
            @include('components.ui-pagination', ['paginator' => $attachments])
        </div>
    @endif

    @if ($isComponent)
        <x-ui-footer>
            <x-ui-button jsClick="submitSelectedImages()" clickEvent="" button-name="Submit" loading="true"
                action="Edit" cssClass="btn-primary" iconPath="save.svg" />
        </x-ui-footer>
    @endif

    <script>
        // Simple lazy loading when dialog is opened
        document.addEventListener('DOMContentLoaded', function() {
            // Listen for Bootstrap modal events
            const storageModal = document.getElementById('storageDialogBox');
            if (storageModal) {
                storageModal.addEventListener('shown.bs.modal', function () {
                    if (@js($isComponent) && !@js($loadImages)) {
                        @this.loadImagesLazy();
                    }
                });

                storageModal.addEventListener('hidden.bs.modal', function () {
                    if (@js($isComponent)) {
                        @this.resetImagesLoad();
                    }
                });
            }

            // Listen for Livewire custom events (fallback)
            window.addEventListener('openStorageDialog', function() {
                if (@js($isComponent) && !@js($loadImages)) {
                    setTimeout(() => {
                        @this.loadImagesLazy();
                    }, 200);
                }
            });

            window.addEventListener('closeStorageDialog', function() {
                if (@js($isComponent)) {
                    @this.resetImagesLoad();
                }
            });
        });

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
    <div>

        <!-- Modal Dialog Box -->
        <x-ui-dialog-box id="syncModal" title="Image Sync Simulation">
            <x-slot name="body">
                <div id="sync-container">
                    <p id="sync-status-text" class="mt-2">{{ $status }}</p>
                    <!-- Spinner Loading, ditampilkan saat proses Livewire berlangsung -->
                    <div wire:loading>
                        <div id="sync-spinner" class="d-flex justify-content-center my-3">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <!-- Bagian ini muncul setelah proses selesai (wire:loading.remove) -->
                    <div wire:loading.remove>
                        <!-- Tampilkan progress bar jika diinginkan -->
                        <div class="progress mb-3">
                            <div id="sync-progress-bar" class="progress-bar" role="progressbar"
                                style="width: {{ $syncProgress }}%;" aria-valuenow="{{ $syncProgress }}"
                                aria-valuemin="0" aria-valuemax="100">
                                {{ $syncProgress }}%
                            </div>
                        </div>
                        <!-- Section untuk menampilkan gambar yang berhasil disinkronisasi -->
                        <div id="synced-images-section" style="display: block;">
                            <hr>
                            <h6>Synced Images:</h6>
                            <ul id="synced-images-list"
                                style="max-height: 200px; overflow-y: auto; list-style-type: none; padding: 0;">
                                @foreach ($syncedImages as $image)
                                    <li>Material Code: {{ $image['material_code'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <!-- Section untuk gambar yang gagal (jika ada) -->
                        @if (count($failedImages))
                            <div id="failed-images-section" style="display: block;">
                                <hr>
                                <h6>Failed Images:</h6>
                                <ul id="failed-images-list"
                                    style="max-height: 200px; overflow-y: auto; list-style-type: none; padding: 0; color: red;">
                                    @foreach ($failedImages as $failed)
                                        <li>File: {{ $failed['file_name'] }}, Error: {{ $failed['error'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </x-slot>
            <x-slot name="footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="sync-close-btn">
                    Close
                </button>
            </x-slot>
        </x-ui-dialog-box>
    </div>



</div>
