<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'farmer_id',
        'category_id',
        'title',
        'description',
        'unit',
        'price_per_unit',
        'quantity_available',
        'quantity_reserved',
        'status',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'quantity_available' => 'decimal:3',
        'quantity_reserved' => 'decimal:3',
        'deleted_at' => 'datetime',
    ];

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function priceHistory()
    {
        return $this->hasMany(ListingPriceHistory::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
            ->where('quantity_available', '>', 0);
    }
}
