
<x-ui-page-card title="Catalogue">
    <div class="container mx-auto">
        <div class="filter-sidebar">
            <div class="filter-item">
                <label for="filterDescription">Description</label>
                <input type="text" id="filterDescription" placeholder="Enter description...">
            </div>
            <div class="filter-item">
                <label for="filterPrice">Price Range</label>
                <input type="text" id="filterPrice" placeholder="Enter price range...">
            </div>
            <div class="filter-item">
                <label for="filterCode">Product Code</label>
                <input type="text" id="filterCode" placeholder="Enter product code...">
            </div>
        </div>
        <!-- Main Content -->
        <div class="main-content">
            @foreach($materials as $key => $material)
                <div class="list-group-item">
                    <div class="image-container">
                        @if($material->Attachment->first())
                        <img src="https://via.placeholder.com/300" alt="Material Photo">

                        @else
                            <img src="{{ asset('path/to/default/image.png') }}" alt="No Material Photo" class="img-fluid">
                        @endif
                    </div>
                    <div class="material-info">
                        <div><strong>Description:</strong> {{ $material->descr }}</div>
                        <div><strong>Code:</strong> {{ $material->code }}</div>
                        <div><strong>Price:</strong> {{ $material->selling_price }}</div>
                    </div>
                    <div class="button-group">
                        <button wire:click="addToCart({{ $material->id }})">Add to Cart</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-ui-page-card>
