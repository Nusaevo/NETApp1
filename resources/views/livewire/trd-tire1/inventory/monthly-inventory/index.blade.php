<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <div class="card mb-4">
            <div class="card-body">
                <div class="container mb-2 mt-2">
                    <div class="row align-items-center justify-content-between">
                        {{-- Input Periode --}}
                        <div class="col-md-4">
                            <x-ui-text-field
                                label="Periode (YYYYMM)"
                                model="period"
                                type="text"
                                placeholder="Contoh: 202510"
                                action="Edit"
                                onChanged="updatedPeriod" />
                        </div>

                        {{-- Keterangan Periode --}}
                        <div class="col-md-6">
                            @if($periodLabel)
                                <div class="alert alert-info mb-0">
                                    <strong>Periode Aktif:</strong> {{ $periodLabel }}
                                    ({{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : '' }} - {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : '' }})
                                    <br>
                                    <strong>Periode Berikutnya:</strong> {{ $nextPeriodLabel }} ({{ $nextPeriod }})
                                </div>
                            @else
                                <div class="alert alert-warning mb-0">
                                    <small>Masukkan periode dalam format YYYYMM (contoh: 202510 untuk Oktober 2025)</small>
                                </div>
                            @endif
                        </div>

                        {{-- Button Submit --}}
                        <div class="col-md-2 d-flex justify-content-end">
                            <x-ui-button
                                clickEvent="processPeriod"
                                button-name="Proses"
                                loading="true"
                                action="Edit"
                                cssClass="btn-primary w-100"
                                :disabled="$isProcessing || empty($period)" />
                        </div>
                    </div>

                    {{-- Pesan Status --}}
                    @if($processMessage)
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert {{ $isProcessing ? 'alert-warning' : 'alert-success' }} mb-0">
                                    {{ $processMessage }}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Loading Indicator --}}
                    @if($isProcessing)
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Sedang memproses data inventory bulanan...</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-ui-page-card>
</div>
