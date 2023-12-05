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
        <thead>
            <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                {{ $headers }}
            </tr>
        </thead>
        <tbody>
            {{ $rows }}
        </tbody>
    </table>
</div>
{{-- <script>
    document.addEventListener('livewire:load', function () {
        var checkAndInitializeDataTable = function () {
            var tableId = '{{ isset($id) ? $id : 'defaultTable' }}';
            var tableElement = document.getElementById(tableId);

            myJQuery(tableElement).DataTable();
            // Check if the table exists in the DOM
            if (tableElement) {
                // If DataTable is already initialized, destroy it
                if (myJQuery.fn.DataTable.isDataTable('#' + tableId)) {
                    myJQuery(tableElement).DataTable().destroy().clear();
                }
                // Reinitialize the DataTable
                myJQuery(tableElement).DataTable();
            }
        };

        // Initial DataTable initialization
        checkAndInitializeDataTable();

        // Reinitialize DataTable when Livewire finishes updating the DOM
        Livewire.hook('message.processed', (message, component) => {
            checkAndInitializeDataTable();
        });
    });
</script> --}}
