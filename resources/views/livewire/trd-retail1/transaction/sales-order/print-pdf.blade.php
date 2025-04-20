<div>
    <div>
        <x-ui-button click-event="" type="Back" button-name="Back" />
    </div>
    {{-- <link rel="stylesheet" href="{{ asset('/customs/css/smallinvoice.css') }}"> --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/smallinvoice.css') }}">
    <div class="card">
        <div class="card-body">
            <div class="container mb-5 mt-3">
                <div class="row d-flex align-items-baseline">
                    <div class="col-xl-9">
                        <p style="color: #7e8d9f;font-size: 20px;">Nota Penjualan >> <strong>No:
                                {{ $this->object->tr_id }}</strong></p>
                    </div>
                    <div class="col-xl-3 float-end">
                        <a class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark"
                            onclick="print()"><i class="fas fa-print text-primary"></i> Print</a>
                    </div>
                    <hr>
                </div>
                <div id="print">
                    <div class="invoice-box">
                        <table>
                            <tr class="top">
                                <td colspan="6">
                                    <table class="tbl" style="width: 100%; text-align: center; margin-top: 10px;">
                                        <tr>
                                        <td style="width: 20%; text-align: left; vertical-align: middle;">
                                            <div style="text-align: left;">
                                                <img src="{{ asset('customs/logos/TrdRetail1.png') }}" alt="Logo" style="width: 140px; height: 60px; margin-bottom: 5px;">
                                            </div>
                                        </td>
                                    </tr>
                                        <tr>
                                            <td>
                                                <b> Knit And Cro </b><br>
                                                08888052888<br>
                                                Ruko Pluit Village No 59 <br>
                                                Jakara
                                            </td>
                                        </tr>
                                        <tr class="top_border" style="text-align: left;">
                                            <td>
                                                Tanggal : <b>{{ $this->object->tr_date }}</b><br>
                                            </td>
                                        </tr>
                                        <tr class="top_border">
                                            <td></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <table class='tbl'>
                                @php
                                    $grand_total = 0;
                                @endphp

                                @foreach ($object->OrderDtl as $key => $item)
                                    @if ($item->qty != 0)
                                        <tr>
                                            <td colspan="4"><b>{{ $item->Material->brand }} {{ $item->Material->category }}</b></td>
                                        </tr>
                                        <tr>
                                            <td colspan="4"> {{ $item->Material->code }}  {{ $item->Material->specs['color_code'] }}  {{ $item->Material->specs['color_name']}}</td>
                                        </tr>
                                        <tr>
                                            <td class="item left">{{ qty($item->qty) }} {{ $item->matl_uom }}</td>
                                            <td class="item left">@Rp. {{ number_format($item->price, 0, ',', '.') }}
                                            </td>
                                            <td class="item right">Rp.
                                                {{ number_format($item->price * $item->qty, 0, ',', '.') }}</td>
                                        </tr>
                                        @php
                                            $grand_total += $item->price * $item->qty;
                                        @endphp
                                    @endif
                                @endforeach
                            </table>

                            <!-- Bagian Total -->
                            <table class="tbl">
                                <tr class="top_border">
                                    <td class="right"><b>Total:</b></td>
                                    <td class="right"><b>Rp. {{ number_format($grand_total, 0, ',', '.') }}</b></td>
                                </tr>
                            </table>
                            <table class="tbl" style="width: 100%; text-align: center; margin-top: 10px;">
                            <tr>
                                <td colspan="2" style="text-align: left;">
                                    KEMALA DEWI - 088123501235
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <b>---------- Terima Kasih ----------</b>
                                </td>
                            </tr>
                        </table>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        function print() {
            var page = document.getElementById("print");
            var newWin = window.open('', 'Print-Window');
            newWin.document.open();
            newWin.document.write(
                '<html > <link rel="stylesheet" href="{{ asset('customs/css/smallinvoice.css') }}" ><body onload="window.print()" style="max-height:72mm;">' +
                page.innerHTML + '</body></html>');
            newWin.document.close();
            setTimeout(function() {
                newWin.close();
            }, 10);
        }

        // window.addEventListener('load', function() {
        //     function printPage() {
        //         var page = document.getElementById("print");
        //         var newWin = window.open('', 'Print-Window');
        //         newWin.document.open();
        //         newWin.document.write('<html><link rel="stylesheet" href="/customs/css/smallinvoice.css"><body style="max-height:72mm;">' + page.innerHTML + '</body></html>');
        //         newWin.document.close();
        //         setTimeout(function() {
        //             newWin.print();
        //             newWin.close();
        //         }, 10);
        //     }
        //     printPage();
        // });
    </script>
