
<div>
    <div class="container mb-5 mt-3">
        <div>
            <x-ui-button clickEvent="" type="Back" button-name="Back" />
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row d-flex align-items-baseline">
                    <div class="col-xl-9">
                        <p style="color: #7e8d9f; font-size: 20px;">Nota >> <strong>No: {{ $this->object->tr_id }}</strong></p>
                    </div>
                    <div class="col-xl-3 float-end">
                        <button type="button" class="btn btn-light text-capitalize border-0" data-bs-toggle="modal" data-bs-target="#printSettingsModal">
                            <i class="fas fa-print text-primary"></i> Settings
                        </button>
                        <button type="button" class="btn btn-light text-capitalize border-0" onclick="printInvoice()">
                            <i class="fas fa-print text-primary"></i> Print
                        </button>
                    </div>
                    <hr>
                </div>

                <div id="print">
                    @foreach ($object->OrderDtl->chunk(2) as $chunk)
                        <div class="invoice-box-container">
                            @foreach ($chunk as $key => $OrderDtl)
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
                                                            <div class="price"><b>Price : </b>{{ rupiah(ceil($OrderDtl->price)) }}</div><br>
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
                                                {{ rupiah($OrderDtl->price) }} <br>
                                                {{ terbilang($OrderDtl->price) }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Print Settings Modal -->
        <div class="modal fade" id="printSettingsModal"  aria-labelledby="printSettingsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="printSettingsModalLabel">Pengaturan Cetak</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="savePrintSettings">
                            @foreach ($printSettings as $setting => $value)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="printSettings.{{ $setting }}" id="{{ $setting }}">
                                <label class="form-check-label" for="{{ $setting }}">
                                    {{ ucfirst(str_replace('_', ' ', $setting)) }}
                                </label>
                            </div>
                            @endforeach
                            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                        </form>
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
    @page {
        size: A5 portrait;
        margin: 0;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Calibri';
        font-size: 14px;
        color: #555;
    }

    .invoice-box-container {
        display: flex;
        flex-direction: column;
        height: 100%;
        box-sizing: border-box;
        page-break-inside: avoid;
    }

    .invoice-box {
        width: 100%;
        height: 297px; /* Half of A5 height in pixels */
        border: 1px solid #eee;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        line-height: 24px;
        font-weight: 700;
        color: #555;
        box-sizing: border-box;
        page-break-inside: avoid;
        margin: 0;
        padding: 10px;
    }

    .invoice-box table {
        width: 100%;
        line-height: inherit;
        text-align: left;
        border-collapse: collapse;
    }

    .invoice-box table td {
        vertical-align: top;
        padding: 5px;
    }

    .invoice-box table tr td:nth-child(2) {
        text-align: right;
    }

    .invoice-box table tr.top table td.title {
        font-size: 45px;
        line-height: 45px;
        color: #333;
    }

    .invoice-box table tr.heading td {
        background: #eee;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
    }

    .invoice-box table tr.item td {
        border-bottom: 1px solid #eee;
    }

    .invoice-box table tr.item.last td {
        border-bottom: none;
    }

    .invoice-box table tr.total td:nth-child(2) {
        border-top: 2px solid #eee;
        font-weight: bold;
    }

    .invoice-box .price {
        float: right;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        #print, #print * {
            visibility: visible;
        }
        #print {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
        }
        .card {
            border: none;
            box-shadow: none;
        }
        .container {
            padding: 0;
            margin: 0;
        }
        .btn, .d-flex {
            display: none;
        }
        .invoice-box {
            border: none;
            box-shadow: none;
            margin: 0;
            padding: 10px;
            height: 297px; /* Half of A5 height in pixels */
        }
        .invoice-box-container {
            padding: 0;
            margin: 0;
            height: 100%;
        }
        .invoice-box table {
            width: 100%;
        }
        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }
        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }
        .invoice-box table tr.top table td {
            padding-bottom: 10px;
        }
        .invoice-box table tr.top table td.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }
        .invoice-box table tr.information table td {
            padding-bottom: 10px;
        }
        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .invoice-box table tr.details td {
            padding-bottom: 10px;
        }
        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }
        .invoice-box table tr.item.last td {
            border-bottom: none;
        }
        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }
    }
</style>

</div>
