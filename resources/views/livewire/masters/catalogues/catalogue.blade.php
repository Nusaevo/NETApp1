
<x-ui-page-card title="Catalogue">
    <div>
        @include('layout.customs.notification')
    </div>
    <div class="container mx-auto">
        <x-ui-expandable-card id="ReportFilterCard" title="Filter" :isOpen="true">
            <form wire:submit.prevent="search">
                <div class="card-body">
                    <x-ui-text-field label="Cari Nama Barang" model="inputs.description" type="text" action="Edit" placeHolder="" span='Full'/>
                    <x-ui-text-field label="Harga Jual" model="inputs.selling_price1" type="number" action="Edit" placeHolder="" span='Half'/>
                    <x-ui-text-field label="" model="inputs.selling_price2" type="number" action="Edit" placeHolder="" span='Half'/>
                    <x-ui-text-field label="Code Barang" model="inputs.code" type="text" action="Edit" placeHolder="" span='Full'/>

                </div>

                <div class="card-footer d-flex justify-content-end">
                    <div>
                        <x-ui-button click-event="search" button-name="Search" loading="true" action="Edit" cssClass="btn-primary" />
                    </div>
                </div>
            </form>
        </x-ui-expandable-card>


        </div>
        <!-- Main Content -->
        <div class="main-content">
            @foreach($materials as $key => $material)
                <div class="list-group-item">
                    <div class="image-container">
                        @if($material->Attachment->first())
                            <img src="{{ $material->Attachment->first()->getUrl() }}" alt="Captured Image" class="photo-box-image" style="max-width: 100%; max-height: 100%;">
                        @else
                            <img src="https://via.placeholder.com/300" alt="Material Photo" style="max-width: 100%; max-height: 100%;">
                        @endif
                    </div>
                    <div class="material-info">
                        <div><strong>Description:</strong> {{ $material->descr }}</div>
                        <div><strong>Code:</strong> {{ $material->code }}</div>
                        <div><strong>Price:</strong> {{ $material->selling_price }}</div>
                    </div>
                    <div class="text-right">
                        <x-ui-button
                        :click-event="'addToCart(' . $material->id . ', \'' . $material->code . '\')'"
                        button-name="Add To Cart"
                        loading="true"
                        action="Edit"
                        cssClass="btn-primary"
                    />
                    </div>

                </div>
            @endforeach
            </div>
            <div class="card-footer d-flex justify-content-end">
                @if ($materials->hasPages())
                    <nav class="pagination" aria-label="Pagination">
                        {{-- Previous Page Link --}}
                        @if ($materials->onFirstPage())
                            <a class="pagination-previous" disabled>&laquo;</a>
                        @else
                            <a wire:click="previousPage" href="javascript:void(0);" rel="prev" class="pagination-previous">&laquo;</a>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($materials->links() as $link)
                            @if (is_array($link))
                                <a wire:click="gotoPage({{ $link['url'] }})" href="javascript:void(0);" class="{{ $link['active'] ? 'pagination-link is-current' : 'pagination-link' }}">{{ $link['label'] }}</a>
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($materials->hasMorePages())
                            <a wire:click="nextPage" href="javascript:void(0);" rel="next" class="pagination-next">&raquo;</a>
                        @else
                            <a class="pagination-next" disabled>&raquo;</a>
                        @endif
                    </nav>
                @endif
            </div>



        </div>
    </div>
</x-ui-page-card>
