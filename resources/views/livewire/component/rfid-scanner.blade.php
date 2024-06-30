<x-ui-button id="startScanButton" jsClick="startWebSocketScan()" clickEvent="" button-name="Scan" loading="false" action="Edit" cssClass="btn-primary" iconPath="" />
<x-ui-button id="scanningButton" jsClick="" clickEvent="" button-name="Scanning..." loading="true" action="Edit" cssClass="btn-primary" iconPath="" visible="false" />

<script>
    let socket;
    const wsUrl = 'ws://localhost:8081/RFID';

    function startWebSocketScan() {
        socket = new WebSocket(wsUrl);

        document.getElementById('startScanButton').setAttribute('loading', 'true');
        toggleButtons();

        socket.onopen = function() {
            console.log('WebSocket connection established');
            socket.send('start_scan');
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
            document.getElementById('startScanButton').setAttribute('loading', 'false');
            toggleButtons();
        };

        socket.onerror = function(error) {
            console.log('WebSocket error:', error.message);
            Livewire.emit('errorOccurred', 'WebSocket error: ' + error.message);
            socket.close();
        };

        setTimeout(function() {
            if (socket.readyState === WebSocket.OPEN) {
                console.log('No tag scanned');
                Livewire.emit('errorOccurred', 'No tag scanned');
                socket.close();
            }
        }, 5000); // 5 seconds timeout
    }

    function toggleButtons() {
        const startButton = document.getElementById('startScanButton');
        const scanningButton = document.getElementById('scanningButton');

        if (startButton.style.display === 'none') {
            startButton.style.display = 'block';
            scanningButton.style.display = 'none';
        } else {
            startButton.style.display = 'none';
            scanningButton.style.display = 'block';
        }
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
        document.getElementById('startScanButton').setAttribute('loading', 'false');
        toggleButtons();
    });
</script>
