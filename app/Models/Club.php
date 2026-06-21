<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Club extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'slug',
        //'logo_path',
        //'lnr_url',
    ];

    protected static function booted(): void
    {
        static::creating(function ($club) {
            $club->slug = Str::slug($club->name);
        });

        static::updating(function ($club) {
            $club->slug = Str::slug($club->name);
        });
    }

    public function seasons()
    {
        return $this->belongsToMany(Season::class)
            ->withPivot('competition')
            ->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getLogoUrlAttribute(): string
    {
        $path = public_path('images/clubs/'.$this->slug.'.png');

        if (file_exists($path)) {
            return asset('images/clubs/'.$this->slug.'.png');
        }

        return asset('images/clubs/default.png');
    }
}
