<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\SysConfig1\ConfigService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\SysConfig1\ConfigAppl;
use App\Models\SysConfig1\ConfigUser;
use App\Models\SysConfig1\ConfigConst;
class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'code.required' => 'UserName is required',
            'password.required' => 'Password is required',
        ];
    }
    /**
     * Attempt to authenticate the request's credentials.
     *
     * DEVICE CHECKS ARE DISABLED FOR GENERAL AUTHENTICATION
     * Device verification is only used during OTP process, not for login.
     *
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    /**
     * Get MAC address from IP address
     *
     * @param string $ip
     * @return string|null
     */
    private function getMacAddressFromIp($ip)
    {
        // This is a simplified implementation for demonstration
        // In reality, server-side MAC address detection is limited and may not be reliable

        // First attempt: Use getmac command on Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                // Try getmac command first (Windows specific)
                $output = [];
                exec('getmac /v /fo csv', $output);
               //Log::info("GETMAC output: " . json_encode($output));

                // Parse the CSV output from getmac
                foreach ($output as $i => $line) {
                    // Skip header line
                    if ($i === 0) continue;

                    // Parse CSV line
                    $parts = str_getcsv($line);
                    if (count($parts) >= 3) {
                        // Check if this connection is associated with our IP
                        if (strpos($parts[2], $ip) !== false) {
                            // Format is typically "AA-BB-CC-DD-EE-FF"
                            $mac = $parts[0];
                            return strtolower(str_replace('-', ':', $mac));
                        }
                    }
                }

                // Try ARP command
                $output = [];
                exec('arp -a ' . $ip, $output);
               //Log::info("ARP output for IP {$ip}: " . json_encode($output));

                foreach ($output as $line) {
                    // Windows format: "  192.168.1.1           00-09-0f-aa-00-01     dynamic"
                    if (strpos($line, $ip) !== false) {
                        $parts = preg_split('/\s+/', trim($line));
                        // MAC address is typically the second non-empty part
                        foreach ($parts as $part) {
                            if (preg_match('/([0-9a-fA-F]{2}-){5}([0-9a-fA-F]{2})/', $part)) {
                                return strtolower(str_replace('-', ':', $part));
                            }
                        }
                    }
                }

                // Try parsing raw ARP output with alternative formats
                foreach ($output as $line) {
                    if (strpos($line, $ip) !== false) {
                        // Try to extract any MAC-like pattern
                        if (preg_match('/([0-9a-fA-F]{2}[:-]){5}([0-9a-fA-F]{2})/', $line, $matches)) {
                            return strtolower(str_replace('-', ':', $matches[0]));
                        }
                    }
                }                // Try direct ipconfig command to get physical address - this is the most reliable
                $output = [];
                exec('ipconfig /all', $output);
               //Log::info("IPCONFIG output: " . json_encode($output));

                $physical_address = "";
                $current_adapter = "";
                $ip_found = false;

                // First pass: Find the adapter with our IP and get its MAC
                foreach ($output as $line) {
                    $line = trim($line);

                    // Capture adapter name
                    if (strpos($line, "adapter") !== false && strpos($line, ":") !== false) {
                        $current_adapter = trim($line);
                        $ip_found = false;
                    }

                    // Check if this adapter has our IP
                    if (strpos($line, "IPv4 Address") !== false && strpos($line, $ip) !== false) {
                        $ip_found = true;
                       //Log::info("Found matching IP adapter: " . $current_adapter);
                    }

                    // If we found the right adapter, get its MAC
                    if ($ip_found && strpos($line, "Physical Address") !== false) {
                        $parts = explode(":", $line, 2); // Split at first colon only
                        if (count($parts) > 1) {
                            $physical_address = trim($parts[1]);
                            // Remove any spaces in the MAC address
                            $physical_address = str_replace(' ', '', $physical_address);
                           //Log::info("Extracted physical address: " . $physical_address);

                            // Format correctly: DC-45-46-64-B1-35 -> dc:45:46:64:b1:35
                            return strtolower(str_replace('-', ':', $physical_address));
                        }
                    }
                }

                // If we didn't find it with the first pass, try a simpler approach
                if (empty($physical_address)) {
                    foreach ($output as $line) {
                        $line = trim($line);
                        if (strpos($line, "Physical Address") !== false) {
                            $parts = explode(":", $line, 2); // Split at first colon only
                            if (count($parts) > 1) {
                                $physical_address = trim($parts[1]);
                                // Remove any spaces in the MAC address
                                $physical_address = str_replace(' ', '', $physical_address);
                               //Log::info("Extracted any physical address: " . $physical_address);

                                // Format correctly: DC-45-46-64-B1-35 -> dc:45:46:64:b1:35
                                return strtolower(str_replace('-', ':', $physical_address));
                            }
                        }
                    }
                }

                // Last resort: Try to generate a device-specific fingerprint
                $server_signature = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $server_signature .= $_SERVER['SERVER_ADDR'] ?? '';
                $server_signature .= $_SERVER['REMOTE_ADDR'] ?? '';

                // Create a consistent hash that looks like a MAC address
                $hash = md5($server_signature);
                $mac_format = substr($hash, 0, 2) . ':' .
                              substr($hash, 2, 2) . ':' .
                              substr($hash, 4, 2) . ':' .
                              substr($hash, 6, 2) . ':' .
                              substr($hash, 8, 2) . ':' .
                              substr($hash, 10, 2);

               //Log::info("Generated device fingerprint as MAC: {$mac_format}");
                return $mac_format;

            } catch (\Exception $e) {
                Log::error("Error getting MAC address: " . $e->getMessage());
            }
        }
        // On Linux server, try to use ARP
        else {
            try {
                $output = [];
                // Try the ip neighbor command first (more modern)
                exec('ip neighbor show ' . $ip, $output);

                if (empty($output)) {
                    // Fall back to arp command
                    exec('arp -a ' . $ip, $output);
                }

               //Log::info("Linux ARP output: " . json_encode($output));

                foreach ($output as $line) {
                    if (strpos($line, $ip) !== false) {
                        if (preg_match('/([0-9a-fA-F]{2}[:-]){5}([0-9a-fA-F]{2})/', $line, $matches)) {
                            return strtolower(str_replace('-', ':', $matches[0]));
                        }
                    }
                }

                // Try ifconfig as a last resort
                exec('ifconfig -a', $output);
                foreach ($output as $i => $line) {
                    if (strpos($line, "inet " . $ip) !== false && $i > 0) {
                        // Look for HWaddr or ether in previous lines
                        $prevLine = $output[$i-1];
                        if (preg_match('/([0-9a-fA-F]{2}[:-]){5}([0-9a-fA-F]{2})/', $prevLine, $matches)) {
                            return strtolower(str_replace('-', ':', $matches[0]));
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error getting MAC address on Linux: " . $e->getMessage());
            }
        }

        // Log failure
        Log::warning("Could not determine MAC address for IP: {$ip}, using device fingerprint instead");

        // Generate a consistent device fingerprint as fallback
        $device_info = request()->header('User-Agent') . $ip;
        $hash = md5($device_info);
        $mac_format = substr($hash, 0, 2) . ':' .
                      substr($hash, 2, 2) . ':' .
                      substr($hash, 4, 2) . ':' .
                      substr($hash, 6, 2) . ':' .
                      substr($hash, 8, 2) . ':' .
                      substr($hash, 10, 2);

       //Log::info("Generated device fingerprint as MAC: {$mac_format}");
        return $mac_format;
    }

    /**
     * Create a consistent browser fingerprint from various request attributes
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    private function createBrowserFingerprint($request)
    {
        // Collect data points that are likely to be consistent for the same device
        $data = [
            'user_agent' => $request->header('User-Agent', ''),
            'accept_language' => $request->header('Accept-Language', ''),
            'accept_encoding' => $request->header('Accept-Encoding', ''),
            'ip' => $request->ip(),
            // Add any other stable identifiers you can find
        ];

        // Create a hash from these values
        $fingerprint = md5(json_encode($data));

        // Format like a MAC address for consistency
        $formatted = substr($fingerprint, 0, 2) . ':' .
                    substr($fingerprint, 2, 2) . ':' .
                    substr($fingerprint, 4, 2) . ':' .
                    substr($fingerprint, 6, 2) . ':' .
                    substr($fingerprint, 8, 2) . ':' .
                    substr($fingerprint, 10, 2);
        return strtolower($formatted);
    }

    /**
     * Check if the device is in the allowed list from database
     *
     * @param string|null $identifier Device identifier (MAC address or fingerprint)
     * @return bool
     */
    private function isDeviceAllowed($identifier)
    {
        if (!$identifier) {
            Log::warning("Device identifier could not be determined");
            return false;
        }

        // Log the identifier we're checking
       //Log::info("Checking if device identifier is allowed: {$identifier}");

        // Check if identifier is in the ALLOW_DEVICE constant group
        $allowedDevices = ConfigConst::where('const_group', 'ALLOW_DEVICE')
            ->where(function ($query) use ($identifier) {
                $query->where('str1', $identifier)
                    ->orWhere('str2', $identifier)
                    // Also check for case-insensitive matches
                    ->orWhereRaw('LOWER(str1) = ?', [strtolower($identifier)])
                    ->orWhereRaw('LOWER(str2) = ?', [strtolower($identifier)]);
            })
            ->exists();

        if (!$allowedDevices) {
            Log::warning("Unauthorized device attempted login: {$identifier}");
            return false;
        }

       //Log::info("Device authorized successfully: {$identifier}");
        return true;
    }

    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'code' => request()->input('code'),
            'password' => request()->input('password')
        ];

        $remember = request()->has('remember');

        if (! Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'code' => trans('auth.failed'),
            ]);
        }

        // Device fingerprint and MAC address checks are disabled
        // Uncomment below to re-enable device security checks
        /*
        $clientIp = request()->ip();
        $macAddress = $this->getMacAddressFromIp($clientIp);
        $browserFingerprint = $this->createBrowserFingerprint(request());

        if (!$this->isDeviceAllowed($macAddress) && !$this->isDeviceAllowed($browserFingerprint)) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            throw ValidationException::withMessages([
                'code' => 'Device not authorized. Please contact your administrator.',
            ]);
        }
        */

        $salt = Str::random(40);
        $appKey = config('app.key');
        Session::put('session_salt', $salt . $appKey);

        $configService = new ConfigService();
        $appIds = $configService->getAppIds();
        if (!empty($appIds)) {
            $firstAppId = $appIds[0];
            $firstApp = ConfigAppl::find($firstAppId);
            if ($firstApp) {
                Session::put('app_id', $firstApp->id);
                Session::put('app_code', $firstApp->code);
                Session::put('database', $firstApp->db_name);


                $user = ConfigUser::find(Auth::id());

                if ($user) {
                    $groupCodes = $user->getGroupCodesBySessionAppCode();
                    Session::put('group_codes', $groupCodes);
                }
            }
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited()
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'code' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @return string
     */
    public function throttleKey()
    {
        return Str::transliterate(Str::lower(request()->input('code', '')).'|'.request()->ip());
    }
}
