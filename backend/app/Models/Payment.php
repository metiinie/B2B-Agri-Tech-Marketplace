<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'chapa_tx_ref',
        'chapa_checkout_url',
        'amount',
        'currency',
        'status',
        'confirmed_at',
        'gateway_metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'gateway_metadata' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function webhookEvents()
    {
        return $this->hasMany(PaymentWebhookEvent::class);
    }

    public function exceptions()
    {
        return $this->hasMany(PaymentException::class);
    }
}
