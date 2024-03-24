<div id="page-card" class="container-xxl mb-5" >
    <div class="card shadow-sm">
        @if(!empty($title))
            <h3 class="p-5">{{ $title }}</h3>
        @endif
        <div class="card-body">
            @isset($status)
                @if (!empty($status))
                    <div class="d-flex justify-content-end">
                        <div>
                            <strong><h3>Status : {{ $status }}</h3></strong>
                        </div>
                    </div>
                @endif
            @endisset
            @isset($slot)
                {{ $slot }}
            @endisset
        </div>
    </div>
</div>
<div>
    @include('layout.customs.notification')
</div>
