@php
$id = str_replace(['.', '[', ']'], '_', $model);
@endphp

<div class="col-sm mb-5" @if(isset($span)) span="{{ $span }}" @endif @if(isset($visible) && $visible === 'false') style="display: none;" @endif>
    <div class="d-flex align-items-center">
        <div class="form-floating flex-grow-1">
            @if(isset($type) && $type === 'textarea')
            <textarea style="min-height: 150px;" wire:model="{{ $model }}" id="{{ $id }}" rows="{{ isset($rows) ? $rows : '10' }}" class="form-control form-control-lg @error($model) is-invalid @enderror"
                      @if (!(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) && ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))) disabled @endif
                      @if(isset($required) && $required === 'true') required @endif
                      placeholder="{{ isset($label) ? $label : '' }}"
                      @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif autocomplete="off"></textarea>
            @elseif(isset($type) && $type === 'document')
            <input wire:model="{{ $model }}" id="{{ $id }}" type="file" class="form-control @error($model) is-invalid @enderror"
                   @if (!(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) && ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))) disabled @endif
                   @if(isset($required) && $required === 'true') required @endif accept=".pdf, .doc, .docx"
                   @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif />
            @elseif(isset($type) && $type === 'barcode')
            <input x-data="{ initBarcode() { /* Barcode Init Code */ } }" x-init="initBarcode()" wire:model="{{ $model }}" id="{{ $id }}" type="text" class="form-control @error($model) is-invalid @enderror"
                   @if (!(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) && ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))) disabled @endif
                   @if(isset($required) && $required === 'true') required @endif
                   placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off"
                   @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif x-ref="inputField">
            @elseif(isset($type) && $type === 'code')
            <input wire:model="{{ $model }}" type="text" class="form-control @error($model) is-invalid @enderror"
                   @if (!(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) && ((isset($action) && ($action === 'Edit' || $action === 'View')) || (isset($enabled) && $enabled === 'false'))) disabled @endif
                   @if(isset($required) && $required === 'true') required @endif
                   placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off"
                   @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif />
            @elseif(isset($type) && $type === 'date')
            <input x-data="{ initDatepicker() { /* Datepicker Init Code */ } }" x-init="initDatepicker()" wire:model="{{ $model }}" id="{{ $id }}" type="text" class="form-control @error($model) is-invalid @enderror"
                   @if (!(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) && ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))) disabled @endif
                   @if(isset($required) && $required === 'true') required @endif
                   readonly="readonly" x-ref="inputField"
                   @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif />
            @elseif(isset($type) && $type === 'number')
            <input x-data="{ initInputMask() { /* Input Mask Init Code */ } }" x-init="initInputMask()" wire:model="{{ $model }}" id="{{ $id }}" type="text" class="form-control number-mask @error($model) is-invalid @enderror"
                   @if (!(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) && ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))) disabled @endif
                   @if(isset($required) && $required === 'true') required @endif
                   placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off"
                   @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif x-ref="inputField">
            @elseif(isset($type) && $type === 'image')
            <input wire:model="{{ $model }}" id="{{ $id }}" type="file" class="form-control @error($model) is-invalid @enderror" accept="image/*"
                   @if (!(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) && ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))) disabled @endif
                   @if(isset($required) && $required === 'true') required @endif
                   @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif />
            @else
            <input wire:model="{{ $model }}" type="{{ isset($type) ? $type : 'text' }}" class="form-control @error($model) is-invalid @enderror"
                   @if (!(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) && ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))) disabled @endif
                   @if(isset($required) && $required === 'true') required @endif
                   placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off"
                   @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif />
            @endif

            @if (!empty($label))
                <label for="{{ $id }}" class="@if(isset($required) && $required === 'true') required @endif">{{ $label }}</label>
            @endif
            @if(!empty($placeHolder))
                <div class="placeholder-text">{{ $placeHolder }}</div>
            @endif
            @error($model)
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <!-- Refresh Button -->
        @if (isset($clickEvent) && $clickEvent !== '')
        <div class="d-flex align-items-center ms-2">
            <span wire:loading.remove wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
                <button type="button" class="btn btn-secondary" wire:click="{{ $clickEvent }}"
                        @if (!(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) && ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))) disabled @endif>
                    {{ $buttonName }}
                </button>
            </span>
            <span wire:loading wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
                <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
            </span>
        </div>
        @endif
    </div>
</div>
