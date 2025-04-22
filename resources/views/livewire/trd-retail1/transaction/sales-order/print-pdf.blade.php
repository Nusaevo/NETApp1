<div>
    <x-ui-button click-event="" type="Back" button-name="Back" />

    <!-- load CSS invoice -->
    <link rel="stylesheet" href="{{ asset('customs/css/TrdRetail1/smallinvoice.css') }}">

    <div class="card border-0">
        <div class="card-body p-0">

            {{-- Header Nota --}}
            <div
                style="padding:0 4px; margin-bottom:4px; display:flex; justify-content:space-between; align-items:center;">
                <p style="margin:0; font-size:10px; color:#7e8d9f;">
                    Nota Penjualan » <strong>No: {{ $this->object->tr_id }}</strong>
                </p>
                <button onclick="printInvoice()" style="background:none; border:none; padding:0; cursor:pointer;">
                    <i class="fas fa-print text-primary"></i> Print
                </button>
            </div>

            {{-- Area cetak --}}
            <div id="print">
                <div class="invoice-box">

                    {{-- Logo --}}
                    <div class="logo-container">
                        <img src="{{ asset('customs/logos/TrdRetail1.png') }}" alt="Logo"
                            style="
                            width: 50mm;        /* lebar logo 30 mm */
                            height: auto;       /* ketinggian mengikuti rasio */
                            max-height: 50mm;   /* batas tinggi 15 mm */
                            object-fit: contain;
                            display: block;
                            margin: 0 auto 4px;
                            ">
                    </div>

                    {{-- Info Toko --}}
                    <table>
                        <tr>
                            <td>
                                <p
                                    style="    text-align: center;
                                    padding-bottom: 4px;
                                    font-size: 16px !important;
                                    line-height: 1.2;
                                    font-weight: bold;">
                                    <b>Knit And Cro</b><br>
                                    08888052888<br>
                                    Ruko Pluit Village No 59<br>
                                    Jakarta
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td class="top-border" style="text-align:left; padding-top:4px;">
                                Tanggal: <b>{{ $this->object->tr_date }}</b> <br>
                                Pembayaran: <b>{{ $this->object->payment_term }}</b>
                            </td>
                        </tr>
                    </table>

                    {{-- Detail Barang --}}
                    <table style="margin-top:4px;">
                        @php $grand_total = 0; @endphp
                        @foreach ($object->OrderDtl as $item)
                            @continue(!$item->qty)
                            <tr>
                                <td colspan="3" style="padding-top:4px;"><b>{{ $item->Material->name }}</b></td>
                            </tr>
                            <tr>
                                <td>{{ qty($item->qty) }} {{ $item->matl_uom }}</td>
                                <td>Rp. {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td style="text-align:right;">
                                    Rp. {{ number_format($item->price * $item->qty, 0, ',', '.') }}
                                </td>
                            </tr>
                            @php $grand_total += $item->price * $item->qty; @endphp
                        @endforeach
                    </table>

                    {{-- Total --}}
                    <table style="margin-top:4px;">
                        <tr>
                            <td style="text-align:right; border-top:1px solid #000; padding-top:4px;">
                                <b>Total:</b>
                            </td>
                            <td style="text-align:right; border-top:1px solid #000; padding-top:4px;">
                                <b>Rp. {{ number_format($grand_total, 0, ',', '.') }}</b>
                            </td>
                        </tr>
                    </table>

                    {{-- Footer --}}
                    <table style="width:100%; text-align:center; margin-top:6px;">
                        <tr>
                            <td style="text-align:left; padding-bottom:4px;">
                                {{ $this->object->Partner->name }}
                                @if ($this->object->Partner->phone)
                                    - {{ $this->object->Partner->phone }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><b>— Terima Kasih —</b></td>
                        </tr>
                    </table>

                </div>
            </div>

        </div>
    </div>
</div>
<script>
    function printInvoice() {
        // Ambil konten nota
        const content = document.getElementById('print').innerHTML;

        // Ambil semua <link rel="stylesheet"> dan <style> dari halaman utama
        const styles = Array.from(
            document.querySelectorAll('link[rel="stylesheet"], style')
        ).map(node => node.outerHTML).join('');

        // Buka window baru
        const printWindow = window.open('', 'Print-Window', 'width=300,height=600');

        // Tulis HTML lengkap termasuk styles
        printWindow.document.open();
        printWindow.document.write(`
        <html>
          <head>
            ${styles}
            <style>
              @page { size:80mm auto; margin:2mm; }
              body { margin:0; padding:0; }
            </style>
          </head>
          <body onload="window.focus(); window.print(); window.close();">
            ${content}
          </body>
        </html>
      `);
        printWindow.document.close();
    }
</script>
