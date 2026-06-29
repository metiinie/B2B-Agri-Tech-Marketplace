<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'payment_id',
        'chapa_tx_ref',
        'event_type',
        'chapa_event_id',
        'payload',
        'signature_verified',
        'processing_status',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_verified' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
