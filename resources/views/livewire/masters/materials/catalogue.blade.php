
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
        .container { display: flex; max-width: 1200px; margin: auto; padding-top: 10px; }
        .filter-sidebar { width: 25%; padding: 20px; border-right: 1px solid #ddd; }
        .filter-item { margin-bottom: 15px; }
        .main-content { flex-grow: 1; padding: 20px; }
        .list-group { max-height: 600px; overflow-y: auto; }
        .list-group-item { border: 1px solid #eee; padding: 15px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
        .image-container { width: 100px; height: 100px; margin-right: 20px; }
        .image-container img { width: 100%; height: 100%; object-fit: cover; }
        .material-info { flex-grow: 1; }
        .button-group { display: flex; flex-direction: column; gap: 10px; }
        .button-group button { padding: 5px 10px; border: none; cursor: pointer; background-color: #007bff; color: white; border-radius: 3px; }
        .button-group button:hover { background-color: #0056b3; }
        .pagination { display: flex; justify-content: center; list-style-type: none; padding: 0; }
        .pagination li { margin: 0 5px; }
        .pagination li a { border: 1px solid #ddd; padding: 5px 10px; color: #333; text-decoration: none; }
        .pagination li.active a { background-color: #007bff; color: white; border-color: #007bff; }
        .pagination li.disabled a { color: #ccc; }
    </style>
    <x-ui-page-card title="Catalogue">
        <div class="container mx-auto">
            <div class="filter-sidebar">
                <div class="filter-item">
                    <label for="searchDescr">Search by Description</label>
                    <input type="text" id="searchDescr" wire:model.debounce.300ms="searchDescr" placeholder="Search materials...">
                </div>
                <div class="filter-item">
                    <label for="searchPrice">Filter by Price</label>
                    <input type="text" id="searchPrice" wire:model.debounce.300ms="searchPrice" placeholder="Filter by Price">
                </div>
                <div class="filter-item">
                    <label for="searchCode">Search by Code</label>
                    <input type="text" id="searchCode" wire:model.debounce.300ms="searchCode" placeholder="Search by Code">
                </div>
                <!-- Add more filters as needed -->
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="list-group mt-5" id="scroll-container">
                    @foreach($materials as $key => $material)
                        <div class="list-group-item">
                                <div class="image-container">
                                    @if($material->attachments->first())
                                        <img src="{{ Storage::url($material->attachments->first()->path) }}" alt="Material Photo">
                                    @else
                                        <img src="" alt="No Material Photo" class="img-fluid">
                                    @endif
                                </div>
                            <div class="material-info">
                                <div><strong>Description:</strong> {{ $material->descr }}</div>
                                <div><strong>Code:</strong> {{ $material->code }}</div>
                                <div><strong>Price:</strong> {{ $material->selling_price }}</div>
                            </div>
                            <div class="button-group">
                                <button wire:click="Edit({{ $material->id }})">Edit</button>
                                <button wire:click="View({{ $material->id }})">View</button>
                                <button wire:click="addToCart({{ $material->id }})">Add to Cart</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-end mt-4">
                    {{ $materials->links() }}
                </div>
            </div>
        </div>
    </x-ui-page-card>