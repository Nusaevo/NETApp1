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
@if(isset($enableDataTable) && strcmp($enableDataTable, 'true') === 0)
    <div x-data="{
            initDataTable() {
                let tableId = '{{ isset($id) ? $id : 'defaultTable' }}';
                let tableElement = this.$refs.table;

                console.log('Initializing DataTable for:', tableId); // Added console log

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

                    console.log('DataTable initialized:', myJQuery('#' + tableId).DataTable().data().toArray()); // Added console log to display table data
                } else {
                    console.log('DataTable initialization failed. myJQuery.fn.DataTable is not defined.');
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
            <tbody>
                {{ $rows }}
            </tbody>
        </table>
        @isset($footer)
        <div class="d-flex justify-content-end mt-4">
            {{ $footer }}
        </div>
        @endisset
    </div>
@else
    <div class="table-container">
        <table {{ isset($id) ? 'id='.$id : '' }} class="table table-striped table-hover">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                    {{ $headers }}
                </tr>
            </thead>
            <tbody>
                {{ $rows }}
            </tbody>
        </table>
        @isset($footer)
        <div class="d-flex justify-content-end mt-4">
            {{ $footer }}
        </div>
        @endisset
    </div>
@endif
