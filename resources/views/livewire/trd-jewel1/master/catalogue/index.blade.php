
<div>
    <x-ui-page-card title="{!! $menuName !!}">
        <x-ui-expandable-card id="ReportFilterCard" title="Filter" :isOpen="true">
            <div class="card-body">
                <div class="row g-3 mb-2">
                    <x-ui-text-field label="Code Barang" model="inputs.code" type="text" action="Edit" />
                    <x-ui-text-field label="Cari Nama Barang" model="inputs.name" type="text" action="Edit" />
                    <x-ui-text-field label="Cari Nama Bahan" model="inputs.description" type="text" action="Edit" />
                </div>
                <div class="row g-3 mb-2">
                    <x-ui-text-field label="Harga Jual (From)" model="inputs.selling_price1" type="number" action="Edit" />
                    <x-ui-text-field label="Harga Jual (To)" model="inputs.selling_price2" type="number" action="Edit" />
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <x-ui-button clickEvent="search" button-name="Search" loading="true" action="Edit" cssClass="btn-primary" />
            </div>
        </x-ui-expandable-card>

        <div class=" mt-4">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                @forelse($materials as $key => $material)
                    <div class="col">
                        <div class="card h-100 shadow-sm border-0 catalogue-item">
                            <div class="position-relative">
                                    @php
                                        // Random image sizes untuk testing
                                        $imageSizes = [
                                            '300x200',   // landscape
                                            '200x300',   // portrait
                                            '500x200',   // wide
                                            '200x500',   // tall
                                            '400x400',   // square
                                            '800x600',   // large landscape
                                            '150x300',   // narrow
                                            '600x300'    // wide landscape
                                        ];
                                        $randomSize = $imageSizes[($key % count($imageSizes))];
                                    @endphp
                                    <img src="https://placehold.co/{{ $randomSize }}/{{ ['FF6B6B', '4ECDC4', '45B7D1', 'FFA07A', '98D8C8', 'F7DC6F', 'BB8FCE', '85C1E9'][($key % 8)] }}/FFFFFF?text=Test+{{ $key + 1 }}"
                                         alt="Test Material {{ $key + 1 }}"
                                         class="card-img-top catalogue-image">
                            </div>
                            <div class="card-body d-flex flex-column p-4">
                                <div class="d-flex justify-content-between align-items-start ">
                                    <h5 class="card-title text-dark mb-0 flex-grow-1 me-3">{{ $material->name ?? "Test" }}</h5>
                                    <span class="badge bg-secondary text-nowrap fs-6">{{ $material->code }}</span>
                                </div>
                                <p  style="font-size: 0.9rem; line-height: 1.4;">{{ $material->descr }}</p>
                                <div class="price-section">
                                    @if($material->isOrderedMaterial())
                                        <span class="badge bg-success fs-5 px-3 py-2">{{ rupiah($material->jwl_selling_price_idr) }}</span>
                                    @else
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge bg-info fs-6 px-3 py-2">{{ dollar($material->jwl_selling_price_usd) }}</span>
                                            <span class="badge bg-warning fs-6 px-3 py-2">{{ rupiah($material->jwl_selling_price_usd * $currencyRate) }}</span>
                                        </div>
                                    @endif
                                </div>
                                @if(isset($permissions['create']) && $permissions['create'])
                                    <div class="mt-4">
                                        <div class="d-grid gap-2">
                                            <x-ui-button :clickEvent="'addToCart(' . $material->id . ', \'' . $material->code . '\')'" button-name="Add To Cart" loading="true" action="Edit" cssClass="btn-primary w-100" />
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-warning text-center">Tidak ada data ditemukan.</div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="pagination-container mt-4">
            @include('components.ui-pagination', ['paginator' => $materials])
        </div>
    </x-ui-page-card>
</div>
