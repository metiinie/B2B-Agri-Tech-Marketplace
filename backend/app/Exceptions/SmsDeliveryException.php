<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when SMS delivery fails during OTP generation.
 *
 * The controller catches this to return a clean 503 error
 * instead of letting the user wait for a code that was never sent.
 */
class SmsDeliveryException extends RuntimeException
{
    public function __construct(string $phone)
    {
        parent::__construct(
            "Failed to deliver SMS to {$phone}"
        );
    }
}
 