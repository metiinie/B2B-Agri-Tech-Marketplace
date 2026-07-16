<?php

namespace App\Http\Controllers;

use App\Exceptions\SmsDeliveryException;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RequestOtpRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
    ) {}

    /**
     * Request an OTP code for registration (phone ownership verification).
     *
     * POST /api/auth/request-otp
     * Body: { "phone": "+251911639555" }
     */
    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        try {
            $this->otpService->generate(
                $request->validated('phone'),
                'registration',
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
     * Register a new user after OTP verification.
     *
     * POST /api/auth/register
     * Body: { "first_name": "...", "second_name": "...", "phone": "+251...", "password": "...", "password_confirmation": "...", "code": "123456" }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $phone = $request->validated('phone');
        $code  = $request->validated('code');

        $valid = $this->otpService->verify($phone, $code, 'registration');

        if (! $valid) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        $user = User::create([
            'first_name'        => $request->validated('first_name'),
            'second_name'       => $request->validated('second_name'),
            'phone'             => $phone,
            'password'          => $request->validated('password'),
            'phone_verified_at' => now(),
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    /**
     * Login with phone and password.
     *
     * POST /api/auth/login
     * Body: { "phone": "+251911639555", "password": "..." }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid phone number or password.',
            ], 401);
        }

        /** @var User $user */
        $user  = Auth::user();
        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => $user,
        ]);
    }
}
