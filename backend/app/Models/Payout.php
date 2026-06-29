<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = [
        'farmer_id',
        'order_fulfillment_id',
        'amount',
        'status',
        'reference',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function fulfillment()
    {
        return $this->belongsTo(OrderFulfillment::class, 'order_fulfillment_id');
    }
}
