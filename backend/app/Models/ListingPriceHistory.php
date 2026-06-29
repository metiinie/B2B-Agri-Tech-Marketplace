<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingPriceHistory extends Model
{
    protected $table = 'listing_price_history';

    protected $fillable = [
        'listing_id',
        'price_per_unit',
        'changed_by',
        'effective_at',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'effective_at' => 'datetime',
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
