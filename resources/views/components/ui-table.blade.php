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

<div class="table-responsive mt-5" >
    <table {{ isset($id) ? 'id='.$id : '' }} class="table table-striped table-hover" >
        <thead>
            <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                {{ $headers }}
            </tr>
        </thead>
        <tbody wire:ignore.self >
            {{ $rows }}
        </tbody>
    </table>
</div>
{{--
<script>
    myJQuery(document).ready(function () {
        var checkAndInitializeDataTable = function () {
            var tableId = '{{ isset($id) ? $id : 'defaultTable' }}';
            var tableElement = document.getElementById(tableId);

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
                    pagingType: "full_numbers",
                    stateSave: true,
                });
            }
        };

        checkAndInitializeDataTable();

        Livewire.on('refreshDataTable', function () {
            checkAndInitializeDataTable();
        });
    });
</script> --}}
