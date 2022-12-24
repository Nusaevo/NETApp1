@if( $value->is_finished ==1)
<a href="{{ route('sales.order.printpdf', ['id'=>$value->id ]) }}"   class="btn btn-primary btn-sm"><i class="fa fa-print fs-2 me-2"></i>Print</a>
<a href="{{ route('sales.order.printpdf', ['id'=>$value->id ]) }}"   class="btn btn-primary btn-sm"><i class="fa fa-edit fs-2 me-2"></i>Retur</a>
@else
 <a href="{{ route('sales.order.detail', ['id'=>$value->id ]) }}"   class="btn btn-primary btn-sm"><i class="fa fa-edit fs-2 me-2"></i>Order Gudang</a>
@endif



