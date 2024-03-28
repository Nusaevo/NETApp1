<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">

                    <div class="table-responsive">
                        @livewire('transactions.purchases-deliveries.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('layout.customs.modal', ['modal_listener' => 'disableData'])
</div>
