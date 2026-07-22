<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS via the SMS Ethiopia gateway.
     *
     * Returns true only when the gateway confirms delivery ("status": "success").
     * Returns false (never throws) on HTTP errors or network-level exceptions,
     * so callers can decide their own failure behavior.
     *
     * Security: $text is never logged — it may contain the OTP code.
     */
    public function send(string $msisdn, string $text): bool
    {
        try {

           $response = Http::withoutVerifying()
    ->withHeaders([
        'KEY' => config('services.sms_ethiopia.key'),
        'Content-Type' => 'application/json',
            ])
                ->timeout(10)
                ->post(config('services.sms_ethiopia.base_url') . '/sms/send', [
                    'msisdn' => $this->normalizeMsisdn($msisdn),
                    'text'   => $text,
                ]);

            if ($response->failed()) {
                Log::error('SMS Ethiopia send failed', [
                    'status' => $response->status(),
                    'body'   => $response->json(),
                    'msisdn' => $msisdn,
                ]);

                return false;
            }

            return $response->json('sent') === true;
        } catch (\Throwable $e) {
            Log::error('SMS Ethiopia send exception', [
                'message' => $e->getMessage(),
                'msisdn'  => $msisdn,
            ]);

            return false;
        }
    }

    /**
     * Strip the leading '+' from an E.164 phone number.
     *
     * Input is +251XXXXXXXXX per RequestOtpRequest validation.
     * SMS Ethiopia expects digits only (e.g. 251911639555).
     */
    private function normalizeMsisdn(string $phone): string
    {
        return ltrim($phone, '+');
    }
}
