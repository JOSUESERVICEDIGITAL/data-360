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

    // ────────────────────────────────────────────────────────────
    // Mass assignable
    // ────────────────────────────────────────────────────────────
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_admin',
        'is_superadmin',
        'is_active',
        'credits',
        'plan',
        'otp_bypass',
        'email_verified_at',
        'last_login_ip',
        'last_login_at',
    ];

    // ────────────────────────────────────────────────────────────
    // Hidden
    // ────────────────────────────────────────────────────────────
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ────────────────────────────────────────────────────────────
    // Casts
    // ────────────────────────────────────────────────────────────
    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'phone_verified_at'  => 'datetime',
            'last_login_at'      => 'datetime',
            'password'           => 'hashed',
            'is_admin'           => 'boolean',
            'is_superadmin'      => 'boolean',
            'is_active'          => 'boolean',
            'otp_bypass'         => 'boolean',
            'credits'            => 'integer',
        ];
    }

    // ────────────────────────────────────────────────────────────
    // Relations
    // ────────────────────────────────────────────────────────────
    public function visitorDevices()
    {
        return $this->hasMany(\App\Models\Back\VisitorDevice::class);
    }

    public function searchAttempts()
    {
        return $this->hasMany(\App\Models\Back\SearchAttempt::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(\App\Models\Back\CreditTransaction::class);
    }

    // ────────────────────────────────────────────────────────────
    // Helpers de rôle
    // ────────────────────────────────────────────────────────────

    /**
     * Superadmin — accès total, au-dessus de tout
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    /**
     * Admin — accès backoffice
     * Un superadmin est automatiquement admin
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin || (bool) $this->is_superadmin;
    }

    /**
     * Plan premium ou enterprise
     */
    public function hasPremiumAccess(): bool
    {
        return in_array($this->plan, ['premium', 'enterprise']);
    }

    /**
     * A des crédits disponibles
     * Les admins et superadmins ont toujours accès
     */
    public function hasCredits(): bool
    {
        return $this->isAdmin() || $this->credits > 0;
    }

    /**
     * Consomme un crédit
     * Les admins et superadmins ne consomment jamais de crédits
     */
    public function consumeCredit(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->credits <= 0) {
            return false;
        }

        $this->decrement('credits');
        return true;
    }
}
