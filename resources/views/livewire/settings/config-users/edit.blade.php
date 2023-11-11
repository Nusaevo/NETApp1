<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div>
        <x-ui-button click-event="{{ route('config_users.index') }}" type="Back" button-name="Back"/>
    </div>
    <x-ui-page-card title="{{ $action }} User" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="general,credential"> </x-ui-tab-view>

        <form wire:submit.prevent="{{ $action }}" class="form w-100">
            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="User" :isOpen="true">
                        @if ($action == 'Create')
                            <x-ui-text-field label="Login ID" model="inputs.code" type="text" span="Full" :action="$action" required="true" placeHolder="Enter Login ID (e.g., johndoe123)" visible="true"/>
                        @else
                            <x-ui-text-field label="Login ID" model="inputs.code" type="text" span="Full" :action="$action" required="true" enabled="false" placeHolder="Enter Login ID" visible="true"/>
                        @endif

                        <x-ui-text-field label="Nama" model="inputs.name" type="text" span="Full" :action="$action" required="true" placeHolder="Enter Name (e.g., John Doe)" visible="true"/>
                        <div class="text-field-half">
                            <x-ui-text-field label="Email" model="inputs.email" type="email" span="Half" :action="$action" required="true" placeHolder="Enter Email (e.g., johndoe@example.com)" visible="true"/>
                            <x-ui-text-field label="Phone" model="inputs.phone" type="text" span="Half" :action="$action" placeHolder="Enter Phone (optional)" visible="true"/>
                        </div>

                        <x-ui-text-field label="Department" model="inputs.dept" type="text" span="Full" :action="$action" placeHolder="Enter Department (optional)" visible="true"/>
                        {{-- <x-ui-text-field-search label="Test" model="inputs.dept"
                        :options="$applications" span="Full" name="Application" :action="$action" placeHolder="Search application" /> --}}

                    </x-ui-expandable-card>
                </div>
                <div class="tab-pane fade" id="credential" role="tabpanel" aria-labelledby="credential-tab">
                    <x-ui-expandable-card id="UserPassword" title="User Credential" :isOpen="true">
                        @if ($action == 'Create')
                            <x-ui-text-field label="Password" model="inputs.newpassword" type="password" span="Full" :action="$action" placeHolder="Enter a secure password with at least 8 characters, including lowercase, uppercase, and numbers. (e.g., Password123)" required="true" visible="true"/>
                        @else
                            <x-ui-text-field label="New Password" model="inputs.newpassword" type="password" span="Full" :action="$action" placeHolder="Enter a secure password with at least 8 characters, including lowercase, uppercase, and numbers. (e.g., Password123)" visible="true"/>
                        @endif
                        <x-ui-text-field label="Confirm New Password" model="inputs.confirmnewpassword" type="password" span="Full" :action="$action" placeHolder="Enter same Password" visible="true"/>
                    </x-ui-expandable-card>
                </div>
            </x-ui-tab-view-content>
        </form>
        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
