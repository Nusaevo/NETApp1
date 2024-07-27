<div>
    <div id="page-card" class="container-xxl mb-5">

        <div class="card shadow-sm">
            @if(!empty($title))
            <h3 class="p-5">{{ $title }}</h3>
            @endif
            <div class="card-body">
                @isset($status)
                @if (!empty($status))
                <div class="d-flex justify-content-end">
                    <div>
                        <strong>
                            <h3>Status : {{ $status }}</h3>
                        </strong>
                    </div>
                </div>
                @endif
                @endisset
                @isset($slot)
                {{ $slot }}
                @endisset
            </div>
        </div>

        @isset($this->object->id)
        <div class="p-3">
            <p>Created At: {{ optional($this->object->created_at)->format('Y-m-d H:i:s') }}@if($this->object->created_at) by {{ $this->object->created_by ?? 'N/A' }}@endif</p>
            <p>Updated At: {{ optional($this->object->updated_at)->format('Y-m-d H:i:s') }}@if($this->object->updated_at) by {{ $this->object->updated_by ?? 'N/A' }}@endif</p>
        </div>
        @endisset

    </div>

</div>

