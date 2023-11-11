<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <a href="{{ route('role.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2"><i class="bi bi-arrow-left-circle fs-2 me-2"></i> Kembali </a>
    </div>

    <div style="max-width: 1000px;" id="kt_content_container" class="container-xxl mb-5">
        <div class="card shadow-sm">
            <form wire:submit.prevent='store' class="form w-100">
                <div class="card-header">
                    <h3 class="card-title">Sunting akses {{ $role->name }}</h3>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover gy-7 gs-7">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                    <th class="min-w-50px">Ijinkan</th>
                                    <th class="min-w-70px">Modul</th>
                                    <th class="min-w-200px">Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permissions as $pid => $item)
                                    <tr>
                                        <td>
                                            <div class="form-check form-check-custom">
                                                <input class="form-check-input @error("permissions.$pid.checked") is-invalid @enderror" type="checkbox" wire:model="permissions.{{ $pid }}.checked" value="1"/>
                                            </div>
                                            @error("permissions.$pid.checked") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </td>
                                        <td>{{ $item['module'] ?? '' }}</td>
                                        <td>{{ $item['desc_id'] ?? '' }}</td>
                                    </tr>
                                    @error("permissions.$pid") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                @endforeach
                                @error('permissions') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    @include('layout.customs.button-submit')
                </div>
            </form>
        </div>
    </div>

</div>
