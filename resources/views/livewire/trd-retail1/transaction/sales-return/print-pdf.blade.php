<div>
    @php
        use App\Services\TrdJewel1\Master\MasterService;

        $masterService = new MasterService();
    @endphp
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap');
    </style>

    <div class="container mb-5 mt-3">
        <div>
            <x-ui-button clickEvent="" type="Back" button-name="Back" />
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row d-flex align-items-baseline">
                    <div class="col-xl-9">
                        <p style="color: #7e8d9f; font-size: 20px;">Nota Retur >> <strong>No:
                                {{ $this->object->tr_id }}</strong></p>
                    </div>
                    <div class="col-xl-3 float-end">
                        <button type="button"
                                style="background: linear-gradient(135deg, #007bff, #0056b3);
                                       color: white;
                                       border: none;
                                       padding: 12px 20px;
                                       border-radius: 8px;
                                       font-size: 14px;
                                       font-weight: 500;
                                       cursor: pointer;
                                       display: inline-flex;
                                       align-items: center;
                                       gap: 8px;
                                       box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
                                       transition: all 0.2s ease;"
                                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(0, 123, 255, 0.4)';"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0, 123, 255, 0.3)';"
                                onclick="printInvoice()">
                            <i class="fas fa-print" style="font-size: 16px;"></i>
                            <span>Print Nota</span>
                        </button>
                    </div>
                    <hr>
                </div>

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
                                        Ruko Pluit Village No 59<br>
                                        Jakarta
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td class="top-border" style="text-align:left; padding-top:4px;">
                                    Tanggal: <b>{{ $this->object->tr_date }}</b> <br>
                                    Pembayaran: <b>{{ $this->object->payment_term }}</b> <br>
                                    @if($object->ExchangeOrder && $object->ExchangeOrder->OrderDtl->count() > 0)
                                        <span style="color: #666; font-style: italic;">*Dengan barang pengganti</span>
                                    @else
                                        <span style="color: #666; font-style: italic;">*Retur saja (tanpa pengganti)</span>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        {{-- Detail Barang Return --}}
                        <table style="margin-top:4px;">
                            <tr>
                                <td colspan="3" style="border-top:1px solid #000; padding-top:4px; font-weight: bold;">
                                    BARANG RETUR
                                </td>
                            </tr>
                            @php $return_total = 0; @endphp
                            @foreach ($object->ReturnDtl as $item)
                                @continue(!$item->qty)
                                <tr>
                                    <td colspan="3" style="padding-top:4px;">
                                        <b>
                                            @if($item->Material)
                                                {{ $item->Material->code }} - {{ $item->Material->name }}
                                            @else
                                                {{ $item->matl_code }} - {{ $item->matl_descr }}
                                            @endif
                                        </b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{ qty($item->qty) }} {{ $item->matl_uom }}</td>
                                    <td>Rp. {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td style="text-align:right;">
                                        Rp. {{ number_format($item->price * $item->qty, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @php $return_total += $item->price * $item->qty; @endphp
                            @endforeach
                        </table>
                        {{-- Detail Barang Exchange --}}
                        @if($object->ExchangeOrder && $object->ExchangeOrder->OrderDtl->count() > 0)
                        <table style="margin-top:8px;">
                            <tr>
                                <td colspan="3" style="border-top:1px solid #000; padding-top:4px; font-weight: bold;">
                                    BARANG PENGGANTI
                                </td>
                            </tr>
                            @php $exchange_total = 0; @endphp
                            @foreach ($object->ExchangeOrder->OrderDtl as $item)
                                @continue(!$item->qty)
                                <tr>
                                    <td colspan="3" style="padding-top:4px;">
                                        <b>
                                            @if($item->Material)
                                                {{ $item->Material->code }} - {{ $item->Material->name }}
                                            @else
                                                {{ $item->matl_code }} - {{ $item->matl_descr }}
                                            @endif
                                        </b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{ qty($item->qty) }} {{ $item->matl_uom }}</td>
                                    <td>Rp. {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td style="text-align:right;">
                                        Rp. {{ number_format($item->price * $item->qty, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @php $exchange_total += $item->price * $item->qty; @endphp
                            @endforeach
                        </table>
                        @endif

                        {{-- Total --}}
                        <table style="margin-top:4px;">
                            <tr>
                                <td style="text-align:right; border-top:1px solid #000; padding-top:4px;">
                                    <b>Total Retur:</b>
                                </td>
                                <td style="text-align:right; border-top:1px solid #000; padding-top:4px;">
                                    <b>Rp. {{ number_format($return_total, 0, ',', '.') }}</b>
                                </td>
                            </tr>
                            @if(isset($exchange_total) && $exchange_total > 0)
                            <tr>
                                <td style="text-align:right;">
                                    <b>Total Pengganti:</b>
                                </td>
                                <td style="text-align:right;">
                                    <b>Rp. {{ number_format($exchange_total, 0, ',', '.') }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align:right; border-top:1px solid #000; padding-top:2px;">
                                    <b>
                                        @if($return_total > $exchange_total)
                                            Kembali ke Pelanggan:
                                        @elseif($exchange_total > $return_total)
                                            Tambahan dari Pelanggan:
                                        @else
                                            Selisih:
                                        @endif
                                    </b>
                                </td>
                                <td style="text-align:right; border-top:1px solid #000; padding-top:2px;">
                                    <b>Rp. {{ number_format(abs($return_total - $exchange_total), 0, ',', '.') }}</b>
                                </td>
                            </tr>
                            @endif
                        </table>

                        {{-- Footer --}}
                        <table style="width:100%; text-align:center; margin-top:6px;">
                            <tr>
                                <td style="text-align:left; padding-bottom:4px;">
                                    @if($this->object->Partner)
                                        {{ $this->object->Partner->name }}
                                        @if ($this->object->Partner->phone)
                                            - {{ $this->object->Partner->phone }}
                                        @endif
                                    @else
                                        Partner Tidak Ditemukan
                                    @endif
                                </td>
                            </tr>
                            @if($object->ExchangeOrder && $object->ExchangeOrder->OrderDtl->count() > 0)
                            <tr>
                                <td style="text-align:center; padding:2px 0; font-size:10px; border-top:1px dashed #ccc; border-bottom:1px dashed #ccc;">
                                    <i>Transaksi Retur dengan {{ $object->ExchangeOrder->OrderDtl->count() }} barang pengganti</i>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td><b>— Terima Kasih —</b></td>
                            </tr>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        function printInvoice() {
            window.print();
        }
    </script>
    <style>
    @page { size: 80mm auto; margin: 2mm; }

body {
  margin: 0;
  padding: 0;
  font-family: Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  print-color-adjust: exact;
}

#print {
  width: 80mm;
  margin: 0 auto;
  padding-top: 0;
}

.invoice-box {
  width: 100%;
  box-sizing: border-box;
  padding: 1mm 2mm 0 1mm;
  font-size: 3mm;
  line-height: 1.3;
}

.invoice-box table {
  width: 100%;
  border-collapse: collapse;
}

.invoice-box td,
.invoice-box th {
  padding: 0.5mm 0.75mm;
  vertical-align: top;
}

.logo-container {
  text-align: center;
  margin-bottom: 1mm;
}

.logo-container img {
  max-width: 60mm;
  max-height: 20mm;
  object-fit: contain;
}

.top-border {
  border-top: 1px solid #000;
  padding-top: 1mm;
}

@media print {
  body, #print, .invoice-box {
    width: 80mm !important;
    margin: 0;
    padding: 0;
    top: -50mm !important;
    left: -70mm !important;
  }
}
/* store header below the logo */
 .store-info {
    text-align: center;
    padding-bottom: 4px;
    font-size: 12px !important;  /* adjust as needed */
    line-height: 1.2;
    font-weight: bold;
  }

    </style>
</div>
