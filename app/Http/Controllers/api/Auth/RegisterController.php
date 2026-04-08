<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Services\AuthService;
use App\Services\VerificationCodeService;

class RegisterController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected AuthService $authService,
        protected VerificationCodeService $verificationService
    ) {}


    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());

        $this->verificationService->generateAndSend($user->email, $user->name);

        $token = $user->createToken('mobile_app')->plainTextToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
            'requires_verification' => true
        ], 'Account created successfully. Please verify your email.', 201);
    }
}
