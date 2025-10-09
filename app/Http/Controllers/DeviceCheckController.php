<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SysConfig1\ConfigConst;

/**
 * Device Check Controller
 *
 * This controller is used ONLY for OTP-related device verification.
 * General authentication does NOT require device checks.
 *
 * Purpose:
 * - Provides device information for OTP verification
 * - Allows registration of trusted devices for OTP bypass
 * - Device checks are isolated to OTP process only
 */
class DeviceCheckController extends Controller
{
    /**
     * Show the device check page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Ensure we can access the view
        try {
            return view('device.check');
        } catch (\Exception $e) {
            // If there's an error with the view, return a simple fallback
            return response()->view('errors.500', ['exception' => $e], 500);
        }
    }

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
                Log::info("GETMAC output: " . json_encode($output));

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
                Log::info("ARP output for IP {$ip}: " . json_encode($output));

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
                }

                // Try direct ipconfig command to get physical address - this is the most reliable
                $output = [];
                exec('ipconfig /all', $output);
                Log::info("IPCONFIG output: " . json_encode($output));

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
                        Log::info("Found matching IP adapter: " . $current_adapter);
                    }

                    // If we found the right adapter, get its MAC
                    if ($ip_found && strpos($line, "Physical Address") !== false) {
                        $parts = explode(":", $line, 2); // Split at first colon only
                        if (count($parts) > 1) {
                            $physical_address = trim($parts[1]);
                            // Remove any spaces in the MAC address
                            $physical_address = str_replace(' ', '', $physical_address);
                            Log::info("Extracted physical address: " . $physical_address);

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
                                Log::info("Extracted any physical address: " . $physical_address);

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

                Log::info("Generated device fingerprint as MAC: {$mac_format}");
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

                Log::info("Linux ARP output: " . json_encode($output));

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

        Log::info("Generated device fingerprint as MAC: {$mac_format}");
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
     * Check device status and return device information
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkDevice(Request $request)
    {
        try {
            $clientIp = $request->ip();
            $macAddress = $this->getMacAddressFromIp($clientIp);
            $browserFingerprint = $this->createBrowserFingerprint($request);

            $macAllowed = $this->isIdentifierAllowed($macAddress);
            $fingerprintAllowed = $this->isIdentifierAllowed($browserFingerprint);

            // Try to get allowed devices, but handle database errors gracefully
            try {
                $allowedDevices = ConfigConst::where('const_group', 'ALLOW_DEVICE')->get(['id', 'str1', 'str2', 'note1']);
            } catch (\Exception $e) {
                Log::error("Error fetching allowed devices: " . $e->getMessage());
                $allowedDevices = [];
            }

            return response()->json([
                'clientIp' => $clientIp,
                'macAddress' => $macAddress,
                'browserFingerprint' => $browserFingerprint,
                'macAllowed' => $macAllowed,
                'fingerprintAllowed' => $fingerprintAllowed,
                'allowedDevices' => $allowedDevices,
                'serverOS' => PHP_OS,
                'serverInfo' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'userAgent' => $request->header('User-Agent'),
                'requestHeaders' => collect($request->header())->filter(function($value, $key) {
                    // Filter out sensitive headers
                    return !in_array(strtolower($key), ['cookie', 'authorization']);
                })
            ]);
        } catch (\Exception $e) {
            Log::error("Error in checkDevice: " . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error checking device: ' . $e->getMessage(),
                'clientIp' => $request->ip(),
                'macAddress' => 'Error detecting',
                'browserFingerprint' => 'Error generating',
                'macAllowed' => false,
                'fingerprintAllowed' => false,
                'serverOS' => PHP_OS,
                'userAgent' => $request->header('User-Agent')
            ]);
        }
    }

    /**
     * Register a new device to allowed list
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerDevice(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|max:30',
            'identifierType' => 'required|in:mac,fingerprint',
            'remarks' => 'nullable|string|max:255',
        ]);

        try {
            $identifier = strtolower($request->input('identifier'));
            $remarks = $request->input('remarks') ?: 'Added on ' . now()->format('Y-m-d H:i:s');

            // Create a new ConfigConst record
            $device = new ConfigConst();
            $device->const_group = 'ALLOW_DEVICE';
            $device->str1 = $identifier;

            // Check if the model has 'remarks' or 'note1' field
            if (in_array('remarks', $device->getFillable())) {
                $device->remarks = $remarks;
            } else if (in_array('note1', $device->getFillable())) {
                $device->note1 = $remarks;
            }

            $device->save();

            return response()->json([
                'success' => true,
                'message' => 'Device registered successfully',
                'device' => $device
            ]);
        } catch (\Exception $e) {
            Log::error("Error registering device: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to register device: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if the identifier is in the allowed list from database
     *
     * @param string|null $identifier Device identifier (MAC address or fingerprint)
     * @return bool
     */
    private function isIdentifierAllowed($identifier)
    {
        if (!$identifier) {
            return false;
        }

        // Check if identifier is in the ALLOW_DEVICE constant group
        return ConfigConst::where('const_group', 'ALLOW_DEVICE')
            ->where(function ($query) use ($identifier) {
                $query->where('str1', $identifier)
                    ->orWhere('str2', $identifier)
                    // Also check for case-insensitive matches
                    ->orWhereRaw('LOWER(str1) = ?', [strtolower($identifier)])
                    ->orWhereRaw('LOWER(str2) = ?', [strtolower($identifier)]);
            })
            ->exists();
    }
}
