<div id="page-card" class="container-xxl mb-5" >
    <div class="card shadow-sm">
         <h3 class="p-5">{{ $title }}</h3>
        <div class="card-body">
            @if (!empty($status))
                <div class="d-flex justify-content-end">
                    <div>
                        <strong><h3>Status : {{ $status ?? 'Active' }}</h3></strong>
                    </div>
                </div>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>
