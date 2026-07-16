<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'second_name',
        'phone',
        'phone_verified_at',
        'password',
        'account_status',
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'deleted_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function otpVerifications()
    {
        return $this->hasMany(OtpVerification::class, 'phone', 'phone');
    }

    public function capabilityApplications()
    {
        return $this->hasMany(CapabilityApplication::class);
    }

    public function capabilities()
    {
        return $this->hasMany(UserCapability::class);
    }

    public function listings()
    {
        return $this->hasMany(Listing::class, 'farmer_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'buyer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function orderFulfillments()
    {
        return $this->hasMany(OrderFulfillment::class, 'farmer_id');
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class, 'farmer_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
