
<div>
<x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">

    <div class="table-container">
        @livewire($currentRoute.'.index-data-table')
    </div>
</x-ui-page-card>
</div>
