<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

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
     */
    public function rules(): array
    {
        return [
            'login' => 'required|string',
            'password' => 'required|string|min:6',
            'remember' => 'boolean',
            'device_name' => 'sometimes|string|max:50'
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Email or username is required.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'remember.boolean' => 'Remember me must be true or false.',
        ];
    }

    /**
     * Determine which field to use for login (email or username)
     */
    public function getLoginField(): string
    {
        $login = $this->input('login');

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        return 'username';
    }

    /**
     * Get the login value
     */
    public function getLoginValue(): string
    {
        return $this->input('login');
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('login')) {
            $this->merge([
                'login' => trim($this->login)
            ]);
        }
    }
}
