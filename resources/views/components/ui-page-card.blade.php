<div>
<div class="page-card-wrapper">
    <div id="page-card" class="container-xXxl mb-5">
        <!-- Header Section -->
        <div class="page-header-card bg-white shadow-lg rounded-4 mb-4 overflow-hidden">
            <div class="header-neutral position-relative">
                <div class="d-flex justify-content-between align-items-center p-4">
                    {{-- Title --}}
                    @if (!empty($title))
                        <div class="d-flex align-items-center">

                            <div>
                                <h2 class="page-title mb-1 text-dark fw-bold">{{ $title }}</h2>
                                <p class="page-subtitle mb-0 text-muted">{{ $subtitle ?? '' }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Status --}}
                    @isset($status)
                        @if (!empty($status))
                            <div class="status-badge">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill shadow-sm border">
                                  Status : {{ $status }}
                                </span>
                            </div>
                        @endif
                    @endisset
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="page-content-card bg-white shadow-lg rounded-4 overflow-hidden">
            <div class="card-body p-0">
                {{-- Slot --}}
                @isset($slot)
                    <div class="content-area p-4">
                        {{ $slot }}
                    </div>
                @endisset
            </div>
        </div>

        <!-- Metadata Section -->
        @isset($this->object->id)
            <div class="metadata-card bg-white shadow-sm rounded-3 mt-4 border">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="info-simple">
                                <small class="text-muted d-block mb-1">Dibuat</small>
                                <div class="fw-semibold">{{ optional($this->object->created_at)->format('d M Y, H:i') ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $this->object->created_by ?? 'System' }}</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-simple">
                                <small class="text-muted d-block mb-1">Diperbarui</small>
                                <div class="fw-semibold">{{ optional($this->object->updated_at)->format('d M Y, H:i') ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $this->object->updated_by ?? 'System' }}</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-simple">
                                <small class="text-muted d-block mb-1">Versi</small>
                                <div class="fw-semibold">{{ $this->object->version_number ?? 'v1.0.0' }}</div>
                                <small class="text-muted"></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endisset
    </div>
</div>

<style>
.page-card-wrapper {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.header-neutral {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    position: relative;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.header-neutral::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.3) 50%, transparent 70%);
    pointer-events: none;
}

.page-header-card {
    border: none;
}

.page-content-card {
    border: none;
}

.title-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.page-title {
    font-size: 1.75rem;
    line-height: 1.2;
}

.page-subtitle {
    font-size: 0.95rem;
    opacity: 0.8;
}

.status-badge .badge {
    font-size: 0.875rem;
    font-weight: 500;
    backdrop-filter: blur(10px);
    background-color: rgba(255, 255, 255, 0.95) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
}

.bg-gradient-secondary {
    background: var(--secondary-gradient);
}

.metadata-card {
    /* No transitions that might interfere with modals */
}

.info-simple {
    padding: 0.75rem;
    border-radius: 0.5rem;
    background-color: #f8f9fa;
    height: 100%;
    min-height: 80px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.info-item {
    padding: 1rem;
    border-radius: 0.75rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 1px solid rgba(0, 0, 0, 0.05);
    height: 100%;
}

.info-item i {
    font-size: 1.1rem;
}

.content-area {
    min-height: 200px;
}

/* Disable animations that might conflict with modals */
.page-card-wrapper {
    /* Animation removed to prevent modal conflicts */
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-title {
        font-size: 1.5rem;
    }

    .page-subtitle {
        font-size: 0.875rem;
    }

    .title-icon {
        width: 50px;
        height: 50px;
    }

    .title-icon i {
        font-size: 1.2rem !important;
    }

    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }

    .status-badge {
        align-self: flex-start;
    }
}

@media (max-width: 576px) {
    .content-area {
        padding: 1.5rem !important;
    }
}
</style>

@if (isset($isForm) && $isForm === 'true')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let hasChanges = false;

            // window.addEventListener('beforeunload', function(e) {
            //     if (hasChanges) {
            //         e.preventDefault();
            //         e.returnValue = 'Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin meninggalkan halaman ini?';
            //     }
            // });

            Livewire.on('form-changed', function(data) {
                hasChanges = data.hasChanges;
                console.log('[Livewire] Form changed status:', hasChanges);
            });
        });
    </script>
@endif
</div>
