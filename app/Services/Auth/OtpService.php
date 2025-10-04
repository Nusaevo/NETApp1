<?php

namespace App\Services\Auth;

use App\Models\SysConfig1\ConfigUser;
use App\Models\SysConfig1\ConfigConst;
use App\Services\Base\BaseService;
use App\Enums\Constant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Resend\Laravel\Facades\Resend;

class OtpService extends BaseService
{
    // Device trust cookie constants
    const DEVICE_TRUST_COOKIE = 'nusaevo_device_trust';
    const COOKIE_LIFETIME_DAYS = 1000; // 1 year

    public function __construct()
    {
        parent::__construct();
        // Use SysConfig1 connection for user operations
        $this->setConfigConnection(Constant::configConn()); // SysConfig1 for users
        // Use TrdTire1 connection only for getting email configuration
       try {
            $this->setMainConnection('TrdTire1'); // TrdTire1 for email config lookup only
        } catch (\Exception $e) {
            // If TrdTire1 connection is not available, fall back to SysConfig1
            // This prevents errors when users don't have access to TrdTire1
            $this->setMainConnection(Constant::configConn());
        }
    }
    /**
     * Generate and send OTP to authorized emails
     */
    public function generateAndSendOtp(ConfigUser $user, $appCode = 'TrdTire1')
    {
        // Generate OTP for user (user uses its original SysConfig1 connection)
        $otp = $user->generateOtp();

        // Get authorized emails from TrdTire1 ConfigConst
        $authorizedEmails = $this->getAuthorizedEmails($appCode, $user->getGroupCodesBySessionAppCode());

        // Send OTP to authorized emails
        foreach ($authorizedEmails as $email) {
            $this->sendOtpEmail($email, $otp, $user->name);
        }

        return $otp;
    }

    /**
     * Get authorized emails from ConfigConst based on group codes
     */
    private function getAuthorizedEmails($appCode, $groupCodes)
    {
        $emails = [];

        // Look for email configuration in ConfigConst using TrdTire1 connection ONLY
        $emailConfig = $this->mainConnection
            ->table('config_consts')
            ->select('note1')
            ->where('const_group', 'OTP_EMAILS')
            ->first();

        if ($emailConfig && $emailConfig->note1) {
            // Assume emails are comma-separated in note1
            $groupEmails = explode(',', $emailConfig->note1);
            $emails = array_merge($emails, array_map('trim', $groupEmails));
        } else {
            // Fallback emails if no configuration found
            $emails = ['andrych17@gmail.com', 'andrych1998@gmail.com'];
        }

        // Remove duplicates and filter valid emails
        $emails = array_unique($emails);
        $validEmails = array_filter($emails, function($email) {
            return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        });

        return $validEmails;
    }

