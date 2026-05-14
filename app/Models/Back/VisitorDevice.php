<?php

namespace App\Models\Back   ;

use Illuminate\Database\Eloquent\Model;

class VisitorDevice extends Model
{
    protected $fillable = [
        'user_id',
        'fingerprint_hash',
        'ip_address',
        'user_agent_hash',
        'user_agent',
        'browser',
        'platform',
        'timezone',
        'language',
        'free_searches_used',
        'free_searches_reset_at',
        'is_vpn',
        'is_proxy',
        'is_tor',
        'is_datacenter',
        'risk_score',
        'is_blocked',
        'block_reason',
        'last_seen_at',
        'raw_data',
    ];

    protected $casts = [
        'free_searches_reset_at' => 'datetime',
        'is_vpn' => 'boolean',
        'is_proxy' => 'boolean',
        'is_tor' => 'boolean',
        'is_datacenter' => 'boolean',
        'is_blocked' => 'boolean',
        'last_seen_at' => 'datetime',
        'raw_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function searchAttempts()
    {
        return $this->hasMany(SearchAttempt::class);
    }

    public function hasFreeSearchesLeft(): bool
    {
        return $this->free_searches_used < 2;
    }

    public function incrementFreeSearch(): void
    {
        $this->increment('free_searches_used');
    }

    public function shouldBlockForNetwork(): bool
    {
        return $this->is_blocked
            || $this->is_vpn
            || $this->is_proxy
            || $this->is_tor
            || $this->is_datacenter;
    }
}