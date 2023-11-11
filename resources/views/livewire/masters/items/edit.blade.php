<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="{{ route('items.index') }}" type="Back" button-name="Back"/>
        </div>
    </div>

    <x-ui-page-card title="{{ $action }} Item" status="{{ $status }}">

        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

        <form wire:submit.prevent="{{ $action }}" class="form w-100">
            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="Item Info" :isOpen="true">
                        <x-ui-text-field label="Nama Item" model="inputs.name" type="text" :action="$action" required="true" placeHolder="Enter Name" />
                        <x-ui-dropdown-select label="Item Category"
                        click-event="refreshItemCategory"
                        model="inputs.category_item_id"
                        :options="$item_categories"
                        :selectedValue="$inputs['category_item_id']"
                        loading="true"
                        required="true"
                        :action="$action"/>


                        <div class="card-body p-2 mt-10">
                            <h2 class="mb-2 text-center">Atur Satuan Barang</h2>

                            <x-ui-button
                                click-event="addDetails"
                                cssClass="btn btn-success"
                                iconPath="images/create-icon.png"
                                button-name="Tambah" />

                            <div class="table-responsive mt-5">
                                <table id="tbl" class="table table-striped table-hover gy-7 gs-7">
                                    <thead>
                                        <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                            <th class="min-w-100px">No</th>
                                            <th class="min-w-200px">From</th>
                                            <th class="min-w-100px">Multiplier</th>
                                            <th class="min-w-100px">To</th>
                                            <th class="min-w-15px">Kode Barcode</th>
                                            <th class="min-w-15px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($input_details as $key => $detail)
                                        <tr>
                                            <td class="border">
                                                {{$key+1}}
                                            </td>
                                            <td class="border">
                                                <x-ui-dropdown-select
                                                click-event=""
                                                label=""
                                                :model="'input_details.' . $key . '.unit_id'"
                                                :options="$units"
                                                :selectedValue="$detail['unit_id']"
                                                required="true"
                                                :action="$action"/>
                                            </td>
                                            <td class="border">
                                                <x-ui-text-field model="input_details.{{ $key }}.multiplier" label='' type="text" :action="$action" required="true" placeHolder="" />
                                            </td>
                                            <td class="border">
                                                <x-ui-dropdown-select
                                                click-event=""
                                                label=""
                                                :model="'input_details.' . $key . '.to_unit_id'"
                                                :options="$units"
                                                :selectedValue="$detail['to_unit_id']"
                                                required="true"
                                                :action="$action"/>
                                            </td>
                                            <td class="border">
                                                <x-ui-text-field model="input_details.{{ $key }}.barcode" label=''  type="text" :action="$action" required="true" placeHolder="" />
                                            </td>
                                            <td>
                                                <x-ui-button button-name="Delete" click-event="deleteDetails({{ $key }})" :action="$action" cssClass="btn-danger" />
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </x-ui-expandable-card>
                </div>
            </x-ui-tab-view-content>
        </form>
        <div class="card-footer d-flex justify-content-end">
            <div>
                <x-ui-button click-event="{{ $action }}" button-name="Save" loading="true" :action="$action" cssClass="btn-primary" iconPath="images/save-icon.png" />
            </div>
        </div>

    </x-ui-page-card>
</div>
