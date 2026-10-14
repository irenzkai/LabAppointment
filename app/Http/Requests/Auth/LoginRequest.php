<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Please enter your registered email address or mobile phone number.',
            'password.required' => 'Please enter your account password.',
        ];
    }

    /**
     * Check if the provided login string is formatted as a mobile phone number.
     */
    protected function isPhoneInput(string $input): bool
    {
        if (str_contains($input, '@')) {
            return false;
        }

        $clean = preg_replace('/[\s\-\(\)]/', '', $input);
        return (bool) preg_match('/^(\+?639\d{9}|639\d{9}|09\d{9}|9\d{9})$/', $clean);
    }

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
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $rawInput = (string) $this->input('email', '');
        $cleanInput = trim($rawInput);
        $password = (string) $this->input('password', '');
        $isPhone = $this->isPhoneInput($cleanInput);

        if ($isPhone) {
            $normalizedPhone = $this->normalizePhoneNumber($cleanInput);
            $user = User::where('phone', $normalizedPhone)->first();
            $credentialType = 'phone';
        } else {
            $normalizedEmail = strtolower($cleanInput);
            $user = User::where('email', $normalizedEmail)->first();
            $credentialType = 'email';
        }

        // If no active user is found, check for deactivated (soft-deleted) accounts
        if (! $user) {
            if ($isPhone) {
                $trashedUser = User::onlyTrashed()->where('phone', $normalizedPhone)->first();
            } else {
                $trashedUser = User::onlyTrashed()->where('email', $normalizedEmail)->first();
            }

            if ($trashedUser && Hash::check($password, $trashedUser->password)) {
                RateLimiter::clear($this->throttleKey());

                // Conflict check: if the deactivated account's credentials are now verified on another active account
                $emailInUse = User::where('id', '!=', $trashedUser->id)
                    ->where('email', $trashedUser->email)
                    ->whereNotNull('email_verified_at')
                    ->exists();

                $phoneInUse = !empty($trashedUser->phone) && User::where('id', '!=', $trashedUser->id)
                    ->where('phone', $trashedUser->phone)
                    ->whereNotNull('phone_verified_at')
                    ->exists();

                if ($emailInUse && $phoneInUse) {
                    throw ValidationException::withMessages([
                        'email' => 'This account has been permanently retired because its email address and phone number have already been claimed and verified by another active account.',
                    ]);
                }

                if ($isPhone && $phoneInUse) {
                    throw ValidationException::withMessages([
                        'email' => 'This account cannot be reactivated using this phone number because it has already been claimed and verified by another active account.',
                    ]);
                }

                if (! $isPhone && $emailInUse) {
                    throw ValidationException::withMessages([
                        'email' => 'This account cannot be reactivated using this email address because it has already been claimed and verified by another active account.',
                    ]);
                }

                // No conflict: proceed to standard reactivation OTP dispatch
                session()->put('reactivate_user_id', $trashedUser->id);
                Cache::put("reactivate_ip_{$this->ip()}", $trashedUser->id, now()->addMinutes(30));

                app(EmailVerificationNotificationController::class)->sendReactivationOtp($this);

                throw ValidationException::withMessages([
                    'deactivated' => 'Your account is currently deactivated. A verification code has been dispatched to your email to reactivate it.',
                ]);
            }

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Validate password
        if (! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Check if account has been suspended by Admin
        if (! $user->is_active) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'This account has been disabled by the administrator.',
            ]);
        }

        // Enforce verified credentials rule:
        // Only the verified credential allows login (e.g. if phone is verified and email is unverified, only phone can log in)
        if ($credentialType === 'phone') {
            if (is_null($user->phone_verified_at)) {
                RateLimiter::hit($this->throttleKey());

                if (! is_null($user->email_verified_at)) {
                    throw ValidationException::withMessages([
                        'email' => 'This phone number is unverified and cannot be used to log in. Please sign in using your verified email address.',
                    ]);
                } else {
                    throw ValidationException::withMessages([
                        'email' => 'This phone number is unverified and cannot be used to log in. Please sign in using your registered email address.',
                    ]);
                }
            }
        } else {
            if (is_null($user->email_verified_at)) {
                if (! is_null($user->phone_verified_at)) {
                    RateLimiter::hit($this->throttleKey());

                    throw ValidationException::withMessages([
                        'email' => 'This email address is unverified. Please sign in using your verified mobile phone number.',
                    ]);
                }
            }
        }

        // Successful authentication
        Auth::login($user, $this->boolean('remember'));
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}