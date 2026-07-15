<?php

namespace Tests\Unit;

use App\Exceptions\SmsDeliveryException;
use App\Models\OtpVerification;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sms_ethiopia.base_url' => 'https://smsethiopia.et/api',
            'services.sms_ethiopia.key'      => 'test-api-key-123',
        ]);
    }

    private function makeOtpService(?SmsService $smsService = null): OtpService
    {
        return new OtpService($smsService ?? app(SmsService::class));
    }

    public function test_generate_creates_otp_row_and_sends_sms(): void
    {
        Http::fake([
            'smsethiopia.et/*' => Http::response([
                'status'  => 'success',
                'message' => 'SMS sent successfully',
            ], 200),
        ]);

        $service = $this->makeOtpService();
        $service->generate('+251911639555', 'registration');

        $this->assertDatabaseCount('otp_verifications', 1);
        $this->assertDatabaseHas('otp_verifications', [
            'phone'   => '+251911639555',
            'purpose' => 'registration',
        ]);

        // Verify the SMS was actually sent
        Http::assertSentCount(1);
    }

    public function test_generate_throws_and_deletes_row_when_sms_fails(): void
    {
        Http::fake([
            'smsethiopia.et/*' => Http::response([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401),
        ]);

        $service = $this->makeOtpService();

        $this->expectException(SmsDeliveryException::class);

        try {
            $service->generate('+251911639555', 'login');
        } finally {
            // Option A: row must NOT remain in the database
            $this->assertDatabaseCount('otp_verifications', 0);
        }
    }

    public function test_generate_invalidates_previous_otps(): void
    {
        Http::fake([
            'smsethiopia.et/*' => Http::response([
                'status'  => 'success',
                'message' => 'SMS sent successfully',
            ], 200),
        ]);

        // Create a pre-existing OTP
        OtpVerification::create([
            'phone'      => '+251911639555',
            'code'       => Hash::make('111111'),
            'purpose'    => 'login',
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->assertDatabaseCount('otp_verifications', 1);

        $service = $this->makeOtpService();
        $service->generate('+251911639555', 'login');

        // Old one deleted, new one created = still 1 row
        $this->assertDatabaseCount('otp_verifications', 1);
    }

    public function test_verify_succeeds_with_correct_code(): void
    {
        $code = '123456';
        OtpVerification::create([
            'phone'      => '+251911639555',
            'code'       => Hash::make($code),
            'purpose'    => 'login',
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        Http::fake(); // not used here, but required per security constraint
        $service = $this->makeOtpService();

        $this->assertTrue($service->verify('+251911639555', $code, 'login'));

        // Should be marked as consumed
        $this->assertDatabaseHas('otp_verifications', [
            'phone' => '+251911639555',
        ]);
        $otp = OtpVerification::where('phone', '+251911639555')->first();
        $this->assertNotNull($otp->consumed_at);
    }

    public function test_verify_fails_with_wrong_code(): void
    {
        OtpVerification::create([
            'phone'      => '+251911639555',
            'code'       => Hash::make('123456'),
            'purpose'    => 'login',
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        Http::fake();
        $service = $this->makeOtpService();

        $this->assertFalse($service->verify('+251911639555', '999999', 'login'));
    }

    public function test_verify_fails_when_expired(): void
    {
        OtpVerification::create([
            'phone'      => '+251911639555',
            'code'       => Hash::make('123456'),
            'purpose'    => 'login',
            'attempts'   => 0,
            'expires_at' => now()->subMinute(), // already expired
        ]);

        Http::fake();
        $service = $this->makeOtpService();

        $this->assertFalse($service->verify('+251911639555', '123456', 'login'));
    }

    public function test_verify_fails_after_max_attempts(): void
    {
        OtpVerification::create([
            'phone'      => '+251911639555',
            'code'       => Hash::make('123456'),
            'purpose'    => 'login',
            'attempts'   => 5, // already at max
            'expires_at' => now()->addMinutes(5),
        ]);

        Http::fake();
        $service = $this->makeOtpService();

        $this->assertFalse($service->verify('+251911639555', '123456', 'login'));
    }
}
