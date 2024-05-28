<td>
    <div class="list-group-item">
        <div class="d-flex justify-content-between align-items-start">
            @isset($image)
            <div class="col-md-2">
                <div style="margin-top: 20px; padding-left: 20px; margin-bottom: auto; ">
                    {{ $image }}
                </div>
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
        @isset($button)
            <div class="close-button">
                {{ $button }}
            </div>
        @endisset
    </div>
</td>

