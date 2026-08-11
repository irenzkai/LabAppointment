<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Enforced by auth middleware in routing
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 1. Split Name Fields with strict 60/10 character limits (DOH & PSA Compliant)
            'first_name' => ['required', 'string', 'max:60', $this->nameRule()],
            'middle_name' => ['nullable', 'string', 'max:60', $this->nameRule()],
            'last_name' => ['required', 'string', 'max:60', $this->nameRule()],
            'suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\s.]+$/u'], // Alphanumeric, spaces, and periods allowed

            // 2. Profile Details
            'birthdate' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
            'sex' => ['required', 'string', 'in:Male,Female'],

            // 3. PSGC Address Constraints (Mirrors the exact migration column limits)
            'street' => ['required', 'string', 'max:150'],
            'barangay' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],

            // 4. Contact & Security (Single @ verification & strict 11-digit mobile standard)
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:191', 
                'unique:users,email,' . $this->user()->id, 
                'regex:/^[^@]+@[^@]+$/'
            ],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
        ];
    }

    /**
     * Get the custom error messages for the validation rules.
     */
    public function messages(): array
    {
        return [
            'email.regex' => 'The email address must contain exactly one @ symbol.',
            'phone.regex' => 'The phone number must start with 09 and contain exactly 11 digits.',
            'birthdate.before_or_equal' => 'Administrative Policy: You must be at least 18 years old.',
            'suffix.regex' => 'The suffix may only contain letters, numbers, spaces, and periods.',
        ];
    }

    /**
     * Shared name validation engine (Symmetric with Registration boundaries).
     */
    private function nameRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') {
                return; // Handled by nullable/required constraints
            }

            // 1. Allowed characters boundary validation (Letters, Spanish ñ/Ñ, periods, hyphens, spaces, apostrophes)
            if (!preg_match('/^[a-zA-ZñÑ\s.\'-]+$/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " may only contain letters, spaces, periods, hyphens, and apostrophes.");
                return;
            }

            // 2. Strict non-punctuation starting validation
            if (!preg_match('/^[a-zA-ZñÑ]/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " must start with a letter.");
                return;
            }

            // 3. Must possess at least one character letter to prevent punctuation-only values
            if (!preg_match('/[a-zA-ZñÑ]/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " must contain at least one letter.");
                return;
            }

            // 4. Consecutive punctuation marks validation
            if (preg_match('/[.\'-]{2,}/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " cannot contain consecutive punctuation marks.");
                return;
            }
        };
    }
}