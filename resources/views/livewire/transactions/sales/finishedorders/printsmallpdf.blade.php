
<div>
    <a href="{{ route('sales_order_final.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2"><i class="bi bi-arrow-left-circle fs-2 me-2"></i> Sales Order </a>
</div>
{{-- <link rel="stylesheet" href="{{ asset('/customs/css/smallinvoice.css') }}">  --}}
<link rel="stylesheet" type="text/css" href="{{ asset('customs/css/smallinvoice.css') }}">
<div  class="card">
    <div class="card-body">
      <div class="container mb-5 mt-3">
        <div class="row d-flex align-items-baseline">
          <div class="col-xl-9">
            <p style="color: #7e8d9f;font-size: 20px;">Nota Penjualan >> <strong>No: {{$this->sales_order->id  }}</strong></p>
          </div>
          <div class="col-xl-3 float-end">
            <a  class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark" onclick="print()" ><i
                class="fas fa-print text-primary"></i> Print</a>
          </div>
          <hr>
        </div>
        <div id="print">
        <div class="invoice-box" >
          <table >
              <tr class="top">
                  <td colspan="6">
                      <table class='tbl'>
                          <tr>
                              <td >
                                 <b> TIGA PUTRA </b><br>
                                  Jl. KH. Mimbar <br>
                                  Kabupaten Jombang <br>
                                  Jawa Timur 61419
                              </td>
                         </tr>
                         <tr class="top_border">
                            <td>
                                 Nomor Nota : #<b>{{$this->sales_order->id  }}</b><br>
                                 Tanggal : <b>{{ $this->sales_order->transaction_date}}</b><br>
                                 Pelanggan : <b>{{ $this->sales_order->customer_name}}</b><br>
                                 @if (isset($this->sales_order->customer->address) )
                                 Alamat : <b>{{ $this->sales_order->customer->address}}</b><br>
                                 @endif
                                 Pembayaran : <b>{{ $this->sales_order->payment->name}}</b>
                            </td>
                         </tr>
                         <tr class="top_border"><td></td></tr>
                      </table>
                  </td>
              </tr>
              <table class='tbl'>
              <tr>
                <th class="item center" scope="col">Qty</th>
                <th class="item center" scope="col">Description</th>
                <th class="item center" scope="col">Harga</th>
                <th class="item center" scope="col">SubTotal</th>
              </tr>
              @php
              $grand_total = 0;
              @endphp

              @foreach ($sales_order->sales_order_details as $key => $so)
              @if ($so->qty_wo != 0 )
              <tr>
                  <td class="item center qty_column_width"> {{ qty($so->qty_wo ) }} {{ $so->unit_name }}</td>
                  <td class="item item_column_width"> {{ $so->item_name }}</td>
                  <td class="item center"> {{ qty($so->price ) }} </td>
                  <td class="item center"> {{ qty($so->price * $so->qty_wo  ) }} </td>
              </tr>
              @endif
              @php
                  $grand_total +=$so->price * $so->qty_wo;
              @endphp
              @endforeach
              </table>
              <table class="item" style="width: 100%">
               <tr class="top_border">
                    <td class=" right" colspan="5"><b>TOTAL :       {{ qty($grand_total) }}</b></td>
              </tr>
              <tr >
                <td class="" colspan="5"><b>Terbilang :</b> <u># <b>{{ terbilang($grand_total) }}</b> #</u></td>
              </tr>

              <tr>
                <td class="" colspan="5"><b>Catatan : Barang yang sudah dibeli tidak dapat dikembalikkan.</b></td>
              </tr>
              </table>
          </table>
      </div></div>
    </div>
  </div>
</body>
  <script type="text/javascript">
     function print(){
      var page =document.getElementById("print");
        var newWin=window.open('','Print-Window');
        newWin.document.open();
        newWin.document.write('<html > <link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}" ><body onload="window.print()" style="max-height:72mm;">'+page.innerHTML+'</body></html>');
        newWin.document.close();
        setTimeout(function(){newWin.close();},10);
     }
</script>
