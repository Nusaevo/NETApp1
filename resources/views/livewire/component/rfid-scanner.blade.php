<div id="rfidScannerComponent" data-duration="{{ $duration }}">
    <button id="scanButton" class="btn btn-primary" onclick="startWebSocketScan()">Scan</button>
</div>

<script>
    let socket;
    const wsUrl = 'ws://localhost:8081/RFID';

    function startWebSocketScan() {
        const scanButton = document.getElementById('scanButton');
        const rfidScannerComponent = document.getElementById('rfidScannerComponent');
        const duration = rfidScannerComponent.getAttribute('data-duration');

        socket = new WebSocket(wsUrl);

        scanButton.disabled = true;
        scanButton.innerText = "Scanning...";

        socket.onopen = function() {
            console.log('WebSocket connection established');
            socket.send('start_scan:' + duration);
        };

        socket.onmessage = function(event) {
            const msg = event.data;
            if (msg.startsWith('Scanned Tags:')) {
                const tags = msg.substring(14).split(', ');
                console.log('Tags scanned:', tags);
                Livewire.emit('tagScanned', tags);
            } else {
                console.log('No tag scanned');
                Livewire.emit('errorOccurred', 'No tag scanned');
            }
            socket.close();
        };

        socket.onclose = function() {
            console.log('WebSocket connection closed');
            scanButton.disabled = false;
            scanButton.innerText = "Scan";
        };

        socket.onerror = function(error) {
            console.log('WebSocket error:', error.message);
            Livewire.emit('errorOccurred', 'WebSocket error: ' + error.message);
            scanButton.disabled = false;
            scanButton.innerText = "Scan";
            socket.close();
        };

        // Timeout to close the connection if no response within the specified duration plus buffer time
        setTimeout(function() {
            if (socket.readyState === WebSocket.OPEN) {
                console.log('No tag scanned');
                Livewire.emit('errorOccurred', 'No tag scanned');
                socket.close();
            }
        }, parseInt(duration) + 800); // Adjust the timeout to slightly longer than duration
    }

    Livewire.on('tagScanned', tags => {
        console.log('Tags scanned:', tags);
    });

    Livewire.on('errorOccurred', errorMessage => {
        console.log('Error:', errorMessage);
        Livewire.emit('notify-swal', {
            type: 'error',
            message: errorMessage
        });
        const scanButton = document.getElementById('scanButton');
        scanButton.disabled = false;
        scanButton.innerText = "Scan";
    });
</script>
