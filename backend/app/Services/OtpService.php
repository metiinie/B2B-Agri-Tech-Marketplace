<?php

namespace App\Services;

use App\Exceptions\SmsDeliveryException;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function __construct(
        private readonly SmsService $smsService,
    ) {}

    /**
     * Generate, store, and send an OTP code to the given phone.
     *
     * - Invalidates any previous unconsumed OTPs for the same phone+purpose.
     * - Creates a new OtpVerification row with a hashed code and 5-minute expiry.
     * - Sends the plaintext code via SMS Ethiopia.
     * - On SMS failure: deletes the row and throws SmsDeliveryException (Option A).
     *
     * @throws SmsDeliveryException
     */////////////
    public function generate(string $phone, string $purpose = 'login'): void
    {
        // Invalidate any previous unconsumed OTPs for this phone + purpose
        OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->delete();

        // Generate a random 6-digit code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store hashed — never keep plaintext in the database
        $otp = OtpVerification::create([
            'phone'      => $phone,
            'code'       => Hash::make($code),
            'purpose'    => $purpose,
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Send the SMS with the plaintext code
        $sent = $this->smsService->send(
            $phone,
            "Your verification code is {$code}"
        );

        // Option A: if send fails, clean up the row and throw
        if (! $sent) {
            $otp->delete();
            throw new SmsDeliveryException($phone);
        }
    }

    /**
     * Verify an OTP code for the given phone and purpose.
     *
     * Checks expiry, attempt limits (max 5), and hash match.
     * Marks the OTP as consumed on success.
     */
    public function verify(string $phone, string $code, string $purpose = 'login'): bool
    {
        $otp = OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return false;
        }

        // Enforce max 5 attempts
        if ($otp->attempts >= 5) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code)) {
            return false;
        }

        // Mark as consumed
        $otp->update(['consumed_at' => now()]);

        return true;
    }
}
