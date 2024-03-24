<td>
    <div class="list-group-item">
        <div class="d-flex justify-content-between align-items-start">
            @isset($image)
                <div class="col-md-2">
                    {{ $image }}
                </div>
                <div class="col-md-9">
                    {{ $rows }}
                </div>
            @else
                <div class="col-md-12">
                    {{ $rows }}
                </div>
            @endisset
        </div>
        <div class="close-button">
            {{ $button }}
        </div>
    </div>
</td>
