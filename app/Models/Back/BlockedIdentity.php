<?php

namespace App\Models\Back;
use App\Models\Back\VisitorDevice;
use App\Models\Back\SearchAttempt\SearchAttempt;
use App\Models\Back\CreditTransaction\CreditTransaction;


use Illuminate\Database\Eloquent\Model;

class BlockedIdentity extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'value',
        'reason',
        'is_active',
        'expires_at',
        'blocked_by',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function blocker()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}