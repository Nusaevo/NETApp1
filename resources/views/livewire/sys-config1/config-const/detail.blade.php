<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card isForm="true" title="{{ $actionValue }} {!! $menuName !!}" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    {{-- <x-ui-text-field label="Const Code" model="inputs.code" type="code" :action="$actionValue" required="true" enabled="true"  visible="true" /> --}}
                    <div class="row"><x-ui-dropdown-select label="Application" clickEvent="refreshApplication"
                            model="selectedApplication" :options="$applications" required="true" :action="$actionValue"
                            visible="{{ $isSysConfig1 ? 'true' : 'false' }}" onChanged="applicationChanged"
                            :enabled="$isEnabled" />

                        <x-ui-text-field label="Const Group" model="inputs.const_group" type="text" :action="$actionValue"
                            required="true" visible="true" placeHolder="Ex : MMATL_JEWEL_COMPONENTS"/>
                        <x-ui-text-field label="Seq" model="inputs.seq" type="number" :action="$actionValue"
                            required="true" visible="true" />
                    </div>
                    <div class="row">
                        <x-ui-text-field label="Str1" model="inputs.str1" type="text" :action="$actionValue"
                            required="true" visible="true" placeHolder="Ex : RG"/>
                        <x-ui-text-field label="Str2" model="inputs.str2" type="text" :action="$actionValue"
                            required="false" visible="true" placeHolder="Ex : ROSE GOLD"/>

                        <x-ui-text-field label="Num1" model="inputs.num1" type="number" :action="$actionValue"
                            required="false" visible="true" />
                        <x-ui-text-field label="Num2" model="inputs.num2" type="number" :action="$actionValue"
                            required="false" visible="true" />
                    </div>
                    <x-ui-text-field label="Note1" model="inputs.note1" type="textarea" :action="$actionValue"
                        required="false" visible="true" />

                    {{-- Cookie Management Section for TrdTire1 OTP Groups --}}
                    @if($showCookieManagement)
                        <div class="cookie-management-section">
                            <x-ui-card title="🍪 Device Trust Cookie Management" class="mt-4 border border-info">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Informasi Cookie</h6>
                                    <p class="mb-2">Konfigurasi ini terkait dengan sistem OTP. Anda dapat mengelola device trust cookie untuk testing dan debugging.</p>
                                </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card {{ ($cookieInfo['is_trusted'] ?? false) ? 'border-success' : 'border-warning' }} bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">📊 Status Cookie</h6>
                                            <ul class="list-unstyled">
                                                <li><strong>Cookie Name:</strong>
                                                    <code class="text-muted">{{ $cookieInfo['device_trust_cookie'] ?? 'N/A' }}</code>
                                                </li>
                                                <li><strong>Status:</strong>
                                                    @if($cookieInfo['is_trusted'] ?? false)
                                                        <span class="badge bg-success fs-6">✅ Trusted</span>
                                                    @else
                                                        <span class="badge bg-warning fs-6">❌ Not Trusted</span>
                                                    @endif
                                                </li>
                                                <li><strong>Lifetime:</strong>
                                                    <span class="text-info">{{ $cookieInfo['cookie_lifetime_days'] ?? 'N/A' }} hari ({{ $cookieInfo['cookie_lifetime'] ?? 'N/A' }})</span>
                                                </li>
                                                <li><strong>Cookie Value:</strong>
                                                    <code class="small {{ ($cookieInfo['cookie_value'] ?? 'Tidak ada') === 'Tidak ada' ? 'text-muted' : 'text-success' }}">
                                                        {{ $cookieInfo['cookie_value'] ?? 'Tidak ada' }}
                                                    </code>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">⚙️ Aksi Cookie</h6>
                                            <div class="d-grid gap-2">
                                                @if($cookieInfo['is_trusted'] ?? false)
                                                    {{-- When device is trusted --}}
                                                    <div class="alert alert-success p-2 mb-2">
                                                        <small><i class="fas fa-shield-alt"></i> Device ini sudah trusted</small>
                                                    </div>
                                                    <button wire:click="clearDeviceTrust" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i> Clear Device Trust
                                                    </button>
                                                @else
                                                    {{-- When device is not trusted --}}
                                                    <div class="alert alert-warning p-2 mb-2">
                                                        <small><i class="fas fa-exclamation-triangle"></i> Device belum trusted</small>
                                                    </div>
                                                    <button wire:click="setDeviceTrust" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i> Set Device as Trusted
                                                    </button>
                                                @endif

                                                {{-- Always show refresh button --}}
                                                <button wire:click="refreshCookieInfo" class="btn btn-outline-info btn-sm">
                                                    <i class="fas fa-sync"></i> Refresh Info
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning mt-3">
                                <small>
                                    <strong>⚠️ Catatan:</strong>
                                    <ul class="mb-0">
                                        <li>Cookie "Device Trust" digunakan untuk melewati OTP pada device yang sudah terverifikasi</li>
                                        <li>Jika device trusted, user tidak perlu memasukkan OTP saat login</li>
                                        <li>Cookie ini berlaku selama {{ $cookieInfo['cookie_lifetime'] ?? '365 hari' }}</li>
                                        <li>Fitur ini hanya untuk testing/debugging - gunakan dengan hati-hati</li>
                                    </ul>
                                </small>
                            </div>
                        </x-ui-card>
                    @endif
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>

        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('show-alert', (event) => {
            const data = Array.isArray(event) ? event[0] : event;
            if (data.type === 'success') {
                toastr.success(data.message);
            } else if (data.type === 'info') {
                toastr.info(data.message);
            } else if (data.type === 'warning') {
                toastr.warning(data.message);
            } else if (data.type === 'error') {
                toastr.error(data.message);
            }
        });

        // Listen for cookie update events
        Livewire.on('cookie-updated', (event) => {
            const data = Array.isArray(event) ? event[0] : event;
            const cookieSection = document.querySelector('.cookie-management-section');

            if (cookieSection) {
                // Add visual feedback
                cookieSection.style.opacity = '0.6';
                cookieSection.style.transform = 'scale(0.98)';

                // Force refresh component after a short delay to allow cookie changes to propagate
                setTimeout(() => {
                    @this.call('forceRefresh');
                    cookieSection.style.opacity = '1';
                    cookieSection.style.transform = 'scale(1)';
                }, 400);
            }
        });

        // Listen for Livewire refresh events
        Livewire.on('$refresh', () => {
            // Add visual feedback during refresh
            const cookieSection = document.querySelector('.cookie-management-section');
            if (cookieSection) {
                cookieSection.style.opacity = '0.7';
                setTimeout(() => {
                    cookieSection.style.opacity = '1';
                }, 300);
            }
        });
    });

    // Add click feedback for buttons
    document.addEventListener('click', function(e) {
        if (e.target.matches('[wire\\:click*="Trust"]') || e.target.closest('[wire\\:click*="Trust"]')) {
            const button = e.target.matches('button') ? e.target : e.target.closest('button');
            if (button) {
                button.style.transform = 'scale(0.95)';
                button.disabled = true;

                // Re-enable button after operation
                setTimeout(() => {
                    button.style.transform = '';
                    button.disabled = false;
                }, 1000);
            }
        }
    });
</script><style>
    /* Cookie Management Animations and Styling */
    .cookie-management-section {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card.border-success {
        box-shadow: 0 0 0 0.1rem rgba(25, 135, 84, 0.25);
    }

    .card.border-warning {
        box-shadow: 0 0 0 0.1rem rgba(255, 193, 7, 0.25);
    }

    .btn:hover {
        transform: translateY(-1px);
        transition: all 0.2s ease-in-out;
    }

    .alert {
        animation: slideInRight 0.3s ease-out;
    }

    @keyframes slideInRight {
        from { transform: translateX(10px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    .badge {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    code {
        padding: 2px 6px;
        border-radius: 4px;
        background-color: #f8f9fa;
    }
</style>
