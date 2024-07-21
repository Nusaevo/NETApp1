@once
<script>
    window.addEventListener('notify-swal', function(e) {
        let dataArray = e.detail;
        console.log('Event Data:', dataArray); // Debugging

        // Access the first element of the array
        let data = dataArray[0];

        let title = data.title || 'No title';
        let message = data.message || 'No message';
        let type = data.type || 'info';

        // Map the type to "Failed" for error and "Success" for success
        if (type === 'error') {
            title = 'Failed';
        } else if (type === 'success') {
            title = 'Success';
        }

        Swal.fire(title, message, type);
    });

    window.addEventListener('refresh', function(e) {
        setTimeout(function() {
            window.location.reload();
        }, 1000);
    });
</script>
@endonce



@if (session()->has('success'))
<div class="rounded p-10 pb-0 d-flex flex-column">
    <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row p-5 mb-10">
        <i class="bi bi-check-circle-fill fs-3tx text-light me-4 mb-5 mb-sm-0"></i>
        <div class="d-flex flex-column text-light pe-0 pe-sm-10">
            <h4 class="mb-2 text-light">Berhasil</h4>
            <span>{!! session('success') !!}</span>
        </div>
        <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
            <i class="bi bi-x-circle text-danger"></i>
        </button>
    </div>
</div>
@endif
@if (session()->has('warning'))
<div class="rounded p-10 pb-0 d-flex flex-column">
    <div class="alert alert-dismissible bg-warning d-flex flex-column flex-sm-row p-5 mb-10">
        <i class="bi bi-exclamation-circle-fill fs-3tx text-light me-4 mb-5 mb-sm-0"></i>
        <div class="d-flex flex-column pe-0 pe-sm-10">
            <h4 class="mb-2">Perhatian</h4>
            <span>{!! session('warning') !!}</span>
        </div>
        <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
            <i class="bi bi-x-circle text-danger"></i>
        </button>
    </div>
</div>
@endif
@if (session()->has('error'))
<div class="rounded p-10 pb-0 d-flex flex-column">
    <div class="alert alert-dismissible bg-danger d-flex flex-column flex-sm-row p-5 mb-10">
        <i class="bi bi-x-circle-fill fs-3tx text-light me-4 mb-5 mb-sm-0"></i>
        <div class="d-flex flex-column text-light pe-0 pe-sm-10">
            <h4 class="mb-2 text-light">Kesalahan</h4>
            <span>{!! session('error') !!}</span>
        </div>
        <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
            <i class="bi bi-x-circle text-light"></i>
        </button>
    </div>
</div>
@endif
@if (session()->has('info'))
<div class="rounded p-10 pb-0 d-flex flex-column">
    <div class="alert alert-dismissible bg-primary d-flex flex-column flex-sm-row p-5 mb-10">
        <i class="bi bi-info-circle-fill fs-3tx text-light me-4 mb-5 mb-sm-0"></i>
        <div class="d-flex flex-column text-light pe-0 pe-sm-10">
            <h4 class="mb-2 text-light">Info</h4>
            <span>{!! session('info') !!}</span>
        </div>
        <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
            <i class="bi bi-x-circle text-danger"></i>
        </button>
    </div>
</div>
@endif

