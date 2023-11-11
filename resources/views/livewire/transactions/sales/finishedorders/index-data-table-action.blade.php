@if( $value->is_finished == 1)
<a href="#"  wire:click="$emit('sales_finishorder_index_edit', {{ $value->id}})" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square fs-2 me-2"></i>Ubah Status</a>
<a href="{{ route('sales_order_final.printsmallpdf', ['id'=>$value->id ]) }}"   class="btn btn-primary btn-sm"><i class="fa fa-print fs-2 me-2"></i>Nota Kecil</a>
<a href="{{ route('sales_order_final.printbigpdf', ['id'=>$value->id ]) }}"   class="btn btn-primary btn-sm"><i class="fa fa-print fs-2 me-2"></i>Nota Besar</a>
@endif