    /**
     * Send OTP email using Resend
     */
    /**
     * Send OTP email using Resend
     */
    private function sendOtpEmail($email, $otp, $userName)
    {
        try {
            $fromEmail = 'noreply@nusaevo.com'; // Ganti ke domain yang sudah diverifikasi
            $fromName = 'Nusaevo System';
            $fromField = $fromName . ' <' . $fromEmail . '>';

            // Get device and user information
            $deviceInfo = $this->getDeviceInfo();
            $userInfo = $this->getUserInfo();

            Resend::emails()->send([
                'from' => $fromField,
                'to' => [$email],
                'subject' => 'OTP untuk Akses Nusaevo',
                'html' => $this->getOtpEmailTemplate($otp, $userName, $deviceInfo, $userInfo)
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get OTP email template
     */
    private function getOtpEmailTemplate($otp, $userName, $deviceInfo = null, $userInfo = null)
    {
        $currentTime = now()->setTimezone('Asia/Jakarta')->format('d F Y, H:i:s');

        $template = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
                <!-- Header -->
                <div style='background: linear-gradient(135deg, #495057, #6c757d); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
                    <h1 style='margin: 0; font-size: 24px; font-weight: bold;'>🔐 Kode OTP Nusaevo</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>Verifikasi Akses Cahaya Terang</p>
                </div>

                <!-- Content -->
                <div style='padding: 30px;'>
                    <p style='color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>Halo,</p>
                    <p style='color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 25px 0;'>
                        User <strong style='color: #495057;'>{$userName}</strong> memerlukan akses ke aplikasi <strong style='color: #495057;'>Cahaya Terang</strong>.
                    </p>

                    <!-- OTP Code Box -->
                    <div style='background: #f8f9fa; border: 2px dashed #495057; padding: 25px; border-radius: 10px; text-align: center; margin: 25px 0;'>
                        <p style='margin: 0 0 10px 0; color: #6c757d; font-size: 14px; font-weight: 500;'>KODE OTP ANDA</p>
                        <div style='font-size: 32px; font-weight: bold; color: #495057; letter-spacing: 8px; font-family: monospace;'>{$otp}</div>
                        <p style='margin: 10px 0 0 0; color: #dc3545; font-size: 14px; font-weight: 500;'>⏰ Berlaku selama 5 menit</p>
                    </div>";

        // Add device and user information if available
        if ($deviceInfo || $userInfo) {
            $template .= "
                    <!-- Security Information -->
                    <div style='background: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; margin: 25px 0; border-radius: 0 8px 8px 0;'>
                        <h3 style='margin: 0 0 15px 0; color: #1976d2; font-size: 16px;'>🛡️ Informasi Keamanan</h3>";

            if ($userInfo) {
                $template .= "
                        <div style='margin-bottom: 12px;'>
                            <strong style='color: #333;'>👤 User:</strong> <span style='color: #555;'>{$userInfo['name']}</span>
                        </div>";
            }

            if ($deviceInfo) {
                $template .= "
                        <div style='margin-bottom: 8px;'>
                            <strong style='color: #333;'>💻 Perangkat:</strong> <span style='color: #555;'>{$deviceInfo['device']}</span>
                        </div>
                        <div style='margin-bottom: 8px;'>
                            <strong style='color: #333;'>🌐 Browser:</strong> <span style='color: #555;'>{$deviceInfo['browser']}</span>
                        </div>
                        <div style='margin-bottom: 8px;'>
                            <strong style='color: #333;'>📍 IP Address:</strong> <span style='color: #555;'>{$deviceInfo['ip']}</span>
                        </div>";
            }

            $template .= "
                        <div>
                            <strong style='color: #333;'>🕒 Waktu Request:</strong> <span style='color: #555;'>{$currentTime} WIB</span>
                        </div>
                    </div>";
        }

        $template .= "
                    <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin: 25px 0;'>
                        <p style='margin: 0; color: #856404; font-size: 14px; line-height: 1.5;'>
                            <strong>⚠️ Perhatian:</strong> Jika Anda tidak melakukan request ini, segera hubungi administrator sistem untuk keamanan akun Anda.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div style='background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; border-top: 1px solid #e9ecef;'>
                    <p style='margin: 0; color: #6c757d; font-size: 12px; text-align: center; line-height: 1.4;'>
                        Email ini dikirim secara otomatis oleh sistem Nusaevo.<br>
                        Mohon tidak membalas email ini.
                    </p>
                </div>
            </div>
        ";

        return $template;
    }

    /**
     * Get device information
     */
    private function getDeviceInfo()
    {
        $userAgent = request()->header('User-Agent');
        $ip = request()->ip();

        // Simple device detection
        $device = 'Desktop';
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            $device = 'Mobile Device';
        } else if (preg_match('/Tablet|iPad/', $userAgent)) {
            $device = 'Tablet';
        }

        // Simple browser detection
        $browser = 'Unknown Browser';
        if (strpos($userAgent, 'Chrome') !== false) {
            $browser = 'Google Chrome';
        } else if (strpos($userAgent, 'Firefox') !== false) {
            $browser = 'Mozilla Firefox';
        } else if (strpos($userAgent, 'Safari') !== false) {
            $browser = 'Safari';
        } else if (strpos($userAgent, 'Edge') !== false) {
            $browser = 'Microsoft Edge';
        }

        return [
            'device' => $device,
            'browser' => $browser,
            'ip' => $ip,
            'user_agent' => $userAgent
        ];
    }

    /**
     * Get user information
     */
    private function getUserInfo()
    {
        $user = Auth::user();

        if ($user) {
            return [
                'name' => $user->name ?? 'Unknown',
                'email' => $user->email ?? 'Unknown',
                'id' => $user->id ?? null
            ];
        }

        return [
            'name' => 'Guest User',
            'email' => 'Unknown',
            'id' => null
        ];
    }

    /**
     * Check if current device is trusted (ALWAYS_ALLOW = true)
     */
    public function isDeviceTrusted()
    {
        $cookieValue = request()->cookie(self::DEVICE_TRUST_COOKIE);
        return $cookieValue === 'true';
    }

    /**
     * Set current device as trusted (ALWAYS_ALLOW = true)
     */
    public function setDeviceAsTrusted()
    {
        // Create persistent cookie that expires in 1 year
        $expiresAt = now()->addDays(self::COOKIE_LIFETIME_DAYS);

        cookie()->queue(
            cookie(
                self::DEVICE_TRUST_COOKIE,
                'true',
                self::COOKIE_LIFETIME_DAYS * 24 * 60, // Convert days to minutes
                '/',
                null,
                false, // Not secure (change to true in production with HTTPS)
                true   // HTTP only
            )
        );
    }

    /**
     * Clear device trust (for security purposes)
     */
    public function clearDeviceTrust()
    {
        cookie()->queue(
            cookie()->forget(self::DEVICE_TRUST_COOKIE)
        );
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(ConfigUser $user, $inputOtp)
    {
        $result = $user->verifyOtp($inputOtp);

        // If OTP verification successful, mark device as trusted
        if ($result) {
            $this->setDeviceAsTrusted();
        }

        return $result;
    }
}
