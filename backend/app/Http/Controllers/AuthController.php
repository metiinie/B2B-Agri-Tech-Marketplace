<?php

namespace App\Http\Controllers;

use App\Exceptions\SmsDeliveryException;
use App\Http\Requests\RequestOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
    ) {}

    /**
     * Request an OTP code for registration or login.
     *
     * POST /api/auth/request-otp
     * Body: { "phone": "+251911639555", "purpose": "registration"|"login" }
     */
    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        try {
            $this->otpService->generate(
                $request->validated('phone'),
                $request->validated('purpose'),
            );

            return response()->json([
                'message' => 'Verification code sent.',
            ]);
        } catch (SmsDeliveryException $e) {
            return response()->json([
                'message' => 'Unable to send verification code right now, please try again.',
            ], 503);
        }
    }

    /**
     * Verify an OTP code and issue a Sanctum token.
     *
     * POST /api/auth/verify-otp
     * Body: { "phone": "+251911639555", "code": "123456", "purpose": "registration"|"login", "name": "..." }
     *
     * For registration: creates the user if they don't exist.
     * For login: the user must already exist.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $phone   = $request->validated('phone');
        $code    = $request->validated('code');
        $purpose = $request->validated('purpose');

        $valid = $this->otpService->verify($phone, $code, $purpose);

        if (! $valid) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        if ($purpose === 'registration') {
            $user = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'name'              => $request->validated('name'),
                    'phone_verified_at' => now(),
                ],
            );

            // In case the user already existed but wasn't verified
            if (! $user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
            }
        } else {
            // Login — user must exist
            $user = User::where('phone', $phone)->first();

            if (! $user) {
                return response()->json([
                    'message' => 'No account found for this phone number.',
                ], 404);
            }

            // Mark verified if not already
            if (! $user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
            }
        }

        // Issue a Sanctum token
        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Phone verified successfully.',
            'token'   => $token,
            'user'    => $user,
        ]);
    }
}
