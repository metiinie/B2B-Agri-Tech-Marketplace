<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapabilityApplication extends Model
{
    protected $fillable = [
        'user_id',
        'capability_type',
        'status',
        'supporting_documents',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'supporting_documents' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function capabilityGrant()
    {
        return $this->hasOne(UserCapability::class);
    }
}
