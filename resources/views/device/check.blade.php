@extends('layout.master')

@section('title', 'Device Check')

@section('content')
<div class="card">
    <div class="card-header">
        <h1 class="card-title">Device Check Tool</h1>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            This page helps you identify your device's MAC address and browser fingerprint for device authentication.
        </div>

        <div class="mb-5" id="device-info-loading">
            <div class="d-flex justify-content-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <p class="text-center mt-2">Loading device information...</p>
        </div>

        <div id="device-info" style="display: none;">
            <h3 class="mb-4">Your Device Information</h3>

            <div class="row">
                <div class="col-md-6">
                    <div class="card bg-light mb-4">
                        <div class="card-header">
                            <h4 class="card-title">MAC Address</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h5 class="text-dark">Address</h5>
                                <div class="input-group">
                                    <input type="text" id="mac-address" class="form-control" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('mac-address')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <h5 class="text-dark">Status</h5>
                                <span id="mac-status" class="badge badge-lg"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card bg-light mb-4">
                        <div class="card-header">
                            <h4 class="card-title">Browser Fingerprint</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h5 class="text-dark">Fingerprint</h5>
                                <div class="input-group">
                                    <input type="text" id="browser-fingerprint" class="form-control" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('browser-fingerprint')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <h5 class="text-dark">Status</h5>
                                <span id="fingerprint-status" class="badge badge-lg"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-light mb-4">
                <div class="card-header">
                    <h4 class="card-title">Additional Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h5 class="text-dark">Client IP Address</h5>
                                <input type="text" id="client-ip" class="form-control" readonly>
                            </div>
                            <div class="mb-3">
                                <h5 class="text-dark">Server OS</h5>
                                <input type="text" id="server-os" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h5 class="text-dark">User Agent</h5>
                                <textarea id="user-agent" class="form-control" readonly rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <h5 class="text-dark">Server Info</h5>
                                <input type="text" id="server-info" class="form-control" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title">Register This Device</h4>
                </div>
                <div class="card-body">
                    <form id="register-device-form">
                        <div class="mb-3">
                            <label for="identifier-type" class="form-label">Identifier Type</label>
                            <select id="identifier-type" class="form-select" required>
                                <option value="mac">MAC Address</option>
                                <option value="fingerprint">Browser Fingerprint</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="identifier" class="form-label">Identifier</label>
                            <input type="text" id="identifier" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks (Optional)</label>
                            <input type="text" id="remarks" class="form-control" placeholder="e.g. My Work Laptop">
                        </div>
                        <button type="submit" class="btn btn-primary">Register Device</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Currently Allowed Devices</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Identifier 1</th>
                                    <th>Identifier 2</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="allowed-devices-table">
                                <tr>
                                    <td colspan="4" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load device information
        fetch('{{ route("device.check.status") }}')
            .then(response => response.json())
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Check for error
                if (data.error) {
                    throw new Error(data.message || 'Unknown error occurred');
                }

                // Hide loading, show info
                document.getElementById('device-info-loading').style.display = 'none';
                document.getElementById('device-info').style.display = 'block';

                // Fill in device details
                document.getElementById('mac-address').value = data.macAddress;
                document.getElementById('browser-fingerprint').value = data.browserFingerprint;
                document.getElementById('client-ip').value = data.clientIp;
                document.getElementById('user-agent').value = data.userAgent;
                document.getElementById('server-os').value = data.serverOS;
                document.getElementById('server-info').value = data.serverInfo || 'Not available';

                // Set MAC status - using Bootstrap 5 badge classes
                const macStatusElement = document.getElementById('mac-status');
                if (data.macAllowed) {
                    macStatusElement.classList.add('bg-success', 'text-white');
                    macStatusElement.textContent = 'Allowed';
                } else {
                    macStatusElement.classList.add('bg-danger', 'text-white');
                    macStatusElement.textContent = 'Not Allowed';
                }

                // Set fingerprint status
                const fingerprintStatusElement = document.getElementById('fingerprint-status');
                if (data.fingerprintAllowed) {
                    fingerprintStatusElement.classList.add('bg-success', 'text-white');
                    fingerprintStatusElement.textContent = 'Allowed';
                } else {
                    fingerprintStatusElement.classList.add('bg-danger', 'text-white');
                    fingerprintStatusElement.textContent = 'Not Allowed';
                }

                // Pre-fill identifier based on selection
                const identifierTypeSelect = document.getElementById('identifier-type');
                const identifierInput = document.getElementById('identifier');

                identifierTypeSelect.addEventListener('change', function() {
                    if (this.value === 'mac') {
                        identifierInput.value = data.macAddress;
                    } else {
                        identifierInput.value = data.browserFingerprint;
                    }
                });

                // Set initial value
                identifierInput.value = data.macAddress;

                // Populate allowed devices table
                const allowedDevicesTable = document.getElementById('allowed-devices-table');
                allowedDevicesTable.innerHTML = '';

                if (data.allowedDevices && data.allowedDevices.length > 0) {
                    data.allowedDevices.forEach(device => {
                        const row = document.createElement('tr');

                        const idCell = document.createElement('td');
                        idCell.textContent = device.id;
                        row.appendChild(idCell);

                        const str1Cell = document.createElement('td');
                        str1Cell.textContent = device.str1;
                        row.appendChild(str1Cell);

                        const str2Cell = document.createElement('td');
                        str2Cell.textContent = device.str2 || '-';
                        row.appendChild(str2Cell);

                        const remarksCell = document.createElement('td');
                        remarksCell.textContent = device.remarks || '-';
                        row.appendChild(remarksCell);

                        allowedDevicesTable.appendChild(row);
                    });
                } else {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 4;
                    cell.textContent = 'No allowed devices found.';
                    cell.className = 'text-center';
                    row.appendChild(cell);
                    allowedDevicesTable.appendChild(row);
                }
            })
            .catch(error => {
                console.error('Error fetching device information:', error);
                document.getElementById('device-info-loading').innerHTML =
                    '<div class="alert alert-danger">' +
                    '<strong>Error loading device information:</strong><br>' +
                    error.message + '<br><br>' +
                    'Please try refreshing the page. If the problem persists, check server logs.' +
                    '</div>' +
                    '<button onclick="window.location.reload()" class="btn btn-primary mt-3">Refresh Page</button>';
            });

        // Handle device registration
        document.getElementById('register-device-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const identifierType = document.getElementById('identifier-type').value;
            const identifier = document.getElementById('identifier').value;
            const remarks = document.getElementById('remarks').value;

            // Create form data
            const formData = new FormData();
            formData.append('identifierType', identifierType);
            formData.append('identifier', identifier);
            formData.append('remarks', remarks);
            formData.append('_token', '{{ csrf_token() }}');

            // Submit the form
            fetch('{{ route("device.register") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Device registered successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error registering device:', error);
                alert('Failed to register device. Please try again.');
            });
        });
    });

    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        element.select();
        document.execCommand('copy');

        // Show tooltip or some indication that it was copied
        alert('Copied to clipboard!');
    }
</script>
@endsection
