<div>
    @if($errorMessage)
        <div class="alert alert-danger">
            {{ $errorMessage }}
        </div>
    @endif

    <div x-data="{ isScanning: @entangle('isScanning').defer }" x-init="$watch('isScanning', value => {
        if (value) {
            $wire.startScan();
        }
    })">
        <button x-show="!isScanning" @click="isScanning = true" class="btn btn-secondary">Start Scan</button>
        <button x-show="isScanning" class="btn btn-danger" disabled>Scanning...</button>

        <div>
            <p x-show="isScanning">Scanning in progress...</p>
            <p x-show="!isScanning && $wire.scannedTags.length == 0">Scanning stopped. No tags found.</p>
            <p x-show="!isScanning && $wire.scannedTags.length > 0">Scanning stopped. Tags found:</p>
        </div>

        <ul>
            @foreach($scannedTags as $tag)
                <li>{{ $tag }}</li>
            @endforeach
        </ul>
    </div>
</div>
