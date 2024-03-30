<div>
    <div>
        <x-ui-button click-event="" type="Back" button-name="Back"/>
    </div>
</div>

<link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">
<body>
<div class="card">
    <div class="card-body">
        <div class="container mb-5 mt-3">
            <div class="row d-flex align-items-baseline">
                <div class="col-xl-9">
                    <p style="color: #7e8d9f;font-size: 20px;">Nota Retur >> <strong>No: {{$this->object->id }}</strong></p>
                </div>
                <div class="col-xl-3 float-end">
                    <a class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark" onclick="print()"><i class="fas fa-print text-primary"></i> Print</a>
                </div>
                <hr>
            </div>
            <div id="print">
                <div class="invoice-box">
                    <table>
                        <tr class="top">
                            <td colspan="6">
                                <table>
                                    <tr>
                                        <td rowspan="5">
                                            <h1>Wijaya Mas</h1>
                                        </td>
                                        <td class="info">
                                            Nomor Nota : #<b>{{$this->object->id }}</b><br>
                                            Tanggal : <b>{{ $this->object->tr_date}}</b><br>
                                            Supplier : <b>{{ $this->object->Partner->name}}</b><br>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr class="heading">
                            <th style="text-align:center;" scope="col">No</th>
                            <th style="text-align:center;" scope="col">Code</th>
                            <th style="text-align:center;" scope="col">Description</th>
                            <th style="text-align:center;" scope="col">Qty</th>
                            <th style="text-align:center;" scope="col">Price</th>
                        </tr>
                        @php
                        $grand_total = 0;
                        @endphp
                        @foreach ($object->OrderDtl as $key => $OrderDtl)
                        @if ($OrderDtl->qty != 0 )
                        <tr>
                            <td class="item">{{ $key +1 }}</td>
                            <td> {{ $OrderDtl->matl_code }}</td>
                            <td> {{ $OrderDtl->matl_descr }}</td>
                            <td>  {{ceil(currencyToNumeric($OrderDtl->qty))}}</td>
                            {{-- <td>  {{$OrderDtl->qty }} {{ $OrderDtl->Material->MatlUom[0]->name }}</td> --}}
                            <td class="item"> {{ rupiah(ceil(currencyToNumeric($OrderDtl->price))) }} </td>

                        </tr>
                        @endif
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
    </body>
    <script type="text/javascript">
        function print() {
            var page = document.getElementById("print");
            var newWin = window.open('', 'Print-Window');
            newWin.document.open();
            newWin.document.write('<html><link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}" ><body onload="window.print()">'+page.innerHTML+'</body></html>');
            newWin.document.close();
            setTimeout(function(){newWin.close();},10);
        }

        // window.addEventListener('load', function() {
        //     function printPage() {
        //         var page = document.getElementById("print");
        //         var newWin = window.open('', 'Print-Window');
        //         newWin.document.open();
        //         newWin.document.write('<html><link rel="stylesheet" type="text/css" href="/customs/css/invoice.css"><body onload="window.print()">' + page.innerHTML + '</body></html>');
        //         newWin.document.close();
        //         setTimeout(function() {
        //             newWin.close();
        //         }, 10);
        //     }
        //     printPage();
        // });

    </script>


