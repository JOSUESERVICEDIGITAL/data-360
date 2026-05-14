<?php

namespace App\Models\Back;
use App\Models\Back\VisitorDevice\VisitorDevice;
use App\Models\Back\SearchAttempt\SearchAttempt;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'amount',
        'type',
        'reason',
        'payment_reference',
        'balance_before',
        'balance_after',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}