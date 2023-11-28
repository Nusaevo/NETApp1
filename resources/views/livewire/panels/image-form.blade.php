<div>
    <!-- Modal -->
    <div class="modal fade" id="attachmentModal" tabindex="-1" aria-labelledby="attachmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attachmentModalLabel">Upload Attachment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="attachmentFile" class="form-label">Choose File</label>
                        <input type="file" class="form-control" id="attachmentFile" name="attachmentFile" accept="image/*" required>
                    </div>

                    <!-- Image preview container -->
                    <div class="mb-3" id="imagePreviewContainer" style="display: none;">
                        <label>Image Preview:</label>
                        <img id="imagePreview" src="#" alt="Attachment Preview" style="max-width: 100%; height: auto;">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript to handle image preview -->
    <script>
        // Function to display image preview when a file is selected
        function previewImage() {
            var input = document.getElementById('attachmentFile');
            var imagePreview = document.getElementById('imagePreview');
            var imagePreviewContainer = document.getElementById('imagePreviewContainer');

            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                imagePreviewContainer.style.display = 'none';
            }
        }

        // Attach the previewImage function to the file input's change event
        document.getElementById('attachmentFile').addEventListener('change', previewImage);

    </script>

</div>

