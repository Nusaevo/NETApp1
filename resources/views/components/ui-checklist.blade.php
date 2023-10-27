<div class="mb-10" @if (!$visible) style="display: none;" @endif>
    <label>{{ $label }}</label>
    <table style="margin-top: 10px; margin-bottom: 10px;">
        <tbody>
            @foreach ($options as $key => $optionLabel)
                <tr>
                    <td style="padding-right: 10px;">
                        <input type="checkbox" wire:model="{{ $name }}.{{ $key }}"
                            style="width: 20px; height: 20px;"
                            @if (!$enabled) disabled @endif />
                    </td>
                    <td>
                        {{ $optionLabel }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
