<div>
    @php
        use App\Models\Base\Attachment;
    @endphp

    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        {{-- BAGIAN FILTER --}}
        <x-ui-expandable-card id="ReportFilterCard" title="Filter" :isOpen="true">
            <div class="card-body">
                <div class="col-md-12">
                    {{-- Filter Merk, Jenis, Customer, Qty Range --}}
                    <div class="row">
                        <div class="col-md-6">
                            <x-ui-text-field-search label="Merk" model="merk" :selectedValue="$merk" :options="$merkOptions"
                                action="Edit" />
                        </div>
                        <div class="col-md-6">
                            <x-ui-text-field-search label="Jenis" model="jenis" :selectedValue="$jenis" :options="$jenisOptions"
                                action="Edit" />
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <x-ui-text-field-search type="int" label="Customer" model="customer" :selectedValue="$customer"
                                :options="$customerOptions" action="Edit" />
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <x-ui-text-field type="number" label="Qty Awal" model="startQty" action="Edit" />
                        </div>
                        <div class="col-md-6">
                            <x-ui-text-field type="number" label="Qty Akhir" model="endQty" action="Edit" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end">
                {{-- Tombol Reset & Search --}}
                <x-ui-button clickEvent="resetFilters" button-name="Reset" loading="true" action="Edit"
                    cssClass="btn-secondary" />
                <x-ui-button clickEvent="search" button-name="Search" loading="true" action="Edit"
                    cssClass="btn-primary" />
                {{-- Tombol Print (opsional) --}}
                <x-ui-button :action="$actionValue" clickEvent="" jsClick="printReport()" cssClass="btn-primary"
                    loading="true" button-name="Print" iconPath="print.svg" />
            </div>
        </x-ui-expandable-card>

        {{-- BAGIAN TABEL LAPORAN STOK --}}
        <div id="print">
            <x-ui-table id="LaporanStock">
                <x-slot name="headers">
                    <th>No</th>
                    <th>Kategori</th>
                    <th>Nama Barang</th>
                    <th>Warna</th>
                    <th>Foto</th>
                    <th>Kode Barang</th>
                    <th>UOM</th>
                    <th>Stok</th>
                    <th>Harga</th>
                    <th>Modal</th>
                </x-slot>

                <x-slot name="rows">
                    @foreach ($results as $res)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            {{-- Kategori --}}
                            <td>{{ $res->category }}</td>

                            {{-- Nama Barang (Brand + Type) --}}
                            <td>{{ $res->brand }} {{ $res->type_code }}</td>

                            {{-- Warna (color_code - color_name) --}}
                            <td>{{ $res->color_code }} - {{ $res->color_name }}</td>

                            {{-- Foto --}}
                            <td>
                                @php
                                    $attachment = Attachment::where('attached_objecttype', 'Material')
                                        ->where('attached_objectid', $res->material_id)
                                        ->first();
                                @endphp
                                @if ($attachment)
                                    <x-ui-image src="{{ $attachment->getUrl() }}" alt="Photo" width="50px"
                                        height="50px" />
                                @else
                                    <span>No Image</span>
                                @endif
                            </td>

                            {{-- Kode Barang --}}
                            <td>{{ $res->material_code }}</td>

                            {{-- UOM --}}
                            <td>{{ $res->matl_uom }}</td>

                            {{-- Stok --}}
                            <td style="text-align: right;">
                                {{ number_format($res->qty ?? 0, 0) }}
                            </td>

                            {{-- Harga (selling_price) --}}
                            <td style="text-align: right;">
                                {{ rupiah($res->price ?? 0) }}
                            </td>

                            {{-- Modal (buying_price) --}}
                            <td style="text-align: right;">
                                {{ rupiah($res->cost ?? 0) }}
                            </td>
                        </tr>
                    @endforeach
                </x-slot>
            </x-ui-table>
        </div>
        <script type="text/javascript">
            function printReport() {
                window.print();
            }
        </script>

        <style>
            @page {
                /* size: 210mm 140mm;*/
                /* Ukuran khusus 210 x 140 mm */
                /* margin: 0 10mm;*/
                /* Margin kanan dan kiri */
                margin: 0;
            }


            body {
                margin: 0;
                padding: 0;
                font-family: 'Calibri';
                font-size: 14px;
                color: #555;
            }

            .container {
                padding: 0;
                margin: 0;
            }

            .card {
                border: none;
                box-shadow: none;
            }

            #print {
                width: 100%;
                height: 100%;
                box-sizing: border-box;
            }

            .invoice-box-container {
                display: flex;
                flex-direction: column;
                height: auto;
                /* Ubah untuk menyesuaikan konten */
                box-sizing: border-box;
                page-break-inside: avoid;
                margin-left: 5px;
            }

            .invoice-box {
                width: 100%;
                height: auto;
                /* Sesuaikan tinggi dengan konten */
                border: 1px solid #eee;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
                line-height: 20px;
                /* Kurangi jarak baris untuk menghemat ruang */
                font-weight: 400;
                /* Kurangi ketebalan font untuk menghemat ruang */
                color: #555;
                box-sizing: border-box;
                page-break-inside: avoid;
                margin: 0;
                padding: 5mm 10mm;
                /* Padding atas, bawah, kiri, dan kanan */
            }

            .invoice-box table {
                width: 100%;
                line-height: inherit;
                text-align: left;
                border-collapse: collapse;
            }

            .invoice-box table td {
                vertical-align: top;
                padding: 1mm;
                /* Kurangi padding untuk menghemat ruang */
            }

            .information td {
                border-top: 3px solid #ddd;
                border-bottom: 3px solid #ddd;
            }

            @media print {
                body * {
                    visibility: hidden;
                }

                #print,
                #print * {
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

                .btn,
                .d-flex {
                    display: none;
                }

                .invoice-box {
                    border: none;
                    box-shadow: none;
                    margin: 0;
                    padding: 5mm 10mm;
                    height: auto;
                    page-break-after: always;
                }
            }
        </style>
    </x-ui-page-card>

</div>
