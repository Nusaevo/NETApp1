<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="{{ route('materials.index') }}" type="Back" button-name="Back" />
        </div>
    </div>
        @livewire('panels.material-form', ['materialActionValue' => $actionValue, 'materialIDValue' => $objectIdValue])
</div>

<script>
    function previewImage(event, previewId) {
        var reader = new FileReader();
        reader.onload = function() {
            var output = document.getElementById(previewId);
            output.style.backgroundImage = 'url(' + reader.result + ')';
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    function deleteImage(previewId, inputId) {
        var preview = document.getElementById(previewId);
        var input = document.getElementById(inputId);
        preview.style.backgroundImage = 'none';
        input.value = '';
    }

    function viewImage(previewId) {
        var preview = document.getElementById(previewId);
        var imageUrl = preview.style.backgroundImage.slice(5, -2); // Extract the URL

        // Create the modal container
        var modal = document.createElement('div');
        modal.style.position = 'fixed';
        modal.style.top = 0;
        modal.style.left = 0;
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
        modal.style.display = 'flex';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
        modal.style.zIndex = '1000';

        // Create the image element
        var img = new Image();
        img.src = imageUrl;
        img.style.maxWidth = '80%';
        img.style.maxHeight = '80%';
        img.style.margin = 'auto';

        // Close the modal on click
        modal.addEventListener('click', function() {
            document.body.removeChild(modal);
        });

        // Append the image to the modal container
        modal.appendChild(img);

        // Append the modal to the body
        document.body.appendChild(modal);
    }

</script>

