<?php
// app/Http/Requests/Profile/StoreProfileRequest.php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id|unique:profiles,user_id',
            'phone' => 'nullable|string|max:50|unique:profiles,phone',
            'bio' => 'nullable|string|max:1000',
            'job_title' => 'nullable|string|max:255',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'avatar' => 'nullable|string|max:255|url',
            'location' => 'nullable|string|max:255',
            'twitter_url' => 'nullable|string|max:255|url',
            'alternative_email' => 'nullable|string|email|max:255',
            'github_url' => 'nullable|string|max:255|url',
            'portfolio_url' => 'nullable|string|max:255|url',
            'linkedin_url' => 'nullable|string|max:255|url',
            'cv_url' => 'nullable|string|max:255|url',
            'language' => 'nullable|string|max:10|in:ar,en,fr,es',
            'theme' => ['nullable', Rule::in(['light', 'dark'])],
            'is_public' => 'nullable|boolean',
        ];
    }


    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'User does not exist',
            'user_id.unique' => 'This user already has a profile',
            'phone.unique' => 'Phone number is already in use',
            'phone.max' => 'Phone number must not exceed 50 characters',
            'bio.max' => 'Bio must not exceed 1000 characters',
            'job_title.max' => 'Job title must not exceed 255 characters',
            'skills.array' => 'Skills must be an array',
            'skills.*.string' => 'Each skill must be a string',
            'skills.*.max' => 'Each skill must not exceed 100 characters',
            'avatar.url' => 'Avatar URL must be a valid URL',
            'avatar.max' => 'Avatar URL must not exceed 255 characters',
            'location.max' => 'Location must not exceed 255 characters',
            'twitter_url.url' => 'Twitter URL must be a valid URL',
            'alternative_email.email' => 'Alternative email must be a valid email address',
            'alternative_email.max' => 'Alternative email must not exceed 255 characters',
            'github_url.url' => 'GitHub URL must be a valid URL',
            'portfolio_url.url' => 'Portfolio URL must be a valid URL',
            'linkedin_url.url' => 'LinkedIn URL must be a valid URL',
            'cv_url.url' => 'CV URL must be a valid URL',
            'language.in' => 'Language must be one of: ar, en, fr, es',
            'theme.in' => 'Theme must be either light or dark',
            'is_public.boolean' => 'Is public field must be true or false',
        ];
    }
}
