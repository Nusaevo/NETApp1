<div>
    <div id="page-card" class="container-xxl mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            {{-- Title --}}
            @if (!empty($title))
                <h3 class="p-3 m-0">{{ $title }}</h3>
            @endif

            {{-- Status --}}
            @isset($status)
                @if (!empty($status))
                    <div class="p-3">
                        <strong>Status Data: {{ $status }}</strong>
                    </div>
                @endif
            @endisset

        </div>

        {{-- Slot --}}
        <div>
            @isset($slot)
                {{ $slot }}
            @endisset
        </div>

        {{-- Metadata --}}
        @isset($this->object->id)
            <div class="p-3 mt-3">
                <p>
                    Created At: {{ optional($this->object->created_at)->format('Y-m-d H:i:s') }}
                    @if ($this->object->created_at)
                        by {{ $this->object->created_by ?? 'N/A' }}
                    @endif
                </p>
                <p>
                    Updated At: {{ optional($this->object->updated_at)->format('Y-m-d H:i:s') }}
                    @if ($this->object->updated_at)
                        by {{ $this->object->updated_by ?? 'N/A' }}
                    @endif
                </p>
            </div>
        @endisset
    </div>
</div>
