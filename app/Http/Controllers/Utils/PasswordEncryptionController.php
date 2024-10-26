<?php

namespace App\Http\Controllers\Utils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use App\Http\Controllers\Controller;

class PasswordEncryptionController extends Controller
{
    /**
     * Display the password encryption form.
     */
    public function showEncryptionForm()
    {
        return view('utils.password_encryption');
    }

    /**
     * Encrypt a given password and return the encrypted value as JSON.
     */
    public function encryptPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $encryptedPassword = Crypt::encrypt($request->password);

        return response()->json(['encrypted_password' => $encryptedPassword]);
    }

    /**
     * Decrypt a given encrypted password and return the decrypted value as JSON.
     */
    public function decryptPassword(Request $request)
    {
        $request->validate([
            'encrypted_password' => 'required|string'
        ]);

        try {
            $decryptedPassword = Crypt::decrypt($request->encrypted_password);
            return response()->json(['decrypted_password' => $decryptedPassword]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid encrypted password.'], 400);
        }
    }
}
