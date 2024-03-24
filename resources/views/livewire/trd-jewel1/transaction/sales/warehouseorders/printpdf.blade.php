
<div>
    <a href="{{ route('sales_order_warehouse.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2"><i class="bi bi-arrow-left-circle fs-2 me-2"></i> Sales Order </a>
</div>
<link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">
<div  class="card">
    <div class="card-body">
      <div class="container mb-5 mt-3">
        <div class="row d-flex align-items-baseline">
          <div class="col-xl-9">
            <p style="color: #7e8d9f;font-size: 20px;">Nota Gudang >> <strong>No: {{$this->sales_order->id  }}</strong></p>
          </div>
          <div class="col-xl-3 float-end">
            <a  class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark" onclick="print()" ><i
                class="fas fa-print text-primary"></i> Print</a>
          </div>
          <hr>
        </div>
        <div id="print">
        <div class="invoice-box" >
          <table>
              <tr class="top">
                  <td colspan="6">
                      <table>
                          <tr>
                              <td rowspan="5">
                                 <h1> UD 3 PUTRA</h1>
                                  Jl. KH. Mimbar <br>
                                  Kabupaten Jombang <br>
                                  Jawa Timur 61419
                              </td>
                              <td class="info">
                                 Nomor Nota : #<b>{{$this->sales_order->id  }}</b><br>
                                 Tanggal : <b>{{ $this->sales_order->transaction_date}}</b><br>
                                 Pelanggan : <b>{{ $this->sales_order->customer_name}}</b><br>
                              </td>
                          </tr>
                      </table>
                  </td>
              </tr>
              <tr class="heading">
                <th style="text-align:center;" scope="col">No</th>
                <th style="text-align:center;" scope="col">Description</th>
                <th style="text-align:center;" scope="col">Qty</th>
                <th style="text-align:center;" scope="col">Gudang</th>
              </tr>
              @php
              $grand_total = 0;
              @endphp
              @foreach ($sales_order->sales_order_details as $key => $so)
              @if ($so->qty_wo != 0 )
              <tr>
                  <td class="item">{{ $key +1 }}</td>
                  <td > {{ $so->item_name }}</td>
                  <td class="item"> {{ qty($so->qty_wo ) }} {{ $so->unit_name }}</td>
                  <td class="item"> {{ $so->item_warehouse->warehouse->name }} </td>
              </tr>
              @endif
              @endforeach
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
        newWin.document.write('<html><link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}"><body onload="window.print()">'+page.innerHTML+'</body></html>');
        newWin.document.close();
        setTimeout(function(){newWin.close();},10);
     }
</script>
