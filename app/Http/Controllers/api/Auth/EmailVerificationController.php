<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ResendVerificationRequest;
use App\Http\Requests\Api\Auth\VerifyEmailRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Request;
use App\Models\User;
use App\Services\VerificationCodeService;

class EmailVerificationController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected VerificationCodeService $verificationService) {}

    public function verify(VerifyEmailRequest $request)
    {
        $verified = $this->verificationService->verify(
            $request->email,
            $request->code
        );

        if (!$verified) {
            return $this->errorResponse('Invalid or expired verification code.', 400);
        }

        return $this->successResponse(null, 'Email verified successfully. You can now login.');
    }

    public function resend(ResendVerificationRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->successResponse(null, 'If your email is registered, you will receive a verification code.');
        }

        if (!$user->is_active) {
            return $this->successResponse(null, 'If your email is registered, you will receive a verification code.');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(null, 'If your email is registered, you will receive a verification code.');
        }

        $this->verificationService->generateAndSend($user->email, $user->name);

        return $this->successResponse(null, 'If your email is registered, you will receive a verification code.');
    }

    public function checkStatus(Request $request)
    {
        $user = $request->user();

        return $this->successResponse([
            'email' => $user->email,
            'is_verified' => $user->hasVerifiedEmail(),
            'verified_at' => $user->email_verified_at
        ]);
    }
}
