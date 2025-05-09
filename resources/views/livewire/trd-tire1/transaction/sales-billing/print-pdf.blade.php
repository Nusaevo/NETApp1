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
                    <div class="row">
                        <div class="col-12">
                            <h4 class="text-center">PLATINA</h4>
                            <p class="text-center">KERTAJAYA 16, SURABAYA</p>
                            <p class="text-end">Tgl.: {{ now()->format('d-M-Y') }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <p>Mohon Periksa Nota-nota tersebut di bawah ini</p>
                        </div>
                    </div>

                    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid #000; padding: 8px; text-align: left;">Tgl.</th>
                                <th style="border: 1px solid #000; padding: 8px; text-align: left;">No.</th>
                                <th style="border: 1px solid #000; padding: 8px; text-align: right;">Rp.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $grandTotal = 0;
                            @endphp
                            @foreach ($orders as $order)
                                @php
                                    $grandTotal += $order->total_amt;
                                @endphp
                                <tr>
                                    <td style="border: 1px solid #000; padding: 8px;">
                                        {{ \Carbon\Carbon::parse($order->tr_date)->format('d-M-Y') }}
                                    </td>
                                    <td style="border: 1px solid #000; padding: 8px;">
                                        {{ $order->tr_code }}
                                    </td>
                                    <td style="border: 1px solid #000; padding: 8px; text-align: right;">
                                        {{ number_format($order->total_amt, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="2" style="border: 1px solid #000; padding: 8px; text-align: right;">
                                    <strong>Jumlah:</strong>
                                </td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: right;">
                                    <strong>{{ number_format($grandTotal, 2, ',', '.') }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="row mt-5">
                        <div class="col-12">
                            <p>Ttd:</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    <script>
        function printInvoice() {
            window.print();
        }
    </script>
</div>
