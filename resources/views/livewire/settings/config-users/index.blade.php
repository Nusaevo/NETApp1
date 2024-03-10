<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">

                    @include('layout.customs.buttons.create', [
                        'clickEvent' => route('ConfigUsers.Detail', ['action' => encryptWithSessionKey('Create')]),
                        'url' => 'ConfigUsers'
                    ])

                    <div class="table-responsive">
                        @livewire('settings.config-users.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
