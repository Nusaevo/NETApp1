<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">
                    <x-ui-button
                    visible="true"
                    enabled="true"
                    click-event="{{ route('customers.detail', ['action' => Crypt::encryptString('Create')])  }}"
                    cssClass="btn btn-success mb-5"
                    type="Route"
                    loading="true"
                    iconPath="images/create-icon.png"
                    button-name="Create" />

                    <div class="table-responsive">
                        @livewire('masters.customers.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('layout.customs.modal', ['modal_listener' => 'disableData'])
</div>
