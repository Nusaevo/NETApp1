<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">

                    @include('layout.customs.buttons.create', [
                        'clickEvent' => route('ConfigConsts.Detail', ['action' => encryptWithSessionKey('Create')]),
                        'url' => 'ConfigConsts'
                    ])

                    <div class="table-responsive">
                        @livewire('settings.config-consts.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
