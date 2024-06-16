<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/invoice.css') }}">

</head>
<body>

<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back"/>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="container mb-5 mt-3">
            <div class="row d-flex align-items-baseline">
                <div class="col-xl-9">
                    <p style="color: #7e8d9f; font-size: 20px;">Nota >> <strong>No: {{ $this->object->tr_id }}</strong></p>
                </div>
                <div class="col-xl-3 float-end">
                    <a class="btn btn-light text-capitalize border-0" data-mdb-ripple-color="dark" onclick="printInvoice()">
                        <i class="fas fa-print text-primary"></i> Print
                    </a>
                </div>
                <hr>
            </div>

            @foreach ($object->OrderDtl as $key => $OrderDtl)
            <div id="print">
                <div class="invoice-box">
                    <table>
                        <tr class="top">
                            <td colspan="2">
                                <table>
                                    <tr>
                                        <td class="title">
                                            {{-- <img src="{{ asset('customs/logos/TrdJewel1.png') }}" style="width: 100%; max-width: 300px;"> --}}
                                        </td>
                                        <td>
                                            <p>
                                                Nomor Nota : #<b>{{ $this->object->id }}</b><br>
                                                Tanggal : <b>{{ $this->object->tr_date }}</b><br>
                                                Customer : <b>{{ $this->object->Partner->name }}</b>
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr class="information">
                            <td>
                                @php
                                $imagePath = $OrderDtl->Material->Attachment->first() ? $OrderDtl->Material->Attachment->first()->getUrl() : 'https://via.placeholder.com/100';
                                @endphp
                                <img src="{{ $imagePath }}" alt="Material Image" style="width: 200px; height: 200px; object-fit: cover;">
                            </td>
                            <td>
                                <table>
                                    <tr>
                                        <td style="padding-bottom: 10px; font-size: 16px;">
                                           {{ $OrderDtl->matl_code }}<br>
                                            <b>{{ $OrderDtl->Material->jwl_category1 }} </b> : {{ $OrderDtl->Material->jwl_category2 }}<br>
                                            <b>Deskripsi Bahan : </b>{{ $OrderDtl->name }}<br>
                                            <b>Deskripsi Bahan : </b>{{ $OrderDtl->matl_descr }}<br>
                                            <div class="price"><b>Price : </b>{{ rupiah(ceil(currencyToNumeric($OrderDtl->price))) }}</div><br>
                                            <b>Qty : </b>1
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr class="heading">
                            <td colspan="2">Keterangan Tambahan</td>
                        </tr>
                        <tr class="item">
                            <td>
                                <ul>
                                    <li>BARANG & BERAT SUDAH DIPERIKSA PEMBELI</li>
                                    <li>BARANG TIDAK DITERIMA KEMBALI / NO RETURN</li>
                                    <li>TUKAR TAMBAH: -15% KONDISI BAIK</li>
                                    <li>JUAL: -25% KONDISI BAIK</li>
                                </ul>
                            </td>
                            <td>
                                {{ rupiah(currencyToNumeric($OrderDtl->price)) }} <br>
                                {{ terbilang(currencyToNumeric($OrderDtl->price)) }}
                            </td>
                        </tr>
                    </table>

                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
<script type="text/javascript">
    function printInvoice() {
        window.print();
    }
</script>

</body>
</html>
