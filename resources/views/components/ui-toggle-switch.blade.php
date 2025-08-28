{{--
    Komponen Toggle Switch

    Cara Penggunaan:
    <x-ui-toggle-switch
        model="inputs.is_active"
        onChanged="onToggleChanged"
        :action="$actionValue"
        enabled="true"
        :showLabel="true"
        label="Status Aktif"
    />

    Contoh penggunaan:
    <x-ui-toggle-switch
        model="input_details.{{ $key }}.is_lunas"
        onChanged="toggleLunas({{ $key }})"
        :action="$actionValue"
        enabled="true"
        :showLabel="false"
        :label="$input_detail['is_lunas'] ? 'Lunas' : 'Belum Lunas'"
    />

    Parameter:
    - model: Nama model untuk wire:model (wajib)
    - onChanged: Method yang dipanggil saat toggle berubah (opsional)
    - action: Action value untuk menentukan enabled/disabled state (opsional)
    - enabled: Boolean untuk enable/disable toggle (default: true)
    - showLabel: Boolean untuk menampilkan label (default: false)
    - label: Teks label yang ditampilkan (opsional)
    - value: Nilai default toggle (opsional)
--}}

@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $isEnabled = !((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'));
    $isDisabled = !$isEnabled;
    $cursorStyle = $isDisabled ? 'not-allowed' : 'pointer';
@endphp

<div class="form-check form-switch d-flex justify-content-center align-items-center">
    <input type="checkbox" class="form-check-input" role="switch" wire:model="{{ $model }}"
        wire:change="{{ $onChanged ?? '' }}" id="toggle_{{ $id }}" {{ $isDisabled ? 'disabled' : '' }}
        style="width: 3rem; height: 1.5rem; cursor: {{ $cursorStyle }};">
    @if (isset($showLabel) && $showLabel)
        <label class="form-check-label ms-2" style="font-size: 0.875rem; color: #6c757d;">
            {{ $label ?? ($value ? 'Aktif' : 'Tidak Aktif') }}
        </label>
    @endif
</div>
