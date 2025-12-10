<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Services\Auth\OtpService;
use App\Models\SysConfig1\ConfigUser;
use App\Models\SysConfig1\ConfigRight;
use App\Models\SysConfig1\ConfigAppl;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // addJavascriptFile('assets/js/custom/authentication/sign-in/general.js');

        return view('pages/auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        // Validate credentials but don't authenticate yet
        $credentials = $request->validated();

        if (!Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'code' => 'The provided credentials are incorrect.',
            ]);
        }

        // Get user without authenticating
        $user = ConfigUser::where('code', $credentials['code'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'code' => 'User not found.',
            ]);
        }

        // Check if user has access to TrdTire1
        $appCode = ['TrdTire1'];
        $hasAccess = ConfigRight::checkUserAppAccess($user, $appCode);

        if (!$hasAccess) {
            // Use authenticate() for users without TrdTire1 access
            $request->authenticate();
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        // Check if user needs OTP for TrdTire1
        $otpAccess = $user->hasOtpAccessToApp('TrdTire1');

        if ($otpAccess === 'bypass') {
            // No OTP required, use authenticate() to set session data
            $request->authenticate();
            return redirect('/');
        }

        if ($otpAccess) {
            // Check if current device is already trusted
            if ($this->otpService->isDeviceTrusted()) {
                // Device is trusted, refresh cookie expiration and proceed with authentication
                $this->otpService->refreshDeviceTrust();
                $request->authenticate();
                return redirect('/')->with('message', 'Login berhasil. Device sudah terverifikasi.');
            }

            // Device not trusted, require OTP verification
            // Store user ID in session for later authentication after OTP verification
            session(['pending_user_id' => $user->id, 'app_code' => 'TrdTire1']);

            // Generate and send OTP
            try {
                $this->otpService->generateAndSendOtp($user, 'TrdTire1');
                return redirect()->route('auth.otp.show')->with('message', 'OTP telah dikirim ke email yang terdaftar.');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Gagal mengirim OTP. Silakan coba lagi.');
            }
        }

        // No OTP access, use authenticate() to set session data
        $request->authenticate();
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Show OTP verification form
     */
    public function showOtpForm()
    {
        // Check if there's a pending user for OTP verification
        $pendingUserId = session('pending_user_id');

        if (!$pendingUserId) {
            return redirect()->route('login')->with('error', 'Tidak ada OTP yang pending. Silakan login ulang.');
        }

        return view('pages.auth.otp');
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6'
        ]);

        // Get pending user from session
        $pendingUserId = session('pending_user_id');

        if (!$pendingUserId) {
            return redirect()->route('login')->with('error', 'Session expired. Silakan login ulang.');
        }

        $user = ConfigUser::find($pendingUserId);

        if (!$user) {
            return redirect()->route('login')->with('error', 'User tidak ditemukan. Silakan login ulang.');
        }

        if ($this->otpService->verifyOtp($user, $request->otp)) {
            // OTP verified successfully, now authenticate using same logic as LoginRequest
            Auth::login($user);

            // Set session data using the same logic as LoginRequest authenticate()
            $salt = Str::random(40);
            $appKey = config('app.key');
            Session::put('session_salt', $salt . $appKey);

            $configService = new \App\Services\SysConfig1\ConfigService();
            $appIds = $configService->getAppIds();

            if (!empty($appIds)) {
                $firstAppId = $appIds[0];
                $firstApp = \App\Models\SysConfig1\ConfigAppl::find($firstAppId);

                if ($firstApp) {
                    Session::put('app_id', $firstApp->id);
                    Session::put('app_code', $firstApp->code);
                    Session::put('database', $firstApp->db_name);

                    // Set group codes
                    $groupCodes = $user->getGroupCodesBySessionAppCode();
                    Session::put('group_codes', $groupCodes);

                    session()->regenerate();

                    // Restore session data after regeneration
                    Session::put('app_id', $firstApp->id);
                    Session::put('app_code', $firstApp->code);
                    Session::put('database', $firstApp->db_name);
                    Session::put('group_codes', $groupCodes);
                }
            }

            // Clear pending user ID
            session()->forget('pending_user_id');

            return redirect('/')->with('message', 'OTP berhasil diverifikasi.');
        }

        return redirect()->back()->with('error', 'OTP tidak valid atau sudah expired.');
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        // Get pending user from session
        $pendingUserId = session('pending_user_id');

        if (!$pendingUserId) {
            return redirect()->route('login')->with('error', 'Session expired. Silakan login ulang.');
        }

        $user = ConfigUser::find($pendingUserId);

        if (!$user) {
            return redirect()->route('login')->with('error', 'User tidak ditemukan. Silakan login ulang.');
        }

        try {
            $this->otpService->generateAndSendOtp($user, 'TrdTire1');
            return redirect()->back()->with('message', 'OTP baru telah dikirim.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengirim OTP. Silakan coba lagi.');
        }
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        try {
            $userId = Auth::id();
            //Log::info('Attempting to destroy session for user ID: ' . $userId);

            if (!$userId) {
                throw new \Exception('User is not authenticated.');
            }

            Auth::guard('web')->logout();
            //Log::info('User logged out.');

            $request->session()->invalidate();
            //Log::info('Session invalidated.');

            $request->session()->regenerateToken();
            //Log::info('Session token regenerated.');

            return redirect('/')->with('message', 'Logged out successfully');
        } catch (\Exception $e) {
            Log::error('Error during logout: ' . $e->getMessage());
            return redirect('/')->with('error', 'There was a problem logging you out.');
        }
    }

    /**
     * Clear device trust (for security purposes)
     */
    public function clearDeviceTrust(Request $request)
    {
        $this->otpService->clearDeviceTrust();
        return redirect()->back()->with('message', 'Device trust telah dihapus. OTP akan diperlukan pada login berikutnya.');
    }

}
