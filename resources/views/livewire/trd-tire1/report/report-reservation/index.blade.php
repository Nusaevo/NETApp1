<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <div class="card mb-4">
            <div class="card-body">
                <div class="container mb-2 mt-2">
                    <div class="row align-items-start">
                        <div class="col-md-10">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <x-ui-dropdown-search label="Kode Barang" model="matl_id" optionValue="id"
                                        :query="$materialQuery" optionLabel="code,name"
                                        placeHolder="Ketik untuk cari barang..." :selectedValue="$matl_id" required="false"
                                        action="Edit" enabled="true" type="int" onChanged="onMaterialChanged" />
                                </div>
                                <div class="col-md-6">
                                    <x-ui-dropdown-search label="Customer" model="customer_id" optionValue="id"
                                        :query="$customerQuery" optionLabel="code,name,address,city"
                                        placeHolder="Ketik untuk cari customer..." :selectedValue="$customer_id" required="false"
                                        action="Edit" enabled="true" type="int" onChanged="onCustomerChanged" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column gap-2">
                                <x-ui-button clickEvent="search" button-name="View" loading="true" action="Edit"
                                    cssClass="btn-primary w-100" />
                                <button type="button" class="btn btn-light text-capitalize border-0 w-100"
                                    onclick="printReport()">
                                    <i class="fas fa-print text-primary"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="print">
            <div>
                <style>
                    @media print {
                        body {
                            background: #fff !important;
                            font-family: 'Calibri', Arial, sans-serif !important;
                            padding-top: 10px;
                        }

                        #print .card {
                            box-shadow: none !important;
                            border: none !important;
                            background: transparent !important;
                        }

                        #print .card-body {
                            padding: 0;
                            margin: 0;
                            background: transparent !important;
                        }

                        #print .container {
                            margin: 0 auto !important;
                            padding: 0;
                            max-width: none !important;
                        }

                        #print table {
                            border-collapse: collapse !important;
                            width: 100% !important;
                            margin-top: 10px !important;
                        }

                        #print table tr:first-child {
                            margin-top: 20px !important;
                        }

                        @page {
                            margin-top: 40px !important;
                            margin-bottom: 40px !important;
                        }

                        /* @page :first {
                            margin-top: 10px !important;
                        } */

                        #print th,
                        #print td {
                            padding: 4px 6px !important;
                            font-size: 15px !important;
                            border: 1px solid #000 !important;
                            vertical-align: middle !important;
                            color: #000 !important;
                        }

                        #print th {
                            background: transparent !important;
                            font-weight: bold !important;
                            text-align: left !important;
                        }

                        #print h3,
                        #print h4 {
                            margin: 0 !important;
                            font-weight: bold !important;
                            color: #000 !important;
                        }

                        #print p {
                            margin: 5px 0 !important;
                        }

                        .btn,
                        .card-header,
                        .card-footer,
                        .page-info {
                            display: none !important;
                        }

                        #print {
                            font-family: 'Calibri', Arial, sans-serif !important;
                            font-size: 16px !important;
                            color: #000 !important;
                            background: transparent !important;
                        }

                        #print * {
                            color: #000 !important;
                        }

                        #print div[style*="max-width:2480px"] {
                            padding: 20px 20px 20px 20px !important;
                            max-width: 100% !important;
                            background: transparent !important;
                        }
                    }
                </style>
                <div class="card print-page">
                    <div class="card-body">
                        <div class="container mb-3 mt-4">
                            <div style="max-width:2480px; margin:auto; padding:20px 20px 10px 20px;">
                                <div style="text-align: left; margin-bottom: 20px;">
                                    <h3 style="font-weight:bold; margin:0; text-decoration: underline;">Laporan Reservasi / Nota Belum Kirim</h3>
                                </div>


                                @php
                                    function fmtDate($d)
                                    {
                                        return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '';
                                    }
                                    function nfmt($n)
                                    {
                                        return number_format($n ?? 0, 0, ',', '.');
                                    }
                                @endphp

                                <table
                                    style="width:100%; border-collapse:collapse; font-family: 'Calibri', Arial, sans-serif; border: 1px solid #000;">
                                    <thead>
                                        <tr>
                                            <th
                                                style="text-align:left; padding:5px 8px; font-weight:bold; font-size:15px; width:40%; border:1px solid #000;">
                                                Kode/Nama Barang</th>
                                            <th
                                                style="text-align:left; padding:5px 8px; font-weight:bold; font-size:15px; width:12%; border:1px solid #000;">
                                                Tgl.Nota</th>
                                            <th
                                                style="text-align:left; padding:5px 8px; font-weight:bold; font-size:15px; width:18%; border:1px solid #000;">
                                                Nomor Nota</th>
                                            <th
                                                style="text-align:left; padding:5px 8px; font-weight:bold; font-size:15px; width:20%; border:1px solid #000;">
                                                Customer</th>
                                            <th
                                                style="text-align:right; padding:5px 8px; font-weight:bold; font-size:15px; width:10%; border:1px solid #000;">
                                                Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalQty = 0;
                                            $currentMatlCode = null;
                                            $groupQty = 0;
                                        @endphp
                                        @foreach ($results as $index => $row)
                                            @if ($currentMatlCode !== $row->matl_code)
                                                @if ($currentMatlCode !== null)
                                                    {{-- Subtotal for previous group --}}
                                                    <tr style="background-color: #f0f0f0;">
                                                        <td colspan="4" style="text-align:right; padding:4px 6px; font-size:14px; font-weight:bold; border:1px solid #000;">
                                                            <strong>Total:</strong>
                                                        </td>
                                                        <td style="text-align:right; padding:4px 6px; font-size:14px; font-weight:bold; border:1px solid #000;">
                                                            <strong>{{ nfmt($groupQty) }}</strong>
                                                        </td>
                                                    </tr>
                                                @endif
                                                @php
                                                    $currentMatlCode = $row->matl_code;
                                                    $groupQty = 0;
                                                @endphp
                                                <tr>
                                                    <td
                                                        style="text-align:left; padding:4px 6px; font-size:14px; border:1px solid #000;">
                                                        {{ $row->matl_code ?? '' }} - {{ $row->matl_name ?? '' }}</td>
                                                    <td
                                                        style="text-align:left; padding:4px 6px; font-size:14px; border:1px solid #000;">
                                                        {{ fmtDate($row->tr_date) }}</td>
                                                    <td
                                                        style="text-align:left; padding:4px 6px; font-size:14px; border:1px solid #000;">
                                                        {{ $row->tr_code ?? '' }}</td>
                                                    <td
                                                        style="text-align:left; padding:4px 6px; font-size:14px; border:1px solid #000;">
                                                        {{ $row->customer_name ?? '' }}</td>
                                                    <td
                                                        style="text-align:right; padding:4px 6px; font-size:14px; border:1px solid #000;">
                                                        {{ nfmt($row->qty) }}</td>
                                                </tr>
                                            @else
                                                <tr>
                                                    <td
                                                        style="text-align:left; padding:4px 6px; font-size:14px; border:1px solid #000;">
                                                        </td>
                                                    <td
                                                        style="text-align:left; padding:4px 6px; font-size:14px; border:1px solid #000;">
                                                        {{ fmtDate($row->tr_date) }}</td>
                                                    <td
                                                        style="text-align:left; padding:4px 6px; font-size:14px; border:1px solid #000;">
                                                        {{ $row->tr_code ?? '' }}</td>
                                                    <td
                                                        style="text-align:left; padding:4px 6px; font-size:14px; border:1px solid #000;">
                                                        {{ $row->customer_name ?? '' }}</td>
                                                    <td
                                                        style="text-align:right; padding:4px 6px; font-size:14px; border:1px solid #000;">
                                                        {{ nfmt($row->qty) }}</td>
                                                </tr>
                                            @endif
                                            @php
                                                $groupQty += $row->qty;
                                                $totalQty += $row->qty;
                                            @endphp
                                        @endforeach

                                        {{-- Subtotal for last group --}}
                                        @if ($currentMatlCode !== null)
                                            <tr style="background-color: #f0f0f0;">
                                                <td colspan="4" style="text-align:right; padding:4px 6px; font-size:14px; font-weight:bold; border:1px solid #000;">
                                                    <strong>Subtotal:</strong>
                                                </td>
                                                <td style="text-align:right; padding:4px 6px; font-size:14px; font-weight:bold; border:1px solid #000;">
                                                    <strong>{{ nfmt($groupQty) }}</strong>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-ui-page-card>

    <script>
        function printReport() {
            window.print();
        }
    </script>
</div>
