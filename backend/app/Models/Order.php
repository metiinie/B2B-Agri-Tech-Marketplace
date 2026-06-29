<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'buyer_id',
        'status',
        'total_amount',
        'currency',
        'placed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'placed_at' => 'datetime',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function fulfillments()
    {
        return $this->hasMany(OrderFulfillment::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
