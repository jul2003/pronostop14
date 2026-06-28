<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonPreseasonCorrectionGroup extends Model
{
    protected $fillable = [
        'season_id',
        'source_template_id',
        'label',
        'code',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function sourceTemplate()
    {
        return $this->belongsTo(PreseasonCorrectionGroupTemplate::class, 'source_template_id');
    }

    public function questions()
    {
        return $this->belongsToMany(
            SeasonPreseasonQuestion::class,
            'season_preseason_correction_group_questions',
            'season_preseason_correction_group_id',
            'season_preseason_question_id'
        )
            ->withTimestamps()
            ->orderBy('position');
    }
}
