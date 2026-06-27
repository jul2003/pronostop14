<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoringProfile extends Model
{
    protected $fillable = [
        'code',
        'category',
        'stop_on_wrong_result',
        'name',
        'description',
        'position',
    ];

    protected $casts = [
        'stop_on_wrong_result' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function rules()
    {
        return $this->hasMany(ScoringRuleTemplate::class)
            ->orderBy('position');
    }
}
