<div>
    <!-- Tombol Back -->
    <div>
        <x-ui-button clickEvent="back" type="Back" button-name="Back" />
    </div>

    <!-- Include CSS Invoice -->
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

    <body>
        <div class="card">
            <div class="card-body">
                <div class="container mb-5 mt-3">
                    <!-- Header Report -->
                    <div class="row d-flex align-items-baseline">
                        <div class="col-xl-9">
                            <p style="color: #7e8d9f; font-size: 20px;">
                                SALES REWARD REPORT >>
                                <strong>No: {{ $this->object->code ?? '' }}</strong>
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

                    <!-- Area yang akan di-print -->
                    <div id="print">
                        <div class="invoice-box"
                            style="max-width: 800px; margin: auto; padding: 20px; border: 1px solid #eee;">

                            <!-- Judul Report -->
                            <div style="text-align: center; margin: 20px 0;">
                                <h3 style="margin: 0; font-weight: bold; text-decoration: underline;">
                                    SALES REWARD REPORT
                                </h3>
                                <p style="margin: 5px 0;">
                                    Program Code: {{ $this->object->code ?? '' }}
                                </p>
                                <p style="margin: 5px 0;">
                                    Period: {{ $this->object->beg_date ?? '' }}
                                    -
                                    {{ $this->object->end_date ?? '' }} </p>
                            </div>

                            <!-- Tabel Detail Item -->
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                <thead>
                                    <tr>
                                        <th style="border: 1px solid #000; padding: 8px; text-align: center;">No</th>
                                        <th style="border: 1px solid #000; padding: 8px;">KODE BARANG</th>
                                        <th style="border: 1px solid #000; padding: 8px;">NAMA BARANG</th>
                                        <th style="border: 1px solid #000; padding: 8px; text-align: center;">GROUP</th>
                                        <th style="border: 1px solid #000; padding: 8px; text-align: center;">QTY</th>
                                        <th style="border: 1px solid #000; padding: 8px; text-align: right;">REWARD</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $grand_total = 0;
                                    @endphp
                                    @foreach ($this->returnIds as $key => $id)
                                        @php
                                            $detail = \App\Models\TrdTire1\Master\SalesReward::find($id);
                                            $subTotal = $detail->qty * $detail->reward;
                                            $grand_total += $subTotal;
                                        @endphp
                                        <tr>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: center;">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: left;">
                                                {{ $detail->matl_code ?? '-' }}
                                            </td>
                                            <td style="border: 1px solid #000; padding: 8px;">
                                                {{ $detail->descrs ?? '-' }}
                                            </td>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: center;">
                                                {{ $detail->grp ?? '-' }}
                                            </td>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: center;">
                                                {{ ceil($detail->qty) }}
                                            </td>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: right;">
                                                {{ number_format(ceil($detail->reward), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>

    <!-- Script untuk Print -->
    <script>
        function printInvoice() {
            window.print();
        }
    </script>
</div>
