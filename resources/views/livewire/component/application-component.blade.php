<div class="aside-menu flex-column-fluid" style="width: 100%;">
    <select class="custom-select" wire:change="applicationChanged($event.target.value)" style="color: grey; width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #f8f9fa;">
        @foreach($applications as $application)
            <option value="{{ $application['value'] }}" @if($selectedApplication == $application['value']) selected @endif>{{ $application['label'] }}</option>
        @endforeach
    </select>
</div>
