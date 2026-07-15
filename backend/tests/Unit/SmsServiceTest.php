<?php

namespace Tests\Unit;

use App\Services\SmsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    private SmsService $sms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sms = new SmsService();

        // Ensure config values are set for all tests
        config([
            'services.sms_ethiopia.base_url' => 'https://smsethiopia.et/api',
            'services.sms_ethiopia.key'      => 'test-api-key-123',
        ]);
    }

    public function test_send_returns_true_on_success_response(): void
    {
        Http::fake([
            'smsethiopia.et/*' => Http::response([
                'status'  => 'success',
                'message' => 'SMS sent successfully',
            ], 200),
        ]);

        $result = $this->sms->send('+251911639555', 'Hello World');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://smsethiopia.et/api/sms/send'
                && $request->header('KEY')[0] === 'test-api-key-123'
                && $request['msisdn'] === '251911639555'  // + stripped
                && $request['text'] === 'Hello World';
        });
    }

    public function test_send_returns_false_on_401_response(): void
    {
        Http::fake([
            'smsethiopia.et/*' => Http::response([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401),
        ]);

        Log::spy();

        $result = $this->sms->send('+251911639555', 'Test');

        $this->assertFalse($result);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'SMS Ethiopia send failed'
                    && $context['status'] === 401;
            });
    }

    public function test_send_returns_false_on_400_response(): void
    {
        Http::fake([
            'smsethiopia.et/*' => Http::response([
                'status'  => 'error',
                'message' => 'Bad request',
            ], 400),
        ]);

        Log::spy();

        $result = $this->sms->send('+251911639555', 'Test');

        $this->assertFalse($result);

        Log::shouldHaveReceived('error')->once();
    }

    public function test_send_returns_false_on_500_response(): void
    {
        Http::fake([
            'smsethiopia.et/*' => Http::response([
                'status'  => 'error',
                'message' => 'Internal server error',
            ], 500),
        ]);

        Log::spy();

        $result = $this->sms->send('+251911639555', 'Test');

        $this->assertFalse($result);

        Log::shouldHaveReceived('error')->once();
    }

    public function test_send_returns_false_on_connection_exception(): void
    {
        Http::fake([
            'smsethiopia.et/*' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        Log::spy();

        $result = $this->sms->send('+251911639555', 'Test');

        $this->assertFalse($result);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'SMS Ethiopia send exception'
                    && str_contains($context['message'], 'Connection refused');
            });
    }

    public function test_send_strips_plus_from_msisdn(): void
    {
        Http::fake([
            'smsethiopia.et/*' => Http::response([
                'status'  => 'success',
                'message' => 'SMS sent successfully',
            ], 200),
        ]);

        $this->sms->send('+251911639555', 'Hello');

        Http::assertSent(function ($request) {
            return $request['msisdn'] === '251911639555';
        });
    }

    public function test_send_uses_correct_key_header(): void
    {
        Http::fake([
            'smsethiopia.et/*' => Http::response([
                'status'  => 'success',
                'message' => 'SMS sent successfully',
            ], 200),
        ]);

        $this->sms->send('+251911639555', 'Hello');

        Http::assertSent(function ($request) {
            // SMS Ethiopia uses 'KEY' header, not 'Authorization: Bearer'
            return $request->hasHeader('KEY')
                && $request->header('KEY')[0] === 'test-api-key-123';
        });
    }
}
