<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCapability extends Model
{
    protected $fillable = [
        'user_id',
        'capability_type',
        'capability_application_id',
        'status',
        'granted_by',
        'granted_at',
        'revoked_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function application()
    {
        return $this->belongsTo(CapabilityApplication::class, 'capability_application_id');
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
