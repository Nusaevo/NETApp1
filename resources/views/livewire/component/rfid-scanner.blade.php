<div wire:ignore>
    @if($action == "Edit" || $action == "Create")
        <div id="rfidScannerComponent" data-duration="{{ $duration }}" style="padding: 5px;">
            <button id="scanButton" class="btn btn-primary btn-action" onclick="startWebSocketScan()" style="width: 100px;">
                <img src="{{ imagePath('rfid.svg') }}" alt="Scan Icon" style="width: 20px; height: 20px; margin-right: 5px;"> <span>Scan</span>
            </button>
        </div>
    @endif

    <script>
        function startWebSocketScan() {
            console.log('Starting WebSocket scan'); // To verify function is called

            const scanButton = document.getElementById('scanButton');
            const rfidScannerComponent = document.getElementById('rfidScannerComponent');
            const duration = rfidScannerComponent.getAttribute('data-duration');

            let socket = new WebSocket('ws://localhost:8081/RFID');

            scanButton.disabled = true;
            scanButton.querySelector('span').innerText = "Scanning...";

            socket.onopen = function() {
                console.log('WebSocket connection established');
                socket.send('start_scan:' + duration);
            };

            socket.onmessage = function(event) {
                const msg = event.data;
                if (msg.startsWith('Scanned Tags:')) {
                    const tags = msg.substring(14).split(', ');
                    console.log('Tags scanned:', tags);
                    Livewire.dispatch('tagScanned', { tags: tags });
                } else {
                    console.log('No tag scanned');
                    Livewire.dispatch('errorOccurred', { message: 'No tag scanned' });
                }
                socket.close();
            };

            socket.onclose = function() {
                console.log('WebSocket connection closed');
                scanButton.disabled = false;
                scanButton.querySelector('span').innerText = "Scan";
            };

            socket.onerror = function(error) {
                console.log('WebSocket error:', error.message);
                Livewire.dispatch('errorOccurred', { message: 'WebSocket error: ' + error.message });
                scanButton.disabled = false;
                scanButton.querySelector('span').innerText = "Scan";
                socket.close();
            };

            setTimeout(function() {
                if (socket.readyState === WebSocket.OPEN) {
                    console.log('No tag scanned');
                    Livewire.dispatch('errorOccurred', { message: 'No tag scanned' });
                    socket.close();
                }
            }, parseInt(duration) + 800); // Adjust the timeout to slightly longer than duration
        }

        function initializeScanButton() {
            const scanButton = document.getElementById('scanButton');
            if (scanButton) {
                scanButton.addEventListener('click', startWebSocketScan);
            }
        }

        document.addEventListener('livewire:load', function() {
            console.log('Livewire loaded'); // To verify script is loaded
            initializeScanButton();

            Livewire.on('errorOccurred', errorMessage => {
                console.log('Error:', errorMessage);
                Livewire.dispatch('notify-alert', {
                    type: 'error',
                    message: errorMessage.message
                });
                const scanButton = document.getElementById('scanButton');
                scanButton.disabled = false;
                scanButton.querySelector('span').innerText = "Scan";
            });

            Livewire.hook('element.updated', (el, component) => {
                if (el.querySelector('#scanButton')) {
                    console.log('Reinitializing scan button');
                    initializeScanButton();
                }
            });
        });
    </script>

</div>

