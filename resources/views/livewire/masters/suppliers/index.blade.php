<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">
                    @include('layout.customs.buttons.create', [
                        'clickEvent' => route('Suppliers.Detail', ['action' => encryptWithSessionKey('Create')]),
                        'url' => 'Suppliers'
                    ])

                    <div class="table-responsive">
                        @livewire('masters.suppliers.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
