<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',

        'is_admin',
        'is_active',
        'credits',
        'plan',
        'phone',
        'phone_verified_at',
        'last_login_ip',
        'last_login_at',
        'otp_bypass',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',

    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'credits' => 'integer',
            'otp_bypass' => 'boolean',
        ];
    }

    public function visitorDevices()
    {
        return $this->hasMany(\App\Models\VisitorDevice::class);
    }

    public function searchAttempts()
    {
        return $this->hasMany(\App\Models\SearchAttempt::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(\App\Models\CreditTransaction::class);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function hasCredits(): bool
    {
        return $this->is_admin || $this->credits > 0;
    }

    public function consumeCredit(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        if ($this->credits <= 0) {
            return false;
        }

        $this->decrement('credits');

        return true;
    }
}
