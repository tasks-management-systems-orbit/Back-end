<?php

namespace app\Http\Controllers\api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    use ApiResponseTrait;

    /**
     * Logout user and revoke current token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully.');
    }

    /**
     * Logout user from all devices and revoke all tokens
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutFromAllDevices(Request $request)
    {
        $request->user()->tokens()->delete();

        return $this->successResponse(null, 'Logged out from all devices successfully.');
    }
}
