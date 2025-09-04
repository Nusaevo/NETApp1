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
                            <x-ui-text-field type="date" label="Tanggal Awal" model="startDate" action="Edit" />
                        </div>
                        <div class="col-md-6">
                            <x-ui-text-field type="date" label="Tanggal Akhir" model="endDate" action="Edit" />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <x-ui-dropdown-select label="Jenis Nota" model="transactionType"
                                :options="[
                                    ['label' => 'Semua', 'value' => ''],
                                    ['label' => 'Penjualan', 'value' => 'SO'],
                                    ['label' => 'Retur', 'value' => 'SR'],
                                    ['label' => 'Barang Pengganti', 'value' => 'SOR']
                                ]" action="Edit" />
                        </div>
                        <div class="col-md-6">
                            <x-ui-text-field type="text" label="Nomor Nota" model="transactionNumber" action="Edit" />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <x-ui-dropdown-search
                                label="Customer"
                                model="customer"
                                query="SELECT id, code, name FROM partners WHERE deleted_at IS NULL AND grp='C'"
                                optionValue="id"
                                optionLabel="{code},{name}"
                                placeHolder="Type to search customers..."
                                :selectedValue="$customer ?? null"
                                required="false"
                                action="Edit"
                                type="int" />
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

        <!-- Material Selection Dialog -->
        <livewire:trd-retail1.component.material-selection
            :dialogId="'reportMaterial'"
            :title="'Pilih Barang'"
            :multiSelect="true"
            :enableFilters="true"
            :width="'modal-xl'"
            :height="'600px'" />

        <div id="print" style="width: 100%; height: 100%; box-sizing: border-box;">
            <!-- Print Header - Only visible during print -->
            <div class="print-header" style="display: none;">
                <h2 style="text-align: center; margin: 10px 0 5px 0; font-size: 16px; font-weight: bold;">
                    LAPORAN PENJUALAN
                </h2>
                <h3 style="text-align: center; margin: 0 0 15px 0; font-size: 12px; font-weight: normal;">
                    @if($transactionType == 'SO')
                        Jenis Nota: Penjualan
                    @elseif($transactionType == 'SR')
                        Jenis Nota: Retur
                    @elseif($transactionType == 'SOR')
                        Jenis Nota: Barang Pengganti
                    @else
                        Jenis Nota: Semua
                    @endif
                    @if($startDate && $endDate)
                        | Periode: {{ date('d/m/Y', strtotime($startDate)) }} - {{ date('d/m/Y', strtotime($endDate)) }}
                    @endif
                </h3>
            </div>

            <x-ui-table id="LaporanPenerimaan">
                <x-slot name="headers">
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>No. Nota</th>
                    <th>Jenis Nota</th>
                    <th>Customer</th>
                    <th>Kode Barang</th>
                    <th>Total Qty</th>
                    <th>Total Amount</th>
                    <th class="no-print">Action</th>
                </x-slot>

                <x-slot name="rows">
                    @foreach ($results as $res)
                        @php
                            $rowKey = $res->tr_id . '_' . $res->tr_type;
                            $isExpanded = in_array($rowKey, $expandedRows);
                        @endphp

                        <!-- Main header row -->
                        <tr style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f1f1f1'" onmouseout="this.style.backgroundColor=''">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $res->tr_date }}</td>
                            <td>{{ $res->tr_id }}</td>
                            <td>
                                <span class="badge
                                    @if($res->tr_type == 'SO') bg-success
                                    @elseif($res->tr_type == 'SR') bg-danger
                                    @elseif($res->tr_type == 'SOR') bg-warning text-dark
                                    @else bg-secondary
                                    @endif">
                                    @if($res->tr_type == 'SO') Penjualan
                                    @elseif($res->tr_type == 'SR') Retur
                                    @elseif($res->tr_type == 'SOR') Pengganti
                                    @else {{ $res->tr_type }}
                                    @endif
                                </span>
                            </td>
                            <td>{{ $res->customer_name ?? '-' }}</td>
                            <td>{{ $res->material_codes }}</td>
                            <td style="text-align: right;">{{ number_format($res->total_qty, 0) }}</td>
                            <td style="text-align: right;">{{ rupiah($res->total_amount) }}</td>
                            <td style="text-align: center;" class="no-print">
                                <button type="button" class="btn btn-sm btn-outline-primary no-print"
                                        wire:click="toggleRowDetails('{{ $res->tr_id }}', '{{ $res->tr_type }}')">
                                    <i class="fas fa-{{ $isExpanded ? 'minus' : 'plus' }}"></i>
                                    {{ $isExpanded ? 'Hide' : 'Show' }} Details
                                </button>
                            </td>
                        </tr>

                        <!-- Detail rows (shown when expanded) -->
                        @if($isExpanded && isset($rowDetails[$rowKey]))
                            @foreach($rowDetails[$rowKey] as $detail)
                                <tr class="no-print" style="background-color: #f8f9fa; border-left: 4px solid #007bff;">
                                    <td colspan="2" ></td>
                                    <td style="padding-left: 2rem;">
                                        <small class="text-muted">{{ $detail->material_code }}</small>
                                    </td>
                                    <td><small>{{ $detail->material_name }}</small></td>
                                    <td><small>{{ $detail->category }}</small></td>
                                    <td><small>{{ $detail->brand }} {{ $detail->type_code }}</small></td>
                                    <td style="text-align: right;"><small>{{ number_format($detail->qty, 0) }}</small></td>
                                    <td style="text-align: right;"><small>{{ rupiah($detail->total) }}</small></td>
                                    <td></td>
                                </tr>
                            @endforeach
                        @endif
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
                font-size: 6px !important;
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

            /* Ultra-compact column widths for A4 portrait */
            th:nth-child(1), td:nth-child(1) { width: 4% !important; } /* No */
            th:nth-child(2), td:nth-child(2) { width: 11% !important; } /* Tanggal */
            th:nth-child(3), td:nth-child(3) { width: 8% !important; } /* No. Nota */
            th:nth-child(4), td:nth-child(4) { width: 12% !important; } /* Jenis Nota */
            th:nth-child(5), td:nth-child(5) { width: 15% !important; } /* Customer */
            th:nth-child(6), td:nth-child(6) { width: 28% !important; } /* Kode Barang */
            th:nth-child(7), td:nth-child(7) { width: 10% !important; } /* Total Qty */
            th:nth-child(8), td:nth-child(8) { width: 12% !important; } /* Total Amount */

            /* Badge styles for print - make them smaller */
            .badge {
                font-size: 5px !important;
                padding: 0px 2px !important;
                border-radius: 2px !important;
                color: white !important;
            }

            .badge.bg-success {
                background-color: #198754 !important;
            }

            .badge.bg-danger {
                background-color: #dc3545 !important;
            }

            .badge.bg-warning {
                background-color: #ffc107 !important;
                color: #000 !important;
            }

            /* Text alignment */
            td:nth-child(1) { text-align: center !important; }
            td:nth-child(2) { text-align: center !important; }
            td:nth-child(3) { text-align: center !important; }
            td:nth-child(4) { text-align: center !important; }
            td:nth-child(7), td:nth-child(8) { text-align: right !important; }

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
