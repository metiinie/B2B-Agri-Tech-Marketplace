<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChapaWebhookController extends Controller
{
    /**
     * Handle inbound Chapa webhook events.
     *
     * POST /api/payments/webhook
     *
     * This is the ONLY code path in the application that is allowed to
     * transition payments.status from "pending" to "confirmed".
     * (See MANDATORY CORE DATABASE LOGIC RULE in the documentation.)
     *
     * Flow:
     *  1. Verify the webhook signature using the shared secret.
     *  2. Log the raw event into payment_webhook_events (received).
     *  3. Check idempotency — skip if the same tx_ref+event_type was already processed.
     *  4. Look up the payment by chapa_tx_ref.
     *  5. If the event indicates success, confirm the payment and advance the order status.
     *  6. If the event indicates failure, mark the payment as failed.
     *  7. Mark the webhook event as processed.
     *
     * Chapa expects a 200 response; any non-2xx will trigger retries.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload   = $request->all();
        $txRef     = $payload['tx_ref'] ?? $payload['trx_ref'] ?? null;
        $eventType = $payload['event'] ?? $payload['type'] ?? 'charge.completed';
        $chapaEventId = $payload['event_id'] ?? $payload['id'] ?? null;

        // ------------------------------------------------------------------
        // 1. Signature verification
        // ------------------------------------------------------------------
        $signatureVerified = $this->verifySignature($request);

        if (! $signatureVerified) {
            Log::warning('Chapa webhook: invalid signature', [
                'tx_ref'  => $txRef,
                'ip'      => $request->ip(),
            ]);

            // Log the event even on bad signature so it's auditable.
            $this->logWebhookEvent(null, $txRef, $eventType, $chapaEventId, $payload, false, 'failed');

            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        if (! $txRef) {
            Log::warning('Chapa webhook: missing tx_ref in payload.');

            return response()->json(['message' => 'Missing transaction reference.'], 400);
        }

        // ------------------------------------------------------------------
        // 2. Look up payment
        // ------------------------------------------------------------------
        $payment = Payment::where('chapa_tx_ref', $txRef)->first();

        // ------------------------------------------------------------------
        // 3. Log the raw event (always, before any state mutation)
        // ------------------------------------------------------------------
        $webhookEvent = $this->logWebhookEvent(
            $payment?->id,
            $txRef,
            $eventType,
            $chapaEventId,
            $payload,
            true,
            'received',
        );

        if (! $payment) {
            Log::error('Chapa webhook: no payment found for tx_ref', ['tx_ref' => $txRef]);

            $webhookEvent->update([
                'processing_status' => 'failed',
                'processed_at'      => now(),
            ]);

            // Return 200 so Chapa does not keep retrying for a tx_ref we'll never match.
            return response()->json(['message' => 'Payment not found.']);
        }

        // ------------------------------------------------------------------
        // 4. Idempotency check
        // ------------------------------------------------------------------
        $alreadyProcessed = PaymentWebhookEvent::where('chapa_tx_ref', $txRef)
            ->where('event_type', $eventType)
            ->where('processing_status', 'processed')
            ->where('id', '!=', $webhookEvent->id)
            ->exists();

        if ($alreadyProcessed) {
            $webhookEvent->update([
                'processing_status' => 'duplicate_ignored',
                'processed_at'      => now(),
            ]);

            return response()->json(['message' => 'Duplicate event ignored.']);
        }

        // ------------------------------------------------------------------
        // 5. Process the event
        // ------------------------------------------------------------------
        try {
            DB::transaction(function () use ($payment, $payload, $eventType, $webhookEvent) {
                $status = $payload['status'] ?? null;

                if ($this->isSuccessEvent($eventType, $status)) {
                    $this->confirmPayment($payment, $payload);
                } elseif ($this->isFailureEvent($eventType, $status)) {
                    $this->failPayment($payment, $payload);
                }

                $webhookEvent->update([
                    'payment_id'        => $payment->id,
                    'processing_status' => 'processed',
                    'processed_at'      => now(),
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Chapa webhook: processing error', [
                'tx_ref'  => $txRef,
                'error'   => $e->getMessage(),
            ]);

            $webhookEvent->update([
                'processing_status' => 'failed',
                'processed_at'      => now(),
            ]);

            // Return 200 to prevent infinite retries; the failed status is logged for admin review.
            return response()->json(['message' => 'Processing error recorded.']);
        }

        return response()->json(['message' => 'Webhook processed successfully.']);
    }

    /**
     * Verify the Chapa webhook signature against the shared secret.
     *
     * Chapa sends a hash in the "Chapa-Signature" header (or "x-chapa-signature").
     * We compute HMAC-SHA256 of the raw body with our webhook secret and compare.
     */
    private function verifySignature(Request $request): bool
    {
        $secret = config('services.chapa.webhook_secret');

        // If no webhook secret is configured, skip verification in local/testing.
        // In production this should ALWAYS be set.
        if (empty($secret)) {
            Log::warning('Chapa webhook: CHAPA_WEBHOOK_SECRET is not configured — skipping signature verification.');

            return true;
        }

        $signature = $request->header('Chapa-Signature')
            ?? $request->header('x-chapa-signature');

        if (! $signature) {
            return false;
        }

        $computedHash = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($computedHash, $signature);
    }

    /**
     * Log a webhook event into payment_webhook_events for audit and idempotency.
     */
    private function logWebhookEvent(
        ?int $paymentId,
        ?string $txRef,
        string $eventType,
        ?string $chapaEventId,
        array $payload,
        bool $signatureVerified,
        string $processingStatus,
    ): PaymentWebhookEvent {
        return PaymentWebhookEvent::create([
            'payment_id'         => $paymentId,
            'chapa_tx_ref'       => $txRef ?? 'unknown',
            'event_type'         => $eventType,
            'chapa_event_id'     => $chapaEventId,
            'payload'            => $payload,
            'signature_verified' => $signatureVerified,
            'processing_status'  => $processingStatus,
        ]);
    }

    /**
     * Confirm the payment and advance the order status.
     *
     * MANDATORY CORE DATABASE LOGIC RULE:
     * This is the ONLY place in the entire application where
     * payments.status may be set to "confirmed".
     */
    private function confirmPayment(Payment $payment, array $payload): void
    {
        if ($payment->status !== 'pending') {
            return;
        }

        $payment->update([
            'status'           => 'confirmed',
            'confirmed_at'     => now(),
            'gateway_metadata' => $this->extractSafeMetadata($payload),
        ]);

        // Advance the order status from pending_payment → payment_confirmed.
        $order = $payment->order;

        if ($order && $order->status === 'pending_payment') {
            $order->update(['status' => 'payment_confirmed']);
        }
    }

    /**
     * Mark the payment as failed.
     */
    private function failPayment(Payment $payment, array $payload): void
    {
        if ($payment->status !== 'pending') {
            return;
        }

        $payment->update([
            'status'           => 'failed',
            'gateway_metadata' => $this->extractSafeMetadata($payload),
        ]);
    }

    /**
     * Determine if the event represents a successful payment.
     */
    private function isSuccessEvent(string $eventType, ?string $status): bool
    {
        // Chapa sends event "charge.completed" / "charge.success" with status "success".
        if (in_array($eventType, ['charge.completed', 'charge.success'], true)) {
            return true;
        }

        if ($status && strtolower($status) === 'success') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the event represents a failed payment.
     */
    private function isFailureEvent(string $eventType, ?string $status): bool
    {
        if (in_array($eventType, ['charge.failed', 'charge.declined'], true)) {
            return true;
        }

        if ($status && in_array(strtolower($status), ['failed', 'declined', 'expired'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Extract only non-sensitive metadata from the Chapa payload.
     *
     * Per documentation: "sensitive payment data never stored in the application."
     * We strip anything that looks like card/wallet/account data.
     */
    private function extractSafeMetadata(array $payload): array
    {
        $safeKeys = [
            'tx_ref', 'trx_ref', 'status', 'currency', 'amount',
            'charge', 'mode', 'method', 'type', 'created_at',
            'updated_at', 'reference', 'event', 'event_id',
            'customization', 'first_name', 'last_name',
        ];

        return array_intersect_key($payload, array_flip($safeKeys));
    }
}
