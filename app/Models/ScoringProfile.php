<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoringProfile extends Model
{
    //
    protected $fillable = [
        'code',
        'name',
        'description',
        'position',
    ];

    public function rules()
    {
        return $this->hasMany(ScoringRuleTemplate::class)
            ->orderBy('position');
    }
}
