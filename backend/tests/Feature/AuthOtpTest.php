<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sms_ethiopia.base_url' => 'https://smsethiopia.et/api',
            'services.sms_ethiopia.key'      => 'test-api-key-123',
        ]);
    }

    // ----------------------------------------------------------------
    // POST /api/auth/request-otp
    // ----------------------------------------------------------------

    public function test_request_otp_returns_200_on_success(): void
    {
        Http::fake([
            'smsethiopia.et/*' => Http::response([
                'status'  => 'success',
                'message' => 'SMS sent successfully',
            ], 200),
        ]);

        $response = $this->postJson('/api/auth/request-otp', [
            'phone'   => '+251911639555',
            'purpose' => 'registration',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Verification code sent.']);

        $this->assertDatabaseCount('otp_verifications', 1);
    }

    public function test_request_otp_returns_503_when_sms_fails(): void
    {
        Http::fake([
            'smsethiopia.et/*' => Http::response([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401),
        ]);

        $response = $this->postJson('/api/auth/request-otp', [
            'phone'   => '+251911639555',
            'purpose' => 'login',
        ]);

        $response->assertStatus(503)
            ->assertJson([
                'message' => 'Unable to send verification code right now, please try again.',
            ]);

        // Option A: no OTP row left behind
        $this->assertDatabaseCount('otp_verifications', 0);
    }

    public function test_request_otp_validates_phone_format(): void
    {
        Http::fake();

        $response = $this->postJson('/api/auth/request-otp', [
            'phone'   => '0911639555', // wrong format, missing +251
            'purpose' => 'login',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_request_otp_validates_purpose(): void
    {
        Http::fake();

        $response = $this->postJson('/api/auth/request-otp', [
            'phone'   => '+251911639555',
            'purpose' => 'password_reset', // invalid purpose
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['purpose']);
    }

    public function test_request_otp_requires_all_fields(): void
    {
        Http::fake();

        $response = $this->postJson('/api/auth/request-otp', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'purpose']);
    }

    // ----------------------------------------------------------------
    // POST /api/auth/verify-otp
    // ----------------------------------------------------------------

    public function test_verify_otp_returns_token_on_valid_registration(): void
    {
        Http::fake();

        $code = '654321';
        OtpVerification::create([
            'phone'      => '+251911639555',
            'code'       => Hash::make($code),
            'purpose'    => 'registration',
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone'   => '+251911639555',
            'code'    => $code,
            'purpose' => 'registration',
            'name'    => 'Aymen Test',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'token', 'user'])
            ->assertJson(['message' => 'Phone verified successfully.']);

        $this->assertDatabaseHas('users', [
            'phone' => '+251911639555',
            'name'  => 'Aymen Test',
        ]);
    }

    public function test_verify_otp_returns_token_on_valid_login(): void
    {
        Http::fake();

        // Pre-create the user
        User::create([
            'name'  => 'Existing User',
            'phone' => '+251911639555',
        ]);

        $code = '654321';
        OtpVerification::create([
            'phone'      => '+251911639555',
            'code'       => Hash::make($code),
            'purpose'    => 'login',
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone'   => '+251911639555',
            'code'    => $code,
            'purpose' => 'login',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'token', 'user']);
    }

    public function test_verify_otp_returns_error_on_invalid_code(): void
    {
        Http::fake();

        OtpVerification::create([
            'phone'      => '+251911639555',
            'code'       => Hash::make('123456'),
            'purpose'    => 'login',
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        User::create([
            'name'  => 'Test User',
            'phone' => '+251911639555',
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone'   => '+251911639555',
            'code'    => '999999', // wrong code
            'purpose' => 'login',
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Invalid or expired verification code.']);
    }

    public function test_verify_otp_returns_404_for_login_with_nonexistent_user(): void
    {
        Http::fake();

        $code = '123456';
        OtpVerification::create([
            'phone'      => '+251911639555',
            'code'       => Hash::make($code),
            'purpose'    => 'login',
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        // No user exists for this phone
        $response = $this->postJson('/api/auth/verify-otp', [
            'phone'   => '+251911639555',
            'code'    => $code,
            'purpose' => 'login',
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'No account found for this phone number.']);
    }

    public function test_verify_otp_validates_code_format(): void
    {
        Http::fake();

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone'   => '+251911639555',
            'code'    => 'abc', // not digits, not 6 chars
            'purpose' => 'login',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_verify_otp_requires_name_for_registration(): void
    {
        Http::fake();

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone'   => '+251911639555',
            'code'    => '123456',
            'purpose' => 'registration',
            // name missing
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
