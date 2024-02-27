<?php

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

if (!function_exists('encryptWithSessionKey')) {
    /**
     * Encrypts a value using a session-specific salt.
     *
     * @param mixed $value The value to encrypt.
     * @return string The encrypted value.
     */
    function encryptWithSessionKey($value)
    {
        $sessionSalt = Session::get('session_salt', '');
        // Using only session salt for the encryption process
        $valueToEncrypt = $sessionSalt . $value;
        return Crypt::encryptString($valueToEncrypt);
    }
}

if (!function_exists('decryptWithSessionKey')) {
    /**
     * Decrypts a value encrypted with a session-specific salt.
     *
     * @param string $encryptedValue The encrypted value.
     * @return mixed The decrypted value.
     * @throws \Exception If the session key is invalid or the data has been tampered with.
     */
    function decryptWithSessionKey($encryptedValue)
    {
        $sessionSalt = Session::get('session_salt', '');
        $decryptedValue = Crypt::decryptString($encryptedValue);

        // Check if the decrypted value starts with the session salt
        if (strpos($decryptedValue, $sessionSalt) === 0) {
            // Remove the session salt from the decrypted value
            return substr($decryptedValue, strlen($sessionSalt));
        }
        abort(404, 'Page not found');
    }
}
