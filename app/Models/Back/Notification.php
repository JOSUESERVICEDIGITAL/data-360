<?php
// app/Models/Back/Notification.php

namespace App\Models\Back;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'icon',
        'link',
        'is_read',
        'is_global',
        'meta',
        'read_at',
        'expires_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_global' => 'boolean',
        'meta' => 'array',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('is_global', true);
        });
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public static function types(): array
    {
        return [
            'admin' => ['icon' => 'fa-solid fa-shield-halved', 'color' => '#8b5cf6'],
            'info' => ['icon' => 'fa-solid fa-circle-info', 'color' => '#3b82f6'],
            'success' => ['icon' => 'fa-solid fa-circle-check', 'color' => '#10b981'],
            'warning' => ['icon' => 'fa-solid fa-triangle-exclamation', 'color' => '#f59e0b'],
            'danger' => ['icon' => 'fa-solid fa-circle-exclamation', 'color' => '#ef4444'],
        ];
    }
}