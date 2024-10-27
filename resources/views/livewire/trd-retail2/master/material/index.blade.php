
<div>
<x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">

    <div class="table-container">
        @livewire($baseRenderRoute.'.index-data-table')
    </div>
</x-ui-page-card>
</div>
