<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Contoh Penggunaan UI Dropdown Search Multiple Select</h3>
                </div>
                <div class="card-body">
                    <!-- Flash Messages -->
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Form dengan Multiple Dropdowns -->
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <!-- Brand Selection -->
                            <div class="col-md-6 mb-3">
                                <x-ui-dropdown-search-multiple
                                    label="Pilih Brand"
                                    model="selectedBrandIds"
                                    optionValue="str1"
                                    optionLabel="str2"
                                    query="SELECT str1, str2 FROM config_const WHERE const_group='MMATL_BRAND' AND deleted_at IS NULL"
                                    placeHolder="Ketik untuk mencari brand..."
                                    onChanged="onBrandsSelected"
                                    required="true"
                                />
                            </div>

                            <!-- Category Selection -->
                            <div class="col-md-6 mb-3">
                                <x-ui-dropdown-search-multiple
                                    label="Pilih Kategori"
                                    model="selectedCategoryIds"
                                    optionValue="id"
                                    optionLabel="name"
                                    query="SELECT id, name FROM categories WHERE active = 1"
                                    placeHolder="Pilih kategori..."
                                    onChanged="onCategoriesSelected"
                                    required="true"
                                />
                            </div>

                            <!-- Product Selection -->
                            <div class="col-md-6 mb-3">
                                <x-ui-dropdown-search-multiple
                                    label="Pilih Produk"
                                    model="selectedProductIds"
                                    optionValue="product_id"
                                    optionLabel="product_name,product_code"
                                    query="SELECT product_id, product_name, product_code FROM products WHERE stock > 0"
                                    placeHolder="Cari produk..."
                                    onChanged="onProductsSelected"
                                />
                            </div>

                            <!-- Supplier Selection -->
                            <div class="col-md-6 mb-3">
                                <x-ui-dropdown-search-multiple
                                    label="Pilih Supplier"
                                    model="selectedSupplierIds"
                                    optionValue="supplier_id"
                                    optionLabel="supplier_name"
                                    query="SELECT supplier_id, supplier_name FROM suppliers WHERE status = 'active'"
                                    placeHolder="Cari supplier..."
                                    onChanged="onSuppliersSelected"
                                />
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-save me-1"></i>
                                    Simpan
                                </button>
                                <button type="button" class="btn btn-secondary me-2" wire:click="clearAll">
                                    <i class="fas fa-trash me-1"></i>
                                    Clear All
                                </button>
                                <button type="button" class="btn btn-info me-2" wire:click="resetToDefault">
                                    <i class="fas fa-undo me-1"></i>
                                    Reset to Default
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Display Selected Values -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Data yang Dipilih:</h5>

                            <!-- Selected Brands -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Brands Selected ({{ count($selectedBrandIds) }})</h6>
                                </div>
                                <div class="card-body">
                                    @if (!empty($selectedBrandIds))
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($selectedBrandIds as $brandId)
                                                <span class="badge bg-primary">{{ $brandId }}</span>
                                            @endforeach
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                Details: {{ implode(', ', $selectedBrands) }}
                                            </small>
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">Tidak ada brand yang dipilih</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Selected Categories -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Categories Selected ({{ count($selectedCategoryIds) }})</h6>
                                </div>
                                <div class="card-body">
                                    @if (!empty($selectedCategoryIds))
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($selectedCategoryIds as $categoryId)
                                                <span class="badge bg-success">{{ $categoryId }}</span>
                                            @endforeach
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                Details: {{ implode(', ', $selectedCategories) }}
                                            </small>
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">Tidak ada kategori yang dipilih</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Selected Products -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Products Selected ({{ count($selectedProductIds) }})</h6>
                                </div>
                                <div class="card-body">
                                    @if (!empty($selectedProductIds))
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($selectedProductIds as $productId)
                                                <span class="badge bg-warning text-dark">{{ $productId }}</span>
                                            @endforeach
                                        </div>
                                        @if (!empty($selectedProducts))
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    Details:
                                                    @foreach ($selectedProducts as $productId => $product)
                                                        {{ $product['name'] }} (Rp {{ number_format($product['price']) }})
                                                        @if (!$loop->last), @endif
                                                    @endforeach
                                                </small>
                                            </div>
                                        @endif
                                    @else
                                        <p class="text-muted mb-0">Tidak ada produk yang dipilih</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Selected Suppliers -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Suppliers Selected ({{ count($selectedSupplierIds) }})</h6>
                                </div>
                                <div class="card-body">
                                    @if (!empty($selectedSupplierIds))
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($selectedSupplierIds as $supplierId)
                                                <span class="badge bg-info">{{ $supplierId }}</span>
                                            @endforeach
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                Details: {{ implode(', ', $selectedSuppliers) }}
                                            </small>
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">Tidak ada supplier yang dipilih</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Debug Information -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Debug Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Selected Brand IDs:</h6>
                                            <pre class="bg-light p-2 rounded">{{ json_encode($selectedBrandIds, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Selected Category IDs:</h6>
                                            <pre class="bg-light p-2 rounded">{{ json_encode($selectedCategoryIds, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <h6>Selected Product IDs:</h6>
                                            <pre class="bg-light p-2 rounded">{{ json_encode($selectedProductIds, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Selected Supplier IDs:</h6>
                                            <pre class="bg-light p-2 rounded">{{ json_encode($selectedSupplierIds, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="{{ asset('customs/css/dropdown-multiple-select.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script>
    // Additional JavaScript for enhanced functionality
    document.addEventListener('livewire:load', function () {
        // Listen for Livewire events
        Livewire.on('brandsUpdated', (data) => {
            console.log('Brands updated:', data);
        });

        Livewire.on('categoriesUpdated', (data) => {
            console.log('Categories updated:', data);
        });

        Livewire.on('productsUpdated', (data) => {
            console.log('Products updated:', data);
        });

        Livewire.on('suppliersUpdated', (data) => {
            console.log('Suppliers updated:', data);
        });
    });
</script>
@endpush
