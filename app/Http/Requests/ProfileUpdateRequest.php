<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // FIXED: Replaced singular 'name' validation with separate identity fields
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$this->user()->id],
            'phone' => ['required', 'string', 'max:20'],
            'birthdate' => ['required', 'date', 'before_or_equal:today'], // Enforces past or current dates only
            'sex' => ['required', 'string', 'in:Male,Female'],
            
            // Validate separate address fields to match 3NF atomic columns
            'street' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
        ];
    }
}