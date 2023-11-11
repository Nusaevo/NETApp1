<div class="mb-10" @if(isset($visible) && !$visible) style="display: none;" @endif>
    @isset($label)
        <label>{{ $label }}</label>
    @endisset
    <table style="margin-top: 10px; margin-bottom: 10px;">
        <tbody>
            @isset($options)
                @foreach ($options as $key => $optionLabel)
                    <tr>
                        <td style="padding-right: 10px;">
                            <input type="checkbox" wire:model="{{ isset($name) ? $name.'.' : '' }}{{ $key }}"
                                style="width: 20px; height: 20px;"
                                @if (isset($enabled) && !$enabled) disabled @endif />
                        </td>
                        <td>
                            {{ $optionLabel }}
                        </td>
                    </tr>
                @endforeach
            @endisset
        </tbody>
    </table>
    @isset($name)
        @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
    @endisset
</div>
