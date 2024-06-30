
    <link rel="stylesheet" href="{{ asset('customs/css/invoice.css') }}">

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
                    <button type="button" class="btn btn-light text-capitalize border-0"  onclick="printInvoice()">
                        <i class="fas fa-print text-primary"></i> Print
                    </button>
                </div>
                <hr>
            </div>

            <div id="print">
            @foreach ($object->OrderDtl as $key => $OrderDtl)
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
            @endforeach
        </div>
        </div>
    </div>

    <!-- Print Settings Modal -->
    <div class="modal fade" id="printSettingsModal" tabindex="-1" aria-labelledby="printSettingsModalLabel" aria-hidden="true">
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

@livewireScripts
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript">
    function printInvoice() {
        window.print();
    }
</script>
