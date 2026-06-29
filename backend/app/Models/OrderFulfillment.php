<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderFulfillment extends Model
{
    protected $fillable = [
        'order_id',
        'farmer_id',
        'status',
        'subtotal_amount',
        'farmer_notes',
        'accepted_at',
        'rejected_at',
        'completed_at',
    ];

    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payout()
    {
        return $this->hasOne(Payout::class);
    }
}
