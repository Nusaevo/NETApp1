
<div>
<x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">

    <div class="table-container">
        @livewire($currentRoute.'.index-data-table')
    </div>

    {{-- Buying Price Update Dialog --}}
    <x-ui-dialog-box id="buyingPriceDialog" title="Mass Update Buying Price" width="400px" height="300px"
        onOpened="openBuyingPriceDialog" onClosed="closeBuyingPriceDialog">
        <x-slot name="body">
            <div class="row mb-3">
                <div class="col-12">
                    <p>Masukkan harga modal untuk {{ count($selectedIds ?? []) }} barang:</p>
                </div>
            </div>
            <div class="row">
                <x-ui-text-field
                    label="Modal"
                    model="newBuyingPrice"
                    type="number"
                    required="true"
                    enabled="true"
                    actionValue="Edit"
                    Currency="IDR"
                />
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-ui-button clickEvent="closeBuyingPriceDialog" button-name="Cancel"
                cssClass="btn btn-secondary" />
            <x-ui-button clickEvent="confirmBuyingPriceUpdate" button-name="Update Price"
                loading="true" cssClass="btn btn-primary" />
        </x-slot>
    </x-ui-dialog-box>

    {{-- Selling Price Update Dialog --}}
    <x-ui-dialog-box id="sellingPriceDialog" title="Mass Update Selling Price" width="400px" height="300px"
        onOpened="openSellingPriceDialog" onClosed="closeSellingPriceDialog">
        <x-slot name="body">
            <div class="row mb-3">
                <div class="col-12">
                    <p>Masukkan harga jual untuk {{ count($selectedIds ?? []) }} barang:</p>
                </div>
            </div>
            <div class="row">
                <x-ui-text-field
                    label="Harga Jual"
                    model="newSellingPrice"
                    type="number"
                    required="true"
                    enabled="true"
                    actionValue="Edit"
                    Currency="IDR"
                />
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-ui-button clickEvent="closeSellingPriceDialog" button-name="Cancel"
                cssClass="btn btn-secondary" />
            <x-ui-button clickEvent="confirmSellingPriceUpdate" button-name="Update Price"
                loading="true" cssClass="btn btn-primary" />
        </x-slot>
    </x-ui-dialog-box>

</x-ui-page-card>
</div>
