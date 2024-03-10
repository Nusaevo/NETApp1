<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">
                    @include('layout.customs.buttons.create', [
                        'clickEvent' => route('Materials.Detail', ['action' => encryptWithSessionKey('Create')])
                    ])


                    <div class="table-responsive">
                        @livewire('masters.materials.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
