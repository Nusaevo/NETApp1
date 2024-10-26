<!-- resources/views/password_encryption.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Encryption and Decryption</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5" style="max-width: 600px;">
        <h2 class="text-center">Password Encryption and Decryption</h2>

        <!-- Encryption Form -->
        <form id="passwordEncryptionForm">
            @csrf
            <div class="mb-3">
                <label for="password" class="form-label">Enter Password to Encrypt</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Encrypt Password</button>
        </form>

        <div id="result" class="mt-3" style="display: none;">
            <h5>Encrypted Password:</h5>
            <div class="d-flex align-items-center">
                <p id="encryptedPassword" class="alert alert-success mb-0 me-2" style="flex-grow: 1; overflow-wrap: break-word; max-height: 2.5em; overflow: hidden;"></p>
                <button id="toggleButton" class="btn btn-secondary" onclick="togglePasswordDisplay()">Show More</button>
            </div>
            <button id="copyButton" class="btn btn-secondary mt-2" onclick="copyPassword()">Copy</button>
        </div>

        <hr class="my-5">

        <!-- Decryption Form -->
        <form id="passwordDecryptionForm">
            @csrf
            <div class="mb-3">
                <label for="encryptedPasswordInput" class="form-label">Enter Encrypted Password to Decrypt</label>
                <input type="text" class="form-control" id="encryptedPasswordInput" name="encrypted_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Decrypt Password</button>
        </form>

        <div id="decryptionResult" class="mt-3" style="display: none;">
            <h5>Decrypted Password:</h5>
            <p id="decryptedPassword" class="alert alert-success"></p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js"></script>
    <script>
        // Handle Encryption
        document.getElementById('passwordEncryptionForm').addEventListener('submit', async function (event) {
            event.preventDefault();

            const password = document.getElementById('password').value;
            try {
                const response = await axios.post('/api/v1/encrypt-password', { password });

                // Display the encrypted password
                document.getElementById('result').style.display = 'block';
                document.getElementById('encryptedPassword').textContent = response.data.encrypted_password;
            } catch (error) {
                console.error('Error encrypting password:', error);
                alert('Failed to encrypt the password. Please try again.');
            }
        });

        // Toggle Full Password Display
        function togglePasswordDisplay() {
            const encryptedPasswordElement = document.getElementById('encryptedPassword');
            const toggleButton = document.getElementById('toggleButton');

            if (encryptedPasswordElement.style.maxHeight === '2.5em') {
                encryptedPasswordElement.style.maxHeight = 'none';
                toggleButton.textContent = 'Show Less';
            } else {
                encryptedPasswordElement.style.maxHeight = '2.5em';
                toggleButton.textContent = 'Show More';
            }
        }

        // Copy Password Function
        function copyPassword() {
            const encryptedPassword = document.getElementById('encryptedPassword').textContent;
            navigator.clipboard.writeText(encryptedPassword).then(() => {
                alert('Encrypted password copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy password: ', err);
            });
        }

        // Handle Decryption
        document.getElementById('passwordDecryptionForm').addEventListener('submit', async function (event) {
            event.preventDefault();

            const encryptedPassword = document.getElementById('encryptedPasswordInput').value;
            try {
                const response = await axios.post('/api/v1/decrypt-password', { encrypted_password: encryptedPassword });

                // Display the decrypted password
                document.getElementById('decryptionResult').style.display = 'block';
                document.getElementById('decryptedPassword').textContent = response.data.decrypted_password;
            } catch (error) {
                console.error('Error decrypting password:', error);
                alert('Failed to decrypt the password. Please check the input and try again.');
            }
        });
    </script>
</body>
</html>
