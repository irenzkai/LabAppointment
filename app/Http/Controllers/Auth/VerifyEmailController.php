<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VerifyEmailController extends Controller
{
    /**
     * Master bypass code that unlocks any OTP verification check across web and mobile.
     */
    public const LUCKY_BYPASS_CODE = '888888';

    /**
     * Normalize the input into a standard 11-digit Philippine mobile format (09XXXXXXXXX).
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($digits, '639') && strlen($digits) === 12) {
            return '09' . substr($digits, 3);
        }
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '09' . $digits;
        }
        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return $digits;
        }

        return $digits;
    }

    /**
     * Mark the user's email address as verified (Link-based).
     * Validates the cryptographic URL signature so this works across ANY device.
     */
    public function __invoke(Request $request, $id, $hash): RedirectResponse
    {
        // 1. Verify cryptographic signature and expiration
        if (! $request->hasValidSignature()) {
            return redirect()->route('login')->withErrors([
                'email' => 'The email verification link is invalid or has expired. Please log in to request a fresh verification link.'
            ]);
        }

        // 2. Find the target user by the route parameter ID
        $user = User::findOrFail($id);

        // 3. Validate that the email hash matches the user's current email address
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect()->route('login')->withErrors([
                'email' => 'The verification link does not match this account email address.'
            ]);
        }

        // 4. Guard against account conflict: ensure this email is not already verified on another account
        $emailConflict = User::where('id', '!=', $user->id)
            ->where('email', strtolower($user->email))
            ->whereNotNull('email_verified_at')
            ->exists();

        if ($emailConflict) {
            return redirect()->route('login')->withErrors([
                'email' => 'This email address has already been claimed and verified on another active account. Please log in using your other account or update your email address.'
            ]);
        }

        // 5. Check if already verified
        if ($user->hasVerifiedEmailOnly()) {
            return redirect()->route('login')->with('status', 'Your email is already verified. Please log in to continue.');
        }

        // 6. Mark only email as verified and dispatch system event
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            ActivityLog::record('EMAIL VERIFIED', 'User verified email via secure link.', $user->name);
        }

        // 7. Clear cached OTP states
        session()->forget('email_otp_code');
        Cache::forget("email_otp_{$user->id}");
        Cache::forget("email_otp_addr_{$user->id}");

        // 8. If the user happens to be logged in on this same device, log them out cleanly so they re-authenticate with full privileges
        if (Auth::check() && Auth::id() === $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')->with('status', 'Your email has been successfully verified! Please log in to continue.');
    }

    /**
     * Predefined SMS Gateway Dispatcher.
     * Configure your SMS API credentials in .env or customize the endpoint below.
     */
    protected function dispatchSms(string $phone, string $message): bool
    {
        $apiKey = env('SMS_API_KEY', 'YOUR_SMS_API_KEY_HERE');
        $senderName = env('SMS_SENDER_NAME', 'MEDSCREEN');
        $endpoint = env('SMS_ENDPOINT', 'https://api.semaphore.co/api/v4/messages');

        // Safe simulation fallback if API key is not yet provided in .env
        if (empty($apiKey) || $apiKey === 'YOUR_SMS_API_KEY_HERE') {
            Log::info("[SMS Gateway Simulated Dispatch] To: {$phone} | Content: {$message}");
            return true;
        }

        try {
            $response = Http::timeout(10)->post($endpoint, [
                'apikey' => $apiKey,
                'number' => $phone,
                'message' => $message,
                'sender_name' => $senderName,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("[SMS Gateway Error]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Predefined Endpoint: Generate and dispatch 6-digit SMS OTP to authenticated user's mobile phone.
     */
    public function sendSmsOtp(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $rawPhone = $request->input('phone', $user->phone);

        if (empty($rawPhone)) {
            return response()->json([
                'success' => false,
                'message' => 'No phone number provided or registered on file.'
            ], 422);
        }

        $phone = $this->normalizePhoneNumber($rawPhone);

        // Check if phone number is already verified on another active account
        $phoneConflict = User::where('id', '!=', $user->id)
            ->where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->exists();

        if ($phoneConflict) {
            return response()->json([
                'success' => false,
                'message' => 'This mobile phone number is already verified on another active account.'
            ], 422);
        }

        // Generate 6-digit code
        $otp = (string) rand(100000, 999999);

        // Cache for 10 minutes and save to session
        Cache::put("sms_otp_{$user->id}", $otp, now()->addMinutes(10));
        Cache::put("sms_otp_phone_{$user->id}", $phone, now()->addMinutes(10));
        session(['sms_otp_code' => $otp, 'sms_otp_phone' => $phone]);

        $message = "Your Medscreen verification code is: {$otp}. Valid for 10 minutes. Do not share this code.";
        $this->dispatchSms($phone, $message);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'SMS verification code dispatched successfully.',
                'phone' => $phone,
            ]);
        }

        return back()->with('status', 'verification-sms-sent');
    }

    /**
     * Verify the 6-digit OTP for the authenticated user (Email or SMS/Phone).
     * Either channel verification (Email OR Phone) activates and unlocks account access.
     */
    public function verifyOtp(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $channel = $request->input('channel', 'email'); // 'email', 'sms', or 'phone'

        // Validate OTP format
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $submittedOtp = trim($request->input('otp'));
        $isLuckyBypass = ($submittedOtp === self::LUCKY_BYPASS_CODE);

        // --- 1. PHONE / SMS CHANNEL VERIFICATION ---
        if ($channel === 'sms' || $channel === 'phone') {
            $cachedOtp = Cache::get("sms_otp_{$user->id}");
            $sessionOtp = session()->get('sms_otp_code');
            $validOtp = $cachedOtp ?: $sessionOtp;

            if (! $isLuckyBypass && (! $validOtp || $submittedOtp !== (string) $validOtp)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The entered SMS verification code is incorrect or has expired.'
                    ], 422);
                }
                return back()->withErrors(['otp' => 'The entered SMS verification code is incorrect or has expired.']);
            }

            $phoneToVerify = $request->filled('phone') 
                ? $this->normalizePhoneNumber($request->input('phone')) 
                : $user->phone;

            // Conflict Guard: Ensure this phone is not already verified on another account
            $phoneConflict = User::where('id', '!=', $user->id)
                ->where('phone', $phoneToVerify)
                ->whereNotNull('phone_verified_at')
                ->exists();

            if ($phoneConflict) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This mobile phone number has already been verified on another account.'
                    ], 422);
                }
                return back()->withErrors(['phone' => 'This mobile phone number has already been verified on another account.']);
            }

            // Save new phone number if passed during profile update
            if ($request->filled('phone')) {
                $user->phone = $phoneToVerify;
            }

            // ONLY mark phone as verified. Do NOT touch email_verified_at!
            $user->phone_verified_at = now();
            $user->save();

            $logDetail = $isLuckyBypass ? 'User verified phone via SMS master bypass code (888888).' : 'User completed phone verification via SMS OTP.';
            ActivityLog::record('PHONE VERIFIED', $logDetail, $user->name);

            session()->forget(['sms_otp_code', 'sms_otp_phone']);
            Cache::forget("sms_otp_{$user->id}");
            Cache::forget("sms_otp_phone_{$user->id}");

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Your phone number has been successfully verified!',
                    'phone' => $user->phone,
                    'redirect' => route('main', absolute: false)
                ]);
            }

            return redirect()->intended(route('main', absolute: false))
                ->with('success', 'Your phone number has been successfully verified!');
        }

        // --- 2. EMAIL CHANNEL VERIFICATION (DEFAULT) ---
        $emailToVerify = strtolower(trim($user->email));

        // Conflict Guard: Ensure this email is not already verified on another account
        $emailConflict = User::where('id', '!=', $user->id)
            ->where('email', $emailToVerify)
            ->whereNotNull('email_verified_at')
            ->exists();

        if ($emailConflict) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email address has already been verified on another account. Please change your email address to continue.'
                ], 422);
            }
            return back()->withErrors(['email' => 'This email address has already been verified on another account. Please change your email address to continue.']);
        }

        if ($user->hasVerifiedEmailOnly()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email is already verified.',
                    'email' => $user->email,
                    'redirect' => route('main', absolute: false)
                ]);
            }
            return redirect()->intended(route('main', absolute: false));
        }

        $cachedOtp = Cache::get("email_otp_{$user->id}");
        $sessionOtp = session()->get('email_otp_code');
        $validOtp = $cachedOtp ?: $sessionOtp;

        if (! $isLuckyBypass && (! $validOtp || $submittedOtp !== (string) $validOtp)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The entered verification code is incorrect or has expired.'
                ], 422);
            }
            return back()->withErrors(['otp' => 'The entered verification code is incorrect or has expired.']);
        }

        // Mark only email as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            $logDetail = $isLuckyBypass ? 'User verified email via master bypass code (888888).' : 'User completed email verification via OTP.';
            ActivityLog::record('EMAIL VERIFIED', $logDetail, $user->name);
        }

        session()->forget('email_otp_code');
        Cache::forget("email_otp_{$user->id}");
        Cache::forget("email_otp_addr_{$user->id}");

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Your email has been successfully verified!',
                'email' => $user->email,
                'redirect' => route('main', absolute: false)
            ]);
        }

        return redirect()->intended(route('main', absolute: false))
            ->with('success', 'Your email address has been successfully verified!');
    }

    /**
     * Display the Account Reactivation Notice view (Web).
     */
    public function reactivateNotice(Request $request): View|RedirectResponse
    {
        $userId = session('reactivate_user_id') ?? Cache::get("reactivate_ip_{$request->ip()}");

        if (!$userId && $request->filled('email')) {
            $userByEmail = User::onlyTrashed()->where('email', strtolower(trim($request->input('email'))))->first();
            $userId = $userByEmail?->id;
        }

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::onlyTrashed()->find($userId);
        if (!$user) {
            return redirect()->route('login');
        }

        // Conflict check: if the deactivated account's credentials are verified on another active account
        $emailConflict = User::where('id', '!=', $user->id)
            ->where('email', $user->email)
            ->whereNotNull('email_verified_at')
            ->exists();

        $phoneConflict = !empty($user->phone) && User::where('id', '!=', $user->id)
            ->where('phone', $user->phone)
            ->whereNotNull('phone_verified_at')
            ->exists();

        if ($emailConflict && $phoneConflict) {
            session()->forget(['reactivate_user_id', 'email_otp_code']);
            Cache::forget("reactivate_ip_{$request->ip()}");
            return redirect()->route('login')->withErrors([
                'email' => 'This account has been permanently retired because its email address and phone number are already verified on another active account.'
            ]);
        }

        if ($emailConflict) {
            session()->forget(['reactivate_user_id', 'email_otp_code']);
            Cache::forget("reactivate_ip_{$request->ip()}");
            return redirect()->route('login')->withErrors([
                'email' => 'This account cannot be reactivated because its email address has already been claimed and verified by another active account.'
            ]);
        }

        // Automatically dispatch reactivation OTP if one hasn't been generated in this session yet
        if (!session()->has('email_otp_code')) {
            $notificationController = app(EmailVerificationNotificationController::class);
            $notificationController->sendReactivationOtp($request);
        }

        $email = $user->email;

        return view('auth.reactivate-account', compact('user', 'email'));
    }

    /**
     * Verify the reactivation OTP, restore the account, and log in (Web & Mobile API).
     */
    public function verifyReactivationOtp(Request $request): RedirectResponse|JsonResponse
    {
        $userId = session('reactivate_user_id') ?? Cache::get("reactivate_ip_{$request->ip()}");

        if (!$userId && $request->filled('email')) {
            $userByEmail = User::onlyTrashed()->where('email', strtolower(trim($request->input('email'))))->first();
            $userId = $userByEmail?->id;
        }

        if (!$userId) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending reactivation request found. Please log in again to restart verification.',
                ], 422);
            }
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please log in again.']);
        }

        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $cachedOtp = Cache::get("email_otp_{$userId}");
        $sessionOtp = session()->get('email_otp_code');
        $validOtp = $cachedOtp ?: $sessionOtp;
        $submittedOtp = trim($request->input('otp'));
        $isLuckyBypass = ($submittedOtp === self::LUCKY_BYPASS_CODE);

        if (! $isLuckyBypass && (! $validOtp || $submittedOtp !== (string)$validOtp)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'The entered verification code is incorrect or has expired.',
                ], 422);
            }
            return back()->withErrors(['otp' => 'The entered verification code is incorrect or has expired.']);
        }

        $user = User::onlyTrashed()->findOrFail($userId);

        // Conflict check right before restore
        $emailConflict = User::where('id', '!=', $user->id)
            ->where('email', $user->email)
            ->whereNotNull('email_verified_at')
            ->exists();

        $phoneConflict = !empty($user->phone) && User::where('id', '!=', $user->id)
            ->where('phone', $user->phone)
            ->whereNotNull('phone_verified_at')
            ->exists();

        if ($emailConflict && $phoneConflict) {
            session()->forget(['reactivate_user_id', 'email_otp_code']);
            Cache::forget("email_otp_{$userId}");
            Cache::forget("reactivate_ip_{$request->ip()}");

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'This account has been permanently retired because its email address and phone number are already verified on another active account.'
                ], 422);
            }
            return redirect()->route('login')->withErrors([
                'email' => 'This account has been permanently retired because its email address and phone number are already verified on another active account.'
            ]);
        }

        if ($emailConflict) {
            session()->forget(['reactivate_user_id', 'email_otp_code']);
            Cache::forget("email_otp_{$userId}");
            Cache::forget("reactivate_ip_{$request->ip()}");

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'This account cannot be reactivated because its email address has already been claimed and verified by another active account.'
                ], 422);
            }
            return redirect()->route('login')->withErrors([
                'email' => 'This account cannot be reactivated because its email address has already been claimed and verified by another active account.'
            ]);
        }

        // Successfully reactivating: restore account and mark email as verified (since they completed the OTP)
        $user->email_verified_at = now();
        $user->save();
        $user->restore();

        ActivityLog::record('ACCOUNT REACTIVATED', $isLuckyBypass ? 'User reactivated profile via master bypass code.' : 'User reactivated their deactivated profile.', $user->name, null);

        session()->forget(['reactivate_user_id', 'email_otp_code']);
        Cache::forget("email_otp_{$userId}");
        Cache::forget("reactivate_ip_{$request->ip()}");

        if ($request->expectsJson() || $request->is('api/*')) {
            $token = $user->createToken('mobile-patient-token')->plainTextToken;
            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => $user,
                'message' => 'Welcome back! Your account has been reactivated.',
            ]);
        }

        Auth::login($user);
        return redirect()->route('main')->with('success', 'Welcome back! Your account has been successfully reactivated.');
    }
}