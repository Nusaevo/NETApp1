<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('config_menus.detail', ['action' => 'Create']) }}" class="btn btn-success mb-5">
                        <i class="bi bi-file-earmark-plus-fill fs-2 me-2"></i> Tambah
                    </a>
                    <div class="table-responsive">
                        @livewire('settings.config-menus.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layout.customs.modal-delete', ['destroy_listener' => 'master_customer_destroy'])
</div>
