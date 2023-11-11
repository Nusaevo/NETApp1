@if( $value->is_finished == 1)
<a href="{{ route('sales_order_warehouse.printpdf', ['id'=>$value->id ]) }}"   class="btn btn-primary btn-sm"><i class="fa fa-print fs-2 me-2"></i>Nota Gudang</a>
@endif
