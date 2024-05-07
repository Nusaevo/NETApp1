
<x-ui-page-card title="Catalogue">
    <div>
        @include('layout.customs.notification')
    </div>
        <x-ui-expandable-card id="ReportFilterCard" title="Filter" :isOpen="false">
            <form wire:submit.prevent="search">
                <div class="card-body">
                    <x-ui-text-field label="Cari Nama Barang" model="inputs.name" type="text" action="Edit" placeHolder="" span='Full'/>
                    <x-ui-text-field label="Cari Nama Bahan" model="inputs.description" type="text" action="Edit" placeHolder="" span='Full'/>
                    <x-ui-text-field label="Harga Jual" model="inputs.selling_price1" type="number" action="Edit" placeHolder="" span='Half'/>
                    <x-ui-text-field label="" model="inputs.selling_price2" type="number" action="Edit" placeHolder="" span='Half'/>
                    <x-ui-text-field label="Code Barang" model="inputs.code" type="text" action="Edit" placeHolder="" span='Full'/>

                </div>

                <div class="card-footer d-flex justify-content-end">
                    <div>
                        <x-ui-button clickEvent="search" button-name="Search" loading="true" action="Edit" cssClass="btn-primary" />
                    </div>
                </div>
            </form>
        </x-ui-expandable-card>
        <div class="main-content">
            @foreach($materials as $key => $material)
                <div class="list-catalogue-item">
                    <div class="image-container">
                        @if($material->Attachment->first())
                            <img src="{{ $material->Attachment->first()->getUrl() }}" alt="Captured Image" class="photo-box-image" style="max-width: 100%; max-height: 100%;">
                        @else
                            <img src="https://via.placeholder.com/300" alt="Material Photo" style="max-width: 100%; max-height: 100%;">
                        @endif
                    </div>
                    <div class="material-info">
                        <div><strong>Deskripsi:</strong> {{ $material->name }}</div>
                        <div><strong>Deskripsi Bahan:</strong> {{ $material->descr }}</div>
                        <div><strong>Code:</strong> {{ $material->code }}</div>
                        <div><strong>Harga (USD):</strong> {{ dollar(currencyToNumeric($material->jwl_selling_price)) }}</div>
                        <div><strong>Harga (IDR):</strong> {{ rupiah(currencyToNumeric($material->jwl_selling_price) * $currencyRate) }}</div>
                    </div>
                    <div class="text-right">
                        <x-ui-button
                        :clickEvent="'addToCart(' . $material->id . ', \'' . $material->code . '\')'"
                        button-name="Add To Cart"
                        loading="true"
                        action="Edit"
                        cssClass="btn-primary"
                    />
                    </div>

                </div>
            @endforeach
            </div>
            <div class="pagination-container">
                @include('components.ui-pagination', ['paginator' => $materials])
            </div>

        </div>
    </div>
</x-ui-page-card>
