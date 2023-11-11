<div>
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('sales_order.create') }}" class="btn btn-success mb-5"><i class="bi bi-file-earmark-plus-fill fs-2 me-2"></i> Tambah</a>
                    @include('layout.customs.notification')
                    <div class="table-responsive">
                        @livewire('transactions.sales.orders.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
