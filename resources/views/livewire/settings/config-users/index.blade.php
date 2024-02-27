<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">

                    @include('layout.customs.buttons.create', [
                        'clickEvent' => route('config_users.detail', ['action' => encryptWithSessionKey('Create')]),
                        'url' => 'config_users'
                    ])

                    <div class="table-responsive">
                        @livewire('settings.config-users.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
