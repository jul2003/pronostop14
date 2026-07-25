<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'color',
        'nickname',
        'email_pro',
        'email_perso',
        'last_login_at',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (self::count() === 0) {
                $user->role = 'super_admin';
            }
        });
    }

    public function pronos()
    {
        return $this->hasMany(Prono::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin'], true);
    }

    public function isPlayer(): bool
    {
        return $this->role === 'player';
    }

    public function journeeScores()
    {
        return $this->hasMany(JourneeUserScore::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->nickname ?: $this->name;
    }

    public function seasons()
    {
        return $this->belongsToMany(Season::class)
            ->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'nickname';
    }

    public function routeNotificationForMail($notification = null)
    {
        return $this->email;
    }
}
