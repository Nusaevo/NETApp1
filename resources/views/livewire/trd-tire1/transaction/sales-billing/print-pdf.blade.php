<div>

    <div class="row d-flex align-items-baseline">
        <div class="col-xl-9">
            <x-ui-button clickEvent="" type="Back" button-name="Back" />
        </div>
        <div class="col-xl-3 float-end">
            <a class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark" onclick="printInvoice()">
                <i class="fas fa-print text-primary"></i> Print
            </a>
        </div>
        <hr>
    </div>

    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <!-- Card hanya tampil di layar, tidak saat print -->
    <div class="card d-print-none" style="max-width: 700px; margin: 30px auto; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 0px 1.5px rgba(0,0,0,0.03); border-radius: 10px; padding: 32px 32px 40px 32px;">
        <div style="font-family: 'Courier New', Courier, monospace; font-size: 16px;">
            <div style="display: flex; justify-content: flex-end;">
                <div style="text-align: right;">
                    <span>Tgl.: {{ now()->format('d-M-Y') }}</span>
                </div>
            </div>
            <div style="border: 1px solid #000; padding: 10px; margin-bottom: 10px;">
                <div style="font-weight: bold;">PLATINA</div>
                <div>KERTAJAYA 16, SURABAYA</div>
            </div>
            <div style="margin-bottom: 10px;">
                <span style="text-decoration: underline; font-weight: bold;">Mohon Periksa Nota-nota tersebut di bawah ini</span>
            </div>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
                @php $grandTotal = 0; @endphp
                @foreach ($orders as $i => $order)
                    @php $grandTotal += $order->amt; @endphp
                    <tr style="height: 25px;">
                        <td style="padding: 2px 0 2px 5px;">{{ $i + 1 }}.</td>
                        <td style="padding: 2px 5px;">Tgl. {{ \Carbon\Carbon::parse($order->tr_date)->format('d-m-Y') }}</td>
                        <td style="padding: 2px 5px;">No. {{ $order->tr_code }}</td>
                        <td style="padding: 2px 5px; text-align: right;">Rp.</td>
                        <td style="padding: 2px 5px; text-align: right;">{{ number_format($order->amt, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </table>
            <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="padding: 0 5px 0 0; text-align: right;">Jumlah:</td>
                        <td style="padding: 0 5px; text-align: right; border-bottom: 2px solid #000; font-weight: bold;">
                            Rp. {{ number_format($grandTotal, 2, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>
            <div style="margin-top: 30px;">
                Ttd:
            </div>
        </div>
    </div>

    <!-- Area print tetap tampil saat print -->
    <div id="print" class="d-none d-print-block p-20">
        <div style="max-width: 700px; margin: 0 auto; font-family: 'Courier New', Courier, monospace; font-size: 16px;">
            <div style="display: flex; justify-content: flex-end;">
                <div style="text-align: right;">
                    <span>Tgl.: {{ now()->format('d-M-Y') }}</span>
                </div>
            </div>
            <div style="border: 1px solid #000; padding: 10px; margin-bottom: 10px;">
                <div style="font-weight: bold;">PLATINA</div>
                <div>KERTAJAYA 16, SURABAYA</div>
            </div>
            <div style="margin-bottom: 10px;">
                <span style="text-decoration: underline; font-weight: bold;">Mohon Periksa Nota-nota tersebut di bawah ini</span>
            </div>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
                @php $grandTotal = 0; @endphp
                @foreach ($orders as $i => $order)
                    @php $grandTotal += $order->amt; @endphp
                    <tr style="height: 25px;">
                        <td style="padding: 2px 0 2px 5px;">{{ $i + 1 }}.</td>
                        <td style="padding: 2px 5px;">Tgl. {{ \Carbon\Carbon::parse($order->tr_date)->format('d-m-Y') }}</td>
                        <td style="padding: 2px 5px;">No. {{ $order->tr_code }}</td>
                        <td style="padding: 2px 5px; text-align: right;">Rp.</td>
                        <td style="padding: 2px 5px; text-align: right;">{{ number_format($order->amt, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </table>
            <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="padding: 0 5px 0 0; text-align: right;">Jumlah:</td>
                        <td style="padding: 0 5px; text-align: right; border-bottom: 2px solid #000; font-weight: bold;">
                            Rp. {{ number_format($grandTotal, 2, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>
            <div style="margin-top: 30px;">
                Ttd:
            </div>
        </div>
    </div>

    <script>
        function printInvoice() {
            window.print();
        }
    </script>
</div>

