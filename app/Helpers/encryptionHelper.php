<?php

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

if (!function_exists('encryptWithSessionKey')) {
    /**
     * Custom encryption that produces exactly 12 URL-safe characters.
     *
     * @param mixed $value The value to encrypt.
     * @return string The encrypted string (exactly 12 characters, no /).
     */
    function encryptWithSessionKey($value)
    {
        // Convert value to string to handle integers
        $value = (string) $value;

        $sessionSalt = Session::get('session_salt', 'default_salt');

        // Create a combined string with salt and value
        $combined = $sessionSalt . '|' . $value . '|' . time();

        // Generate hash and take first 16 characters (will be reduced to 12)
        $hash = hash('sha256', $combined);

        // Take first 16 hex characters and convert to base36 for shorter result
        $hexPart = substr($hash, 0, 16);
        $decimal = hexdec($hexPart);
        $base36 = base_convert($decimal, 10, 36);

        // Ensure exactly 12 characters by padding or truncating
        $result = str_pad(substr($base36, 0, 12), 12, '0', STR_PAD_LEFT);

        // Replace any potential problematic characters with safe ones
        $safeChars = str_replace(['/', '+', '='], ['x', 'y', 'z'], $result);
        $encrypted = strtoupper($safeChars);

        // Store mapping in session for decryption
        $encryptionMap = Session::get('encryption_map', []);
        $encryptionMap[$encrypted] = $value;
        Session::put('encryption_map', $encryptionMap);

        return $encrypted;
    }
}

if (!function_exists('decryptWithSessionKey')) {
    /**
     * Custom decryption using session-based mapping.
     *
     * @param string $encrypted The encrypted string.
     * @return string The decrypted value.
     */
    function decryptWithSessionKey($encrypted)
    {
        try {
            // Get the encryption mapping from session
            $encryptionMap = Session::get('encryption_map', []);

            // If we have the mapping, return the original value
            if (isset($encryptionMap[$encrypted])) {
                return $encryptionMap[$encrypted];
            }

            // If not found in current session, try to reverse engineer with time window
            $sessionSalt = Session::get('session_salt', 'default_salt');
            $currentTime = time();

            // Try with current time and recent timestamps (within 1 hour)
            for ($timeOffset = 0; $timeOffset <= 3600; $timeOffset += 60) {
                $testTime = $currentTime - $timeOffset;

                // Try common values that might have been encrypted
                $commonValues = [
                    'Edit', 'View', 'Delete', 'Create', '',
                    'cetakProsesDate', 'cetakLaporanPenjualan',
                    // Add more common values as needed
                ];

                foreach ($commonValues as $testValue) {
                    $combined = $sessionSalt . '|' . $testValue . '|' . $testTime;
                    $hash = hash('sha256', $combined);
                    $hexPart = substr($hash, 0, 16);
                    $decimal = hexdec($hexPart);
                    $base36 = base_convert($decimal, 10, 36);
                    $result = str_pad(substr($base36, 0, 12), 12, '0', STR_PAD_LEFT);
                    $safeChars = str_replace(['/', '+', '='], ['x', 'y', 'z'], $result);
                    $testEncrypted = strtoupper($safeChars);

                    if ($testEncrypted === $encrypted) {
                        // Store in session for future use
                        $encryptionMap[$encrypted] = $testValue;
                        Session::put('encryption_map', $encryptionMap);
                        return $testValue;
                    }
                }
            }

            // If we can't decrypt, throw error
            throw new Exception('Cannot decrypt value: ' . $encrypted);

        } catch (Exception $e) {
            // If decryption fails, abort with 404
            abort(404, 'Invalid encryption data: ' . $e->getMessage());
        }
    }
}

// if (!function_exists('encryptWithSessionKey')) {
//     /**
//      * Encrypts a value using a session-specific salt.
//      *
//      * @param mixed $value The value to encrypt.
//      * @return string The encrypted value.
//      */
//     function encryptWithSessionKey($value)
//     {
//         $sessionSalt = Session::get('session_salt', '');
//         // Using only session salt for the encryption process
//         $valueToEncrypt = $sessionSalt . $value;
//         return Crypt::encryptString($valueToEncrypt);
//     }
// }

// if (!function_exists('decryptWithSessionKey')) {
//     /**
//      * Decrypts a value encrypted with a session-specific salt.
//      *
//      * @param string $encryptedValue The encrypted value.
//      * @return mixed The decrypted value.
//      * @throws \Exception If the session key is invalid or the data has been tampered with.
//      */
//     function decryptWithSessionKey($encryptedValue)
//     {
//         $sessionSalt = Session::get('session_salt', '');
//         $decryptedValue = Crypt::decryptString($encryptedValue);

//         // Check if the decrypted value starts with the session salt
//         if (strpos($decryptedValue, $sessionSalt) === 0) {
//             // Remove the session salt from the decrypted value
//             return substr($decryptedValue, strlen($sessionSalt));
//         }
//         abort(404, 'Page not found');
//     }
// }
