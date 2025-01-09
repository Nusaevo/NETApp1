<div class="card-body p-2 mt-10">
    @if(isset($title))
        <h2 class="mb-2 text-center">{{ $title }}</h2>
    @endif

    @if(isset($button))
        <div class="mb-3">
            {{ $button }}
        </div>
    @endif
</div>

<div class="table-responsive mt-5">
    <table {{ isset($id) ? 'id='.$id : '' }} class="table table-striped table-hover">
        <tbody>
            {{ $body }}
        </tbody>
    </table>
    @isset($footerButton)
        <div class="d-flex justify-content-center mt-4">
            {{ $footerButton }}
        </div>
    @endisset
</div>
