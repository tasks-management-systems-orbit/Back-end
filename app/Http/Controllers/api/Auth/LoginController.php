<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Services\AuthService;
use Illuminate\Http\Request;  // ✅ Added this

class LoginController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AuthService $authService)
    {
    }

    /**
     * Login user and create token
     *
     */
    public function login(LoginRequest $request)  // ✅ Fixed type hint
    {
        $field = $request->getLoginField();
        $value = $request->input('login');

        $user = $this->authService->login(
            $field,
            $value,
            $request->password,
            $request->boolean('remember')
        );

        if (!$user->hasVerifiedEmail()) {
            return $this->errorResponse(
                'Email verification required. A new verification code has been sent.',
                403
            );
        }

        if (!$user->is_active) {
            return $this->errorResponse(
                'Account is deactivated. Please contact support.',
                403
            );
        }

        $token = $user->createToken('mobile_app')->plainTextToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ], 'Login successful.');
    }

    /**
     * Get authenticated user details
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)  // ✅ Fixed type hint
    {
        $user = $request->user();

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'email_verified' => $user->hasVerifiedEmail(),
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
            'email_verified_at' => $user->email_verified_at
        ]);
    }
}
