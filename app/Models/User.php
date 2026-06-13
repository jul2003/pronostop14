<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\JourneeUserScore;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'role',
        'color',
        'nickname',
        'email_pro',
        'email_perso',
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
            'last_login_at' => 'datetime',
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
}
