<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'buyer_id',
        'listing_id',
        'quantity',
        'price_snapshot',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'price_snapshot' => 'decimal:2',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
