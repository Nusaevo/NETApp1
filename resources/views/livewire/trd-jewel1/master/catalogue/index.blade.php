 <div>
     <x-ui-page-card title="{!! $menuName !!}">
         <x-ui-expandable-card id="ReportFilterCard" title="Filter" :isOpen="true">
             <div class="card-body">
                 <div class="row">
                     <x-ui-text-field label="Code Barang" model="inputs.code" type="text" action="Edit" />
                     <x-ui-text-field label="Cari Nama Barang" model="inputs.name" type="text" action="Edit" />
                     <x-ui-text-field label="Cari Nama Bahan" model="inputs.description" type="text" action="Edit" />
                 </div>
                 <div class="row">
                     <x-ui-text-field label="Harga Jual (From)" model="inputs.selling_price1" type="number" action="Edit" />
                     <x-ui-text-field label="Harga Jual (To)" model="inputs.selling_price2" type="number" action="Edit" />
                 </div>

             </div>

             <div class="card-footer d-flex justify-content-end">
                 <div>
                     <x-ui-button clickEvent="search" button-name="Search" loading="true" action="Edit" cssClass="btn-primary" />
                 </div>
             </div>
         </x-ui-expandable-card>
         <div class="main-content">
             @foreach($materials as $key => $material)
             <div class="list-catalogue-item">
                 <div class="image-container gallery-image-container">
                     @if($material->Attachment->first())
                     <img src="{{ $material->Attachment->first()->getUrl() }}" alt="Captured Image" class="photo-box-image gallery-photo-box-image">
                     @else
                     <img src="https://via.placeholder.com/300" alt="Material Photo" class="photo-box-image gallery-photo-box-image">
                     @endif
                 </div>
                 <div class="material-info">
                     <div class="flex-between">
                         <div><strong></strong> {{ $material->name ?? "Test" }}</div>
                         <div><strong></strong> {{ $material->code }}</div>
                     </div>
                     <div class="text-left"><strong></strong> {{ $material->descr }}</div>
                     <div class="text-center">
                         @if($material->isOrderedMaterial())
                         <strong>
                             {{ rupiah($material->jwl_selling_price_idr) }}</strong>
                         @else
                         <strong>
                             {{ dollar($material->jwl_selling_price_usd) }} -
                             {{ rupiah($material->jwl_selling_price_usd) * $currencyRate }}</strong>
                         @endif

                     </div>
                 </div>
                 <div class="text-right">
                     @if(isset($permissions['create']) && $permissions['create'])
                     <x-ui-button :clickEvent="'addToCart(' . $material->id . ', \'' . $material->code . '\')'" button-name="Add To Cart" loading="true" action="Edit" cssClass="btn-primary" />
                     @endif
                 </div>
             </div>
             @endforeach
         </div>
         <div class="pagination-container">
            @include('components.ui-pagination', ['paginator' => $materials])
        </div>

     </x-ui-page-card>
 </div>

