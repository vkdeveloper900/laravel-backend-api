<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;
        $rules = [
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'dob' => 'nullable|date|before:today',
            'phone' => 'nullable|string|max:20',
        ];

        if ($this->filled('email')) {
            $rules['email'] = 'required|email|max:255|unique:users,email,' . $userId;
        }

        if ($this->filled('current_password')) {
            $rules['current_password'] = 'required|string';
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        return $rules;
    }
}
