<div>
    @php
        use App\Models\Base\Attachment;
    @endphp

    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <x-ui-expandable-card id="ReportFilterCard" title="Filter" :isOpen="true">
            <div class="card-body">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-6">
                            <x-ui-text-field type="number" label="Qty Awal" model="startQty" action="Edit" />
                        </div>
                        <div class="col-md-6">
                            <x-ui-text-field type="number" label="Qty Akhir" model="endQty" action="Edit" />
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <x-ui-text-field type="text" label="Kode Material" model="filterCode" action="Edit" />
                        </div>
                        <div class="col-md-6">
                            <x-ui-text-field type="text" label="Nama Material" model="filterName" action="Edit" />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <x-ui-dropdown-search
                                label="Category"
                                model="filterCategory"
                                query="SELECT str1, str2 FROM config_consts WHERE const_group='MMATL_CATEGL1' AND deleted_at IS NULL"
                                optionValue="str1"
                                optionLabel="{str2}"
                                placeHolder="Select category..."
                                :selectedValue="$filterCategory ?? null"
                                required="false"
                                action="Edit"
                                type="string" />
                        </div>
                        <div class="col-md-4">
                              <x-ui-dropdown-search
                                label="Brand"
                                model="filterBrand"
                                query="SELECT DISTINCT brand FROM materials WHERE brand IS NOT NULL AND brand != '' AND deleted_at IS NULL"
                                optionValue="brand"
                                optionLabel="{brand}"
                                placeHolder="Select brand..."
                                :selectedValue="$filterBrand ?? null"
                                required="false"
                                action="Edit"
                                type="string" />
                        </div>
                        <div class="col-md-4">
                            <x-ui-dropdown-search
                                label="Type"
                                model="filterType"
                                query="SELECT DISTINCT class_code FROM materials WHERE class_code IS NOT NULL AND class_code != '' AND deleted_at IS NULL"
                                optionValue="class_code"
                                optionLabel="{class_code}"
                                placeHolder="Select type..."
                                :selectedValue="$filterType ?? null"
                                required="false"
                                action="Edit"
                                type="string" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end">
                <x-ui-button clickEvent="resetFilters" button-name="Reset" loading="true" action="Edit"
                    cssClass="btn-secondary" />
                <x-ui-button clickEvent="search" button-name="Search" loading="true" action="Edit"
                    cssClass="btn-primary" />
                <x-ui-button :action="$actionValue" clickEvent="" jsClick="printReport()" cssClass="btn-primary"
                    loading="true" button-name="Print" iconPath="print.svg" />
            </div>
        </x-ui-expandable-card>

        <!-- Hint: Limit 100 rows, suggest filter by code/name -->
        <div style="font-size: 12px; color: #555; margin-bottom: 10px;">
            *Menampilkan maksimal 100 data. Gunakan filter Kode atau Nama material untuk hasil yang lebih spesifik.
        </div>

        <div id="print" style="width: 100%; height: 100%; box-sizing: border-box;">
            <!-- Print Header - Only visible during print -->
            <div class="print-header" style="display: none;">
                <h2 style="text-align: center; margin: 10px 0 5px 0; font-size: 16px; font-weight: bold;">
                    LAPORAN STOK BARANG
                </h2>
                <h3 style="text-align: center; margin: 0 0 15px 0; font-size: 12px; font-weight: normal;">
                    @if($filterCategory || $filterBrand || $filterType)
                        Filter:
                        @if($filterCategory) Kategori: {{ $filterCategory }} @endif
                        @if($filterBrand) {{ $filterCategory ? '| ' : '' }}Brand: {{ $filterBrand }} @endif
                        @if($filterType) {{ ($filterCategory || $filterBrand) ? '| ' : '' }}Type: {{ $filterType }} @endif
                    @else
                        Semua Kategori
                    @endif
                    @if($startQty !== null && $endQty !== null)
                        | Qty: {{ number_format($startQty) }} - {{ number_format($endQty) }}
                    @endif
                </h3>
            </div>

            <x-ui-table id="LaporanStock">
                <x-slot name="headers">
                    <th>No</th>
                    <th>Kategori</th>
                    <th>Nama Barang</th>
                    <th>Warna</th>
                    <th>Foto</th>
                    <th>Kode Barang</th>
                    <th>UOM</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Modal</th>
                </x-slot>

                <x-slot name="rows">
                    @foreach ($results as $res)
                        @php
                            $rowKey = $res->material_id;
                            $isExpanded = in_array($rowKey, $expandedRows);
                        @endphp

                        <!-- Main header row -->
                        <tr style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f1f1f1'" onmouseout="this.style.backgroundColor=''">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $res->category }}</td>
                            <td>{{ $res->brand }} {{ $res->type_code }}</td>
                            <td>{{ $res->color_code }} - {{ $res->color_name }}</td>
                            <td>
                                @php
                                    $attachment = Attachment::where('attached_objecttype', 'Material')
                                        ->where('attached_objectid', $res->material_id)
                                        ->first();
                                @endphp
                                @if ($attachment)
                                    <x-ui-image src="{{ $attachment->getUrl() }}" alt="Photo" width="30px" height="30px" />
                                @else
                                    <span style="font-size: 10px;">No Image</span>
                                @endif
                            </td>
                            <td>{{ $res->material_code }}</td>
                            <td><small class="text-muted">{{ $res->matl_uom }}</small></td>
                            <td style="text-align: right;"><small>{{ number_format($res->qty ?? 0, 0) }}</small></td>
                            <td style="text-align: right;"><small>{{ rupiah($res->price ?? 0) }}</small></td>
                            <td style="text-align: right;"><small>{{ rupiah($res->cost ?? 0) }}</small></td>
                        </tr>

                    @endforeach
                </x-slot>
            </x-ui-table>
        </div>
        <script type="text/javascript">
            function printReport() {
                // Simple direct print - no complex operations to prevent loading stuck
                setTimeout(() => {
                    window.print();
                }, 100);
            }
        </script>

        <style>
        /* Reset default margins and paddings */
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        /* Normal screen styles */
        .print-header {
            display: none;
        }

        /* Normal table styles for screen */
        table {
            font-size: 14px;
            line-height: 1.4;
        }

        th, td {
            padding: 8px 12px;
            font-size: 14px;
            line-height: 1.4;
        }

        .badge {
            font-size: 11px;
            padding: 4px 8px;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                font-family: Arial, sans-serif !important;
                font-size: 10px !important;
            }

            .no-print {
                display: none !important;
            }

            .print-header {
                display: block !important;
                margin-bottom: 10px !important;
            }

            /* Make table super compact for print */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 6px !important;
                line-height: 1.1 !important;
            }

            th, td {
                border: 0.5px solid #999 !important;
                padding: 0.5px 1px !important;
                font-size: 6px !important;
                line-height: 1.1 !important;
                vertical-align: top !important;
                word-wrap: break-word !important;
                overflow: hidden !important;
            }

            th {
                background-color: #f0f0f0 !important;
                font-weight: bold !important;
                text-align: center !important;
            }

            /* Ultra-compact column widths for A4 portrait - Stock Report */
            th:nth-child(1), td:nth-child(1) { width: 4% !important; } /* No */
            th:nth-child(2), td:nth-child(2) { width: 10% !important; } /* Kategori */
            th:nth-child(3), td:nth-child(3) { width: 18% !important; } /* Nama Barang */
            th:nth-child(4), td:nth-child(4) { width: 12% !important; } /* Warna */
            /* Hide Foto column in print */
            th:nth-child(5), td:nth-child(5) { display: none !important; }
            th:nth-child(6), td:nth-child(6) { width: 12% !important; } /* Kode Barang */
            th:nth-child(7), td:nth-child(7) { width: 10% !important; } /* UOMs */
            th:nth-child(8), td:nth-child(8) { width: 8% !important; } /* Total Stok */
            th:nth-child(9), td:nth-child(9) { width: 9% !important; } /* Avg Harga */
            th:nth-child(10), td:nth-child(10) { width: 9% !important; } /* Avg Modal */

            /* Text alignment */
            td:nth-child(1) { text-align: center !important; }
            td:nth-child(5) { text-align: center !important; } /* Foto */
            td:nth-child(8), td:nth-child(9), td:nth-child(10) { text-align: right !important; }

            /* Hide images in print to save space */
            img {
                display: none !important;
            }

            /* Remove all margins and paddings from containers */
            .container, .card, .card-body {
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }

            #print {
                padding: 0 !important;
                margin: 0 !important;
            }
        }
        </style>
    </x-ui-page-card>

</div>
