<div>

    <x-ui-card>
            <x-ui-list-table id="Table" title="Kontak List">
                <x-slot name="body">
                    @foreach ($kontak as $key => $kontak)
                        <tr wire:key="list{{ $key }}">
                            <x-ui-list-body>
                                {{-- <x-slot name="image">
                                        <img src="{{ $item['image_path'] ?? 'https://via.placeholder.com/300' }}" alt="Material Photo" style="width: 200px; height: 200px;">
                                    </x-slot> --}}
                                <x-slot name="rows">
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('Nama Kontak') }}"
                                            model="inputs.name_contact" type="text" :action="$actionValue"
                                            required="false" />
                                        <x-ui-text-field label="{{ $this->trans('Jabatan') }}" model="inputs.position"
                                            type="text" :action="$actionValue" required="false" />
                                        <x-ui-text-field label="{{ $this->trans('Tanggal Lahir') }}"
                                            model="inputs.date_of_birth" type="text" :action="$actionValue"
                                            required="false" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('Phone 1') }}" model="inputs.phone1"
                                            type="text" :action="$actionValue" required="false" />
                                        <x-ui-text-field label="{{ $this->trans('Phone 2') }}" model="inputs.phone2"
                                            type="text" :action="$actionValue" required="false" />
                                        <x-ui-text-field label="{{ $this->trans('Email') }}" model="inputs.email"
                                            type="text" :action="$actionValue" required="false" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('Alamat Kontak') }}"
                                            model="inputs.address_contact" type="text" :action="$actionValue"
                                            required="false" />
                                        <x-ui-text-field label="{{ $this->trans('Catatan Kontak') }}"
                                            model="inputs.note" type="text" :action="$actionValue" required="false" />
                                    </div>
                                </x-slot>
                                <x-slot name="button">
                                    <x-ui-link-text type="close" :clickEvent="'deleteItem(' . $key . ')'" class="btn btn-link"
                                        name="x" />
                                </x-slot>
                            </x-ui-list-body>
                        </tr>
                    @endforeach
                </x-slot>
                <x-slot name="footerButton">
                    <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg"
                        button-name="Add" />
                </x-slot>


            </x-ui-list-table>
    </x-ui-card>
    <x-ui-footer>
        <div>
            <x-ui-button clickEvent="Save" button-name="Save Contact" loading="true" :action="$actionValue"
                cssClass="btn-primary" iconPath="save.svg" />
        </div>
    </x-ui-footer>
</div>
