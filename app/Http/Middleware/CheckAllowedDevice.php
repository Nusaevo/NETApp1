<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Device Check Middleware - DISABLED BY DEFAULT
 *
 * This middleware is currently disabled and not applied to any routes.
 * Device checks are ONLY used during OTP verification process, not for general authentication.
 *
 * To re-enable device checks:
 * 1. Add 'check.allowed.device' middleware to specific routes in web.php
 * 2. Uncomment the device validation logic in the handle() method below
 * 3. Ensure ConfigConst table has ALLOW_DEVICE entries configured
 */
class CheckAllowedDevice
{
    /**
     * Handle an incoming request.
     *
     * DEVICE CHECKS ARE DISABLED - This middleware currently allows all devices through.
     * Device validation is only performed during OTP verification process.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // DEVICE CHECKS DISABLED - All devices are allowed
        // Device validation only occurs during OTP verification process
        // To re-enable, uncomment the code block below and apply middleware to routes

        /*
        if (Auth::check()) {
            // Get the client's IP address
            $clientIp = $request->getClientIp();

            // Get MAC address using IP
            $macAddress = $this->getMacAddressFromIp($clientIp);

            // Check if the MAC address is in the allowed devices list
            $isAllowed = $this->isDeviceAllowed($macAddress);

            if (!$isAllowed) {
                // MAC address not in allowed list
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Device not authorized. Please contact your administrator.');
            }
        }
        */

        return $next($request);
    }

    /**
     * Get MAC address from IP address
     *
     * Note: This is a simplified implementation. Getting MAC addresses in web applications
     * is challenging due to browser security limitations.
     *
     * @param string $ip
     * @return string|null
     */
    private function getMacAddressFromIp($ip)
    {
        // This is a simplified implementation for demonstration
        // In reality, server-side MAC address detection is limited and may not be reliable

        // On Windows server, try to use ARP
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = [];
            exec('arp -a ' . $ip, $output);

            foreach ($output as $line) {
                if (strpos($line, $ip) !== false) {
                    $matches = [];
                    preg_match('/([0-9a-fA-F]{2}[:-]){5}([0-9a-fA-F]{2})/', $line, $matches);
                    if (isset($matches[0])) {
                        return strtolower(str_replace('-', ':', $matches[0]));
                    }
                }
            }
        }
        // On Linux server, try to use ARP
        else {
            $output = [];
            exec('arp -a ' . $ip, $output);

            foreach ($output as $line) {
                if (strpos($line, $ip) !== false) {
                    $matches = [];
                    preg_match('/([0-9a-fA-F]{2}[:-]){5}([0-9a-fA-F]{2})/', $line, $matches);
                    if (isset($matches[0])) {
                        return strtolower(str_replace('-', ':', $matches[0]));
                    }
                }
            }
        }

        // If we couldn't determine MAC address, log this
        Log::warning("Could not determine MAC address for IP: {$ip}");
        // For testing, we can return the client IP as a fallback
        // In production, you might want to handle this differently
        return $ip;
    }

    /**
     * Check if the device is in the allowed list from database
     *
     * @param string|null $macAddress
     * @return bool
     */
    private function isDeviceAllowed($macAddress)
    {
        if (!$macAddress) {
            // If we couldn't determine the MAC address, we should log this and decide
            // whether to allow or deny access. For security, we'll deny.
            Log::warning("MAC address could not be determined");
            return false;
        }

        // Check if MAC address is in the ALLOW_DEVICE constant group
        $allowedDevices = ConfigConst::where('const_group', 'ALLOW_DEVICE')
            ->where(function ($query) use ($macAddress) {
                $query->where('str1', $macAddress)
                    ->orWhere('str2', $macAddress);
            })
            ->exists();

        if (!$allowedDevices) {
            Log::warning("Unauthorized device attempted login: {$macAddress}");
            return false;
        }

        return true;
    }
}
