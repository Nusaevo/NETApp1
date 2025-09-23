<style>
    @media print {
        thead {
            display: table-header-group !important;
        }
        tfoot {
            display: table-footer-group !important;
        }
        tbody {
            display: table-row-group !important;
        }
        tr {
            page-break-inside: avoid !important;
        }
        thead tr {
            page-break-after: avoid !important;
        }
        .header-title {
            margin-top: 80px !important;
            padding-top: 40px !important;
        }
        .header-title:first-child {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        @page {
            margin-top: 20mm;
            margin-bottom: 20mm;
        }
        .page-break {
            page-break-before: always;
        }
        .page-break .header-title {
            margin-top: 0 !important;
            padding-top: 30px !important;
        }
        .header-title {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        .header-title:not(:first-child) {
            margin-top: 10px !important;
            padding-top: 20px !important;
        }
    }
</style>

<div>
    <div class="row d-flex align-items-baseline">
        <div class="col-xl-9">
            <x-ui-button clickEvent="back" type="Back" button-name="Back" />
        </div>
        <div class="col-xl-3 float-end d-flex gap-2">
            <a class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark" onclick="printInvoice()">
                <i class="fas fa-print text-primary"></i> Print
            </a>
            <button type="button" wire:click="downloadExcel" class="btn btn-success text-capitalize border-0"
                data-mdb-ripple-color="dark" style="display: inline-block !important;">
                <i class="fas fa-file-excel text-white"></i> Download Excel
            </button>
        </div>
        <hr>
    </div>

    <!-- Card hanya tampil di layar, tidak saat print -->
    <div class="card d-print-none"
        style="max-width: 1200px; margin: 30px auto; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 0px 1.5px rgba(0,0,0,0.03); border-radius: 10px; padding: 32px 32px 40px 32px;">
        <div class="invoice-box" style="max-width: 1200px; margin: auto; padding: 20px;">
            <!-- Header -->
            <h3 style="margin: 0; text-decoration: underline; font-weight: bold; color: #333; font-size: 24px;">
                Proses Faktur Pajak
            </h3>
            <p style="margin: 5px 0 20px 0; color: #666; font-size: 14px;">
                Tanggal Proses: {{ \Carbon\Carbon::parse($printDate)->format('d-M-Y') }}
            </p>

            @if ($orders->isEmpty())
                <p style="text-align: center; color: #dc3545; font-size: 16px;">Tidak ada data untuk ditampilkan.</p>
            @else
                <!-- Items Table -->
                <table style="width: 100%; margin-bottom: 20px; line-height: 1.2;">
                    <thead>
                        <tr style="background-color: #f8f9fa;">
                            <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                No. Nota
                            </th>
                            <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                No. Faktur
                            </th>
                            <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                Tanggal
                            </th>
                            <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                Nama Pelanggan
                            </th>
                            <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                Nama Barang
                            </th>
                            <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                Qty
                            </th>
                            <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                Harga Pcs
                            </th>
                            <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                Amt
                            </th>
                            <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                PPN
                            </th>
                            <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                Amt + PPN
                            </th>
                            <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                Amt Nota
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php
                                // subtotal per order
                                $sumAmt = 0;
                                $sumPpn = 0;
                            @endphp
                            @foreach ($order->OrderDtl as $index => $detail)
                                @php
                                    // Hitung amount baris (fallback ke qty*price*(1-disc)) bila amt null
                                    $discPct = (float) ($detail->disc_pct ?? 0);
                                    $lineAmt = isset($detail->amt) && $detail->amt > 0
                                        ? (float) $detail->amt
                                        : (float) $detail->qty * (float) $detail->price * (1 - $discPct / 100);

                                    // Ambil DPP dan PPN langsung dari OrderDtl
                                    $dpp = (float) ($detail->amt_beforetax ?? 0);
                                    $ppn = (float) ($detail->amt_tax ?? 0);

                                    $sumAmt += $lineAmt;
                                    $sumPpn += $ppn;
                                @endphp
                                <tr style="line-height: 1.2;">
                                    @if ($index === 0)
                                        <!-- Hanya tampilkan pada seq 1 -->
                                        <td style="text-align: left; padding: 5px; vertical-align: top;"
                                            rowspan="{{ count($order->OrderDtl) }}">
                                            {{ $order->tr_code }}
                                        </td>
                                        <td style="text-align: left; padding: 5px; vertical-align: top;"
                                            rowspan="{{ count($order->OrderDtl) }}">
                                            {{ $order->tax_doc_num }}
                                        </td>
                                        <td style="text-align: left; padding: 5px; vertical-align: top;"
                                            rowspan="{{ count($order->OrderDtl) }}">
                                            {{ \Carbon\Carbon::parse($order->tr_date)->format('d-M-Y') }}
                                        </td>
                                        <td style="text-align: left; padding: 5px; vertical-align: top;"
                                            rowspan="{{ count($order->OrderDtl) }}">
                                            {{ $order->Partner?->name ?? 'N/A' }}
                                        </td>
                                    @endif
                                    <td style="text-align: left; padding: 5px;">
                                        {{ $detail->matl_descr }}
                                    </td>
                                    <td style="text-align: right; padding: 5px;">
                                        {{ $detail->qty }}
                                    </td>
                                    <td style="text-align: right; padding: 5px;">
                                        {{ number_format($detail->price_beforetax, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; padding: 5px;">
                                        {{ number_format($detail->amt, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; padding: 5px;">
                                        {{ number_format($ppn, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; padding: 5px;">
                                        {{ number_format($dpp + $ppn, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; padding: 5px;">
                                        {{ number_format($lineAmt, 0, ',', '.') }}
                                    </td>
                                    {{-- <td style="text-align: right; padding: 5px;">
                                        {{ number_format($ppn, 0, ',', '.') }}</td> --}}
                                </tr>
                            @endforeach
                            @if (count($order->OrderDtl) > 1)
                                <!-- Tampilkan subtotal jika lebih dari satu item -->
                                <tr>
                                    <td colspan="7" style="text-align: right; padding: 5px; font-weight: bold;">
                                    </td>
                                    <td style="text-align: right; padding: 5px; font-weight: bold; border-bottom: 1px solid #000;">
                                        {{ number_format($sumAmt, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; padding: 5px; font-weight: bold; border-bottom: 1px solid #000;">
                                        {{ number_format($sumPpn, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; padding: 5px; font-weight: bold; border-bottom: 3px solid #000;">
                                        {{ number_format($sumAmt + $sumPpn, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; padding: 5px; font-weight: bold;">
                                        {{ number_format($sumAmt, 0, ',', '.') }}
                                    </td>
                                    {{-- <td style="text-align: right; padding: 5px; font-weight: bold;">
                                        {{ number_format($sumPpn, 0, ',', '.') }}
                                    </td> --}}
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- Area print tetap tampil saat print -->
    <div id="print" class="d-none d-print-block p-20">
        <div style="max-width: 1200px; margin: 0 auto; font-family: 'Calibri'; font-size: 14px;">
            <div class="invoice-box" style="max-width: 1200px; margin: auto; padding: 20px;">
                @if ($orders->isEmpty())
                    <!-- Header untuk halaman kosong -->
                    <h3 style="margin: 0; text-decoration: underline; font-weight: bold; color: #333; font-size: 24px;">
                        Proses Faktur Pajak
                    </h3>
                    <p style="margin: 5px 0 20px 0; color: #666; font-size: 14px;">
                        Tanggal Proses: {{ \Carbon\Carbon::parse($printDate)->format('d-M-Y') }}
                    </p>
                    <p style="text-align: center; color: #dc3545; font-size: 16px;">Tidak ada data untuk ditampilkan.
                    </p>
                @else
                    @php
                        // Hitung berapa halaman yang dibutuhkan (misal 20 item per halaman)
                        $itemsPerPage = 20;
                        $allItems = [];
                        foreach ($orders as $order) {
                            foreach ($order->OrderDtl as $detail) {
                                $allItems[] = [
                                    'order' => $order,
                                    'detail' => $detail,
                                    'index' => 0
                                ];
                            }
                        }
                        $chunks = collect($allItems)->chunk($itemsPerPage);
                    @endphp
                    @foreach ($chunks as $chunkIndex => $chunk)
                        <!-- Header per halaman -->
                        <div class="header-title {{ $chunkIndex > 0 ? 'page-break' : '' }}" style="margin-bottom: 20px;">
                            <h3 style="margin: 0; text-decoration: underline; font-weight: bold; color: #333; font-size: 24px;">
                                Proses Faktur Pajak
                            </h3>
                            <p style="margin: 5px 0 20px 0; color: #666; font-size: 14px;">
                                Tanggal Proses: {{ \Carbon\Carbon::parse($printDate)->format('d-M-Y') }}
                            </p>
                        </div>

                        <!-- Items Table -->
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; line-height: 1.2;">
                            <thead>
                                <tr style="background-color: #f8f9fa;">
                                    <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                        No. Nota</th>
                                    <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                        No. Faktur</th>
                                    <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                        Tanggal</th>
                                    <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                        Nama Pelanggan</th>
                                    <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                        Nama Barang</th>
                                    <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                        Qty</th>
                                    <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                        Harga Pcs</th>
                                    <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                        Amt</th>
                                    <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                        PPN</th>
                                    <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                        Amt + PPN</th>
                                    <th style="text-align: center; padding: 8px 5px; font-weight: bold; background-color: #e9ecef;">
                                        Amt Nota</th>

                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $currentOrderId = null;
                                    $sumAmt = 0;
                                    $sumPpn = 0;
                                    $orderDetailCount = 0;
                                @endphp
                                @foreach ($chunk as $item)
                                    @php
                                        $order = $item['order'];
                                        $detail = $item['detail'];
                                        $index = $item['index'];

                                        // Hitung amount baris (fallback ke qty*price*(1-disc)) bila amt null
                                        $discPct = (float) ($detail->disc_pct ?? 0);
                                        $lineAmt = isset($detail->amt) && $detail->amt > 0
                                            ? (float) $detail->amt
                                            : (float) $detail->qty * (float) $detail->price * (1 - $discPct / 100);

                                        // Ambil DPP dan PPN langsung dari OrderDtl
                                        $dpp = (float) ($detail->amt_beforetax ?? 0);
                                        $ppn = (float) ($detail->amt_tax ?? 0);

                                        // Reset subtotal jika order berbeda
                                        if ($currentOrderId !== $order->id) {
                                            if ($currentOrderId !== null) {
                                                // Tampilkan subtotal order sebelumnya
                                                @endphp
                                                <tr>
                                                    <td colspan="7" style="text-align: right; padding: 5px; font-weight: bold;"></td>
                                                    <td style="text-align: right; padding: 5px; font-weight: bold; border-bottom: 1px solid #000;">
                                                        {{ number_format($sumAmt, 0, ',', '.') }}
                                                    </td>
                                                    <td style="text-align: right; padding: 5px; font-weight: bold; border-bottom: 1px solid #000;">
                                                        {{ number_format($sumPpn, 0, ',', '.') }}
                                                    </td>
                                                    <td style="text-align: right; padding: 5px; font-weight: bold; border-bottom: 1px solid #000;">
                                                        {{ number_format($sumAmt + $sumPpn, 0, ',', '.') }}
                                                    </td>
                                                    <td style="text-align: right; padding: 5px; font-weight: bold;">
                                                        {{ number_format($sumAmt, 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                                @php
                                            }
                                            $currentOrderId = $order->id;
                                            $sumAmt = 0;
                                            $sumPpn = 0;
                                            $orderDetailCount = 0;
                                        }

                                        $sumAmt += $lineAmt;
                                        $sumPpn += $ppn;
                                        $orderDetailCount++;
                                    @endphp
                                    <tr style="line-height: 1.2;">
                                        @if ($index === 0)
                                            <td style="text-align: left; padding: 5px; vertical-align: top;">
                                                {{ $order->tr_code }}
                                            </td>
                                            <td style="text-align: left; padding: 5px; vertical-align: top;">
                                                {{ $order->tax_doc_num }}
                                            </td>
                                            <td style="text-align: left; padding: 5px; vertical-align: top;">
                                                {{ \Carbon\Carbon::parse($order->tr_date)->format('d-M-Y') }}
                                            </td>
                                            <td style="text-align: left; padding: 5px; vertical-align: top;">
                                                {{ $order->Partner?->name ?? 'N/A' }}
                                            </td>
                                        @else
                                            <td colspan="4" style="padding: 5px;"></td>
                                        @endif
                                        <td style="text-align: left; padding: 5px;">
                                            {{ $detail->matl_descr }}
                                        </td>
                                        <td style="text-align: right; padding: 5px;">
                                            {{ $detail->qty }}
                                        </td>
                                        <td style="text-align: right; padding: 5px;">
                                            {{ number_format($detail->price, 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; padding: 5px;">
                                            {{ number_format($dpp, 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; padding: 5px;">
                                            {{ number_format($ppn, 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; padding: 5px;">
                                            {{ number_format($dpp + $ppn, 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; padding: 5px;">
                                            {{ number_format($lineAmt, 0, ',', '.') }}
                                        </td>
                                        {{-- <td style="text-align: right; padding: 5px;">
                                            {{ number_format($ppn, 0, ',', '.') }}
                                        </td> --}}
                                    </tr>
                                @endforeach
                                @if ($currentOrderId !== null)
                                    <!-- Tampilkan subtotal order terakhir -->
                                    <tr>
                                        <td colspan="7" style="text-align: right; padding: 5px; font-weight: bold;"></td>
                                        <td style="text-align: right; padding: 5px; font-weight: bold; border-bottom: 1px solid #000;">
                                            {{ number_format($sumAmt, 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; padding: 5px; font-weight: bold; border-bottom: 1px solid #000;">
                                            {{ number_format($sumAmt + $sumPpn, 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; padding: 5px; font-weight: bold;">
                                            {{ number_format($sumAmt, 0, ',', '.') }}
                                        </td>
                                        {{-- <td style="text-align: right; padding: 5px; font-weight: bold;">
                                            {{ number_format($sumPpn, 0, ',', '.') }}
                                        </td> --}}
                                    </tr>
                                @endif
                            </tbody>
                        </table>

                        <!-- Footer per halaman -->
                        <div style="margin-top: 20px; display: flex; justify-content: space-between; font-size: 12px; color: #666;">
                            <div>{{ date('d/m/Y') }}</div>
                            <div>Page {{ $chunkIndex + 1 }} of {{ $chunks->count() }}</div>
                        </div>

                        @if ($chunkIndex < $chunks->count() - 1)
                            <div style="page-break-after: always;"></div>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <script>
        function printInvoice() {
            window.print();
        }
    </script>
</div>
