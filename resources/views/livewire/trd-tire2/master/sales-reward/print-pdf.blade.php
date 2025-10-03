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
    <div class="card d-print-none" style="max-width: 1200px; margin: 30px auto; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 0px 1.5px rgba(0,0,0,0.03); border-radius: 10px; padding: 32px 32px 40px 32px;">
        <div class="invoice-box" style="max-width: 1200px; margin: auto; padding: 20px;">
            <!-- Header -->
            <div style="text-align: center; margin: 20px 0;">
                <h3 style="margin: 0; font-weight: bold; text-decoration: underline;">
                    SALES REWARD REPORT
                </h3>
                <p style="margin: 5px 0;">
                    Program Code: {{ $this->object->code ?? '' }}
                </p>
                <p style="margin: 5px 0;">
                    Period: {{ $this->object->beg_date ?? '' }} - {{ $this->object->end_date ?? '' }}
                </p>
            </div>

            <!-- Items Table -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000; line-height: 1.2;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #000; text-align: center; padding: 8px; width: 8%;">No</th>
                        <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 20%;">KODE BARANG</th>
                        <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 30%;">NAMA PROGRAM</th>
                        <th style="border: 1px solid #000; text-align: center; width: 15%;">GROUP</th>
                        <th style="border: 1px solid #000; text-align: center; width: 12%;">QTY</th>
                        <th style="border: 1px solid #000; text-align: center; padding-right: 5px; width: 15%;">REWARD</th>
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
                        <tr style="line-height: 1.2;">
                            <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 3px 5px 3px 5px;">
                                {{ $loop->iteration }}
                            </td>
                            <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 3px 5px 3px 5px;">
                                {{ $detail->matl_code ?? '-' }}
                            </td>
                            <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 3px 5px 3px 5px;">
                                {{ $detail->descrs ?? '-' }}
                            </td>
                            <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 3px 5px 3px 5px;">
                                {{ $detail->grp ?? '-' }}
                            </td>
                            <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 3px 5px 3px 5px;">
                                {{ ceil($detail->qty) }}
                            </td>
                            <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 3px 5px 3px 5px;">
                                {{ number_format(ceil($detail->reward), 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Area print tetap tampil saat print -->
    <div id="print" class="d-none d-print-block p-20">
        <div style="max-width: 1200px; margin: 0 auto; font-family: 'Calibri'; font-size: 14px;">
            <div class="invoice-box" style="max-width: 1200px; margin: auto; padding: 20px;">
                <!-- Header -->
                <div style="text-align: center; margin: 20px 0;">
                    <h3 style="margin: 0; font-weight: bold; text-decoration: underline;">
                        SALES REWARD REPORT
                    </h3>
                    <p style="margin: 5px 0;">
                        Program Code: {{ $this->object->code ?? '' }}
                    </p>
                    <p style="margin: 5px 0;">
                        Period: {{ $this->object->beg_date ?? '' }} - {{ $this->object->end_date ?? '' }}
                    </p>
                </div>

                <!-- Items Table -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000; line-height: 1.2;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid #000; text-align: center; padding: 8px; width: 8%;">No</th>
                            <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 20%;">KODE BARANG</th>
                            <th style="border: 1px solid #000; text-align: left; padding-left: 5px; width: 30%;">NAMA PROGRAM</th>
                            <th style="border: 1px solid #000; text-align: center; width: 15%;">GROUP</th>
                            <th style="border: 1px solid #000; text-align: center; width: 12%;">QTY</th>
                            <th style="border: 1px solid #000; text-align: center; padding-right: 5px; width: 15%;">REWARD</th>
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
                            <tr style="line-height: 1.2;">
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 3px 5px 3px 5px;">
                                    {{ $loop->iteration }}
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 3px 5px 3px 5px;">
                                    {{ $detail->matl_code ?? '-' }}
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: left; padding: 3px 5px 3px 5px;">
                                    {{ $detail->descrs ?? '-' }}
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 3px 5px 3px 5px;">
                                    {{ $detail->grp ?? '-' }}
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 3px 5px 3px 5px;">
                                    {{ ceil($detail->qty) }}
                                </td>
                                <td style="border-width: 0px 1px 0px 1px; border-style: solid; border-color: #000; text-align: center; padding: 3px 5px 3px 5px;">
                                    {{ number_format(ceil($detail->reward), 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function printInvoice() {
            window.print();
        }
    </script>
</div>
