<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        {{-- Migration Button --}}
        <div class="card mb-4">
            <div class="card-body text-center">
                <x-ui-button clickEvent="migrateOrderToInventory" button-name="Create Data from Order" loading="true"
                    action="Edit" cssClass="btn-success btn-lg px-5" />
                <x-ui-button clickEvent="migrateToInventory" button-name="Create Data From Deliv" loading="true"
                    action="Edit" cssClass="btn-success btn-lg px-5" />
                <x-ui-button clickEvent="migrateToBilling" button-name="Create Billing" loading="true" action="Edit"
                    cssClass="btn-success btn-lg px-5" />
                <x-ui-button clickEvent="migratePaymentToPartner" button-name="Create Data from Payment" loading="true"
                    action="Edit" cssClass="btn-success btn-lg px-5" />
                <x-ui-button clickEvent="migrateIvttrToInventory" button-name="Create Data from Ivttr" loading="true"
                    action="Edit" cssClass="btn-success btn-lg px-5" />
                {{-- <p class="mt-3 text-muted">
                        <i class="fas fa-info-circle"></i>
                        Klik tombol di atas untuk memproses data <strong>Purchase Delivery (PD)</strong> ke inventory
                        (ivtLogs & ivtBal)
                    </p> --}}
            </div>
            <div class="card-body">
                <x-ui-button clickEvent="migrateInventoryAdjustment" button-name="Create Data from IA" loading="true"
                    action="Edit" cssClass="btn-success btn-lg px-5" />
                <x-ui-button clickEvent="migrateSalesOrder" button-name="Create Data from SO 'baru'" loading="true"
                    action="Edit" cssClass="btn-success btn-lg px-5" />
                <x-ui-button clickEvent="migrateSalesBillingSDbaru" button-name="Create Data from SD 'baru'" loading="true"
                    action="Edit" cssClass="btn-success btn-lg px-5" />
            </div>
            <div class="card-body text-center">
                <x-ui-button clickEvent="migrateAll" button-name="Migrate All" loading="true" action="Edit"
                    cssClass="btn-primary btn-lg px-5" />

            </div>
        </div>
        {{-- End Migration Button --}}


        {{-- Hasil Migration --}}
        @if (isset($migrationResult))
            <div class="card mb-4">
                <div class="card-body">
                    <div class="alert alert-success">
                        <h5 class="alert-heading">
                            <i class="fas fa-check-circle"></i> Hasil Migration
                        </h5>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Item Berhasil:</strong> {{ $migrationResult['migrated_count'] }} item diproses
                            </div>
                            <div class="col-md-4">
                                <strong>Total Diproses:</strong> {{ $migrationResult['total_items'] }} item
                            </div>
                            @if ($migrationResult['updated_details'] > 0)
                                <div class="col-md-4">
                                    <strong>Item Diupdate:</strong> {{ $migrationResult['updated_details'] }} qty
                                    ditambahkan
                                </div>
                            @endif
                        </div>


                        {{-- Detail Item yang Diupdate --}}
                        @if (!empty($migrationResult['updated_details_list']))
                            <div class="mt-3">
                                <h6 class="text-info">
                                    <i class="fas fa-plus-circle"></i> Detail Item yang Diupdate (Qty Ditambahkan):
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-info">
                                            <tr>
                                                <th>No. Nota</th>
                                                <th>Tanggal</th>
                                                <th>Material Code</th>
                                                <th>Qty</th>
                                                <th>Alasan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($migrationResult['updated_details_list'] as $skipped)
                                                <tr>
                                                    <td>{{ $skipped['tr_code'] }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($skipped['tr_date'])->format('d-M-Y') }}
                                                    </td>
                                                    <td>{{ $skipped['matl_code'] }}</td>
                                                    <td>{{ number_format($skipped['qty'], 0) }}</td>
                                                    <td>{{ $skipped['reason'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Hasil Migration Umum --}}
        @if (session('migration_success'))
            <div class="card mb-4">
                <div class="card-body">
                    <div class="alert alert-success">
                        <h5 class="alert-heading">
                            <i class="fas fa-check-circle"></i> {{ session('migration_success') }}
                        </h5>
                        <p class="mb-0">
                            <i class="fas fa-clock"></i>
                            Waktu: {{ now()->format('d-m-Y H:i:s') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if (session('migration_error'))
            <div class="card mb-4">
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-triangle"></i> {{ session('migration_error') }}
                        </h5>
                        <p class="mb-0">
                            <i class="fas fa-clock"></i>
                            Waktu: {{ now()->format('d-m-Y H:i:s') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if (session('migration_info'))
            <div class="card mb-4">
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5 class="alert-heading">
                            <i class="fas fa-info-circle"></i> {{ session('migration_info') }}
                        </h5>
                        <p class="mb-0">
                            <i class="fas fa-clock"></i>
                            Waktu: {{ now()->format('d-m-Y H:i:s') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </x-ui-page-card>
</div>
