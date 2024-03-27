<div>
    <div>
        <x-ui-button click-event="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card title="{{ $actionValue }} User" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>


            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-card>
                        <x-ui-text-field label="Login ID" model="inputs.code" type="code" span="Full" :action="$actionValue" required="true" enabled="true" placeHolder="Enter Login ID" visible="true" />
                        <x-ui-text-field label="Nama" model="inputs.name" type="text" span="Full" :action="$actionValue" required="true" placeHolder="Enter Name (e.g., John Doe)" visible="true" />

                        <x-ui-text-field label="Email" model="inputs.email" type="email" span="Half" :action="$actionValue" required="true" placeHolder="Enter Email (e.g., johndoe@example.com)" visible="true" />
                        <x-ui-text-field label="Phone" model="inputs.phone" type="text" span="Half" :action="$actionValue" placeHolder="Enter Phone (optional)" visible="true" />
                        <x-ui-text-field label="Department" model="inputs.dept" type="text" span="Full" :action="$actionValue" placeHolder="Enter Department (optional)" visible="true" />
                        {{-- <x-ui-text-field-search label="Test" model="inputs.dept"
                            :options="$applications" span="Full" name="Application" :action="$actionValue" placeHolder="Search application" /> --}}
                        @if ($actionValue == 'Create')
                        <x-ui-text-field label="Password" model="inputs.newpassword" type="password" span="Full" :action="$actionValue" placeHolder="Enter a secure password with at least 8 characters, including lowercase, uppercase, and numbers. (e.g., Password123)" required="true" visible="true" />
                        @else
                        <x-ui-text-field label="New Password" model="inputs.newpassword" type="password" span="Full" :action="$actionValue" placeHolder="Enter a secure password with at least 8 characters, including lowercase, uppercase, and numbers. (e.g., Password123)" visible="true" />
                        @endif
                        <x-ui-text-field label="Confirm New Password" model="inputs.confirmnewpassword" type="password" span="Full" :action="$actionValue" placeHolder="Enter same Password" visible="true" />
                    </x-ui-card>
                </div>
                {{-- <div class="tab-pane fade" id="credential" role="tabpanel" aria-labelledby="credential-tab">
                       <x-ui-card>
                            @if ($actionValue == 'Create')
                                <x-ui-text-field label="Password" model="inputs.newpassword" type="password" span="Full" :action="$actionValue" placeHolder="Enter a secure password with at least 8 characters, including lowercase, uppercase, and numbers. (e.g., Password123)" required="true" visible="true"/>
                            @else
                                <x-ui-text-field label="New Password" model="inputs.newpassword" type="password" span="Full" :action="$actionValue" placeHolder="Enter a secure password with at least 8 characters, including lowercase, uppercase, and numbers. (e.g., Password123)" visible="true"/>
                            @endif
                            <x-ui-text-field label="Confirm New Password" model="inputs.confirmnewpassword" type="password" span="Full" :action="$actionValue" placeHolder="Enter same Password" visible="true"/>
                        </x-ui-card>
                    </div> --}}
            </x-ui-tab-view-content>

        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
