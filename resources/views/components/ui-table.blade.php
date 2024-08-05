<div class="card-body p-2 mt-2">
    @if(isset($title))
        <h2 class="mb-2 text-center">{{ $title }}</h2>
    @endif

    @if(isset($button))
        <div class="mb-3">
            {{ $button }}
        </div>
    @endif
</div>

<div wire:ignore x-data="{
        initDataTable() {
            let tableId = '{{ isset($id) ? $id : 'defaultTable' }}';
            let tableElement = this.$refs.table;

            if (tableElement && myJQuery.fn.DataTable) {
                if (myJQuery.fn.DataTable.isDataTable('#' + tableId)) {
                    myJQuery('#' + tableId).DataTable().destroy();
                }

                myJQuery('#' + tableId).DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        {
                            extend: 'copy',
                            title: tableId
                        },
                        {
                            extend: 'csv',
                            title: tableId
                        },
                        {
                            extend: 'excel',
                            title: tableId
                        },
                        {
                            extend: 'pdf',
                            title: tableId
                        },
                    ],
                    pagingType: 'full_numbers',
                    stateSave: true,
                });
            }
        }
    }"
    x-init="initDataTable()"
    class="table-container">
    <table {{ isset($id) ? 'id='.$id : '' }} class="table table-striped table-hover" x-ref="table">
        <thead>
            <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                {{ $headers }}
            </tr>
        </thead>
        <tbody wire:ignore.self>
            {{ $rows }}
        </tbody>
    </table>
    @isset($footer)
    <div class="d-flex justify-content-end mt-4">
        {{ $footer }}
    </div>
    @endisset
</div>

{{-- @if(isset($enableDataTable) && strcmp($enableDataTable, 'true') === 0)
@push('scripts')
<script>
    document.addEventListener('livewire:load', function() {
        Livewire.hook('element.init', (el, component) => {
            if (el.id === '{{ isset($id) ? $id : 'defaultTable' }}') {
                console.log('Livewire element.init hook for:', el.id);
                Alpine.store('initDataTable');
            }
        });

        Livewire.hook('morph.updated', (el, component) => {
            if (el.id === '{{ isset($id) ? $id : 'defaultTable' }}') {
                console.log('Livewire morph.updated hook for:', el.id);
                Alpine.store('initDataTable');
            }
        });

        Livewire.on('refreshDataTable', function() {
            console.log('Livewire refreshDataTable event');
            Alpine.store('initDataTable');
        });
    });
</script>
@endpush
@endif --}}
