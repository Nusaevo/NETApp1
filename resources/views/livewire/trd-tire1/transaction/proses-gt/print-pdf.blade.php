<div>
    <!-- Tombol Back -->
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <!-- Include CSS Invoice -->
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <div class="card">
        <div class="card-body">
            <div class="container mb-5 mt-3">
                <!-- Header Report -->
                <div class="row d-flex align-items-baseline">
                    <div class="col-xl-9">
                        <p style="color: #7e8d9f; font-size: 20px;">
                            PROSES NOTA GAJAH TUNGGAL GT RADIAL
                        </p>
                    </div>
                    <div class="col-xl-3 float-end">
                        <a class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark"
                            onclick="printInvoice()">
                            <i class="fas fa-print text-primary"></i> Print
                        </a>
                    </div>
                    <hr>
                </div>

                <!-- Content -->
                <div id="print">
                    <div class="invoice-box page" style="max-width: 2480px; margin: auto; padding: 20px;">
                        <h3 class="text-left" style="text-decoration: underline;">Proses Nota Gajah Tunggal GT RADIAL
                            per Customer</h3>
                        <p class="text-left">Tanggal Proses:
                            {{ \Carbon\Carbon::parse($selectedProcessDate)->format('d-M-Y') }}</p>

                        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                            <thead>
                                <tr style="border-bottom:1px solid #000;">
                                    <th>
                                        <h6>Nama Pelanggan</h1>
                                    </th>
                                    <th>
                                        <h6>No. Nota</h1>
                                    </th>
                                    <th>
                                        <h6>Kode Brg.</h1>
                                    </th>
                                    <th>
                                        <h6>Nama Barang</h1>
                                    </th>
                                    <th>
                                        <h6>T. Ban</h1>
                                    </th>
                                    <th>
                                        <h6>Point</h1>
                                    </th>
                                    <th>
                                        <h6>T. Point</h1>
                                    </th>
                                    <th>
                                        <h6>Nota GT</h1>
                                    </th>
                                    <th>
                                        <h6>Customer Point</h1>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($orders) && count($orders) > 0)
                                    @php
                                        $previousTrhdId = null; // Variabel untuk menyimpan trhd_id sebelumnya
                                    @endphp
                                    @foreach ($orders as $order)
                                        @foreach ($order->OrderDtl as $detail)
                                            <tr
                                                @if ($loop->last && !$loop->parent->last) style="border-bottom: 1px solid #000;" @endif>
                                                <td style="padding: 8px;">
                                                    {{ $order->Partner->name ?? 'N/A' }}
                                                </td>
                                                <td style="padding: 8px;">
                                                    {{ $order->tr_code ?? '-' }}
                                                </td>
                                                <td style="padding: 8px;">
                                                    {{ $detail->matl_code }}
                                                </td>
                                                <td style="padding: 8px;">
                                                    {{ $detail->matl_descr }}
                                                </td>
                                                <td style="padding: 8px; text-align: center;">
                                                    {{ ceil($detail->qty) }}
                                                </td>
                                                <td style="padding: 8px; text-align: center;">
                                                    {{ $detail->SalesReward->reward ?? 0 }}
                                                </td>
                                                <td style="padding: 8px; text-align: center;">
                                                    {{ round(($detail->qty / ($detail->SalesReward->qty ?? 1)) * ($detail->SalesReward->reward ?? 0), 2) }}
                                                </td>
                                                <td style="padding: 8px;">
                                                    {{ $detail->gt_tr_code ?? '-' }}
                                                </td>
                                                <td style="padding: 8px;">
                                                    {{ $order->Partner->city ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            @php
                                                $previousTrhdId = $order->id; // Perbarui trhd_id setelah setiap iterasi
                                            @endphp
                                        @endforeach
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="9"
                                            style="text-align: center; border: 1px solid #000; padding: 8px;">
                                            Tidak ada data untuk ditampilkan.
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

    <!-- Script untuk Print -->
    <script>
        function printInvoice() {
            window.print();
        }
    </script>
</div>
