<div>
    <div>
        <div>
            <x-ui-button clickEvent="" type="Back" button-name="Back" />
        </div>
    </div>

    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <body>
        <div class="card">
            <div class="card-body">
                <div class="container mb-5 mt-3">
                    <h3 class="text-left">Proses Nota Gajah Tunggal GT RADIAL per Customer</h3>
                    <p class="text-left">Tanggal Proses: {{ \Carbon\Carbon::now()->format('d-M-Y') }}</p>

                    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid #000; padding: 8px;">Nama Pelanggan</th>
                                <th style="border: 1px solid #000; padding: 8px;">No. Nota</th>
                                <th style="border: 1px solid #000; padding: 8px;">Kode Brg.</th>
                                <th style="border: 1px solid #000; padding: 8px;">Nama Barang</th>
                                <th style="border: 1px solid #000; padding: 8px;">T. Ban</th>
                                <th style="border: 1px solid #000; padding: 8px;">Point</th>
                                <th style="border: 1px solid #000; padding: 8px;">T. Point</th>
                                <th style="border: 1px solid #000; padding: 8px;">Nota GT</th>
                                <th style="border: 1px solid #000; padding: 8px;">Customer Point</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                @foreach ($order->OrderDtl as $OrderDtl)
                                    <tr>
                                        <td style="border: 1px solid #000; padding: 8px;">
                                            {{ $order->Partner->name ?? 'N/A' }}
                                        </td>
                                        <td style="border: 1px solid #000; padding: 8px;">
                                            {{ $order->tr_code }}
                                        </td>
                                        <td style="border: 1px solid #000; padding: 8px;">
                                            {{ $OrderDtl->matl_code }}
                                        </td>
                                        <td style="border: 1px solid #000; padding: 8px;">
                                            {{ $OrderDtl->matl_descr }}
                                        </td>
                                        <td style="border: 1px solid #000; padding: 8px; text-align: center;">
                                            {{ ceil($OrderDtl->qty) }}
                                        </td>
                                        <td style="border: 1px solid #000; padding: 8px; text-align: center;">
                                            {{ $OrderDtl->SalesReward->reward ?? 0 }}
                                        </td>
                                        <td style="border: 1px solid #000; padding: 8px; text-align: center;">
                                            {{ round(($OrderDtl->qty / ($OrderDtl->SalesReward->qty ?? 1)) * ($OrderDtl->SalesReward->reward ?? 0), 2) }}
                                        </td>
                                        <td style="border: 1px solid #000; padding: 8px;">
                                            {{ $OrderDtl->gt_tr_code ?? '-' }}
                                        </td>
                                        <td style="border: 1px solid #000; padding: 8px;">
                                            {{ $order->Partner->city ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>

                    <p class="text-end mt-3">Tanggal Cetak: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
    </body>

    <script type="text/javascript">
        function printInvoice() {
            var page = document.getElementById("print");
            var newWin = window.open('', 'Print-Window');
            newWin.document.open();
            newWin.document.write(
                '<html>' +
                '<link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}" >' +
                '<body onload="window.print()">' +
                page.innerHTML +
                '</body></html>'
            );
            newWin.document.close();
            setTimeout(function() {
                newWin.close();
            }, 10);
        }
    </script>
</div>
