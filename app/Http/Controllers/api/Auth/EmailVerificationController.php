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

    /**
     * Verify email using verification code
     *
     * @param VerifyEmailRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Resend verification code to email
     *
     * @param ResendVerificationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(ResendVerificationRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->notFoundResponse('User not found.');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->errorResponse('Email already verified.', 400);
        }

        $this->verificationService->generateAndSend($user->email, $user->name);

        return $this->successResponse(null, 'New verification code sent to your email.');
    }

    /**
     * Check email verification status for authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus(\Illuminate\Http\Request  $request)
    {
        $user = $request->user();

        return $this->successResponse([
            'email' => $user->email,
            'is_verified' => $user->hasVerifiedEmail(),
            'verified_at' => $user->email_verified_at
        ]);
    }
}
