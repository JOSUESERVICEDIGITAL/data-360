<?php

namespace App\Models\Back;
use App\Models\Back\VisitorDevice\VisitorDevice;
use Illuminate\Database\Eloquent\Model;
use App\Models\Back\CreditTransaction\CreditTransaction;

class SearchAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'visitor_device_id',
        'query',
        'normalized_address',
        'ip_address',
        'fingerprint_hash',
        'is_authenticated',
        'is_free_search',
        'credit_consumed',
        'success',
        'status',
        'is_vpn',
        'is_proxy',
        'is_tor',
        'is_datacenter',
        'risk_score',
        'block_reason',
        'result_summary',
        'raw_security_data',
    ];

    protected $casts = [
        'is_authenticated' => 'boolean',
        'is_free_search' => 'boolean',
        'credit_consumed' => 'boolean',
        'success' => 'boolean',
        'is_vpn' => 'boolean',
        'is_proxy' => 'boolean',
        'is_tor' => 'boolean',
        'is_datacenter' => 'boolean',
        'result_summary' => 'array',
        'raw_security_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function visitorDevice()
    {
        return $this->belongsTo(VisitorDevice::class);
    }
}