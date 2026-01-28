<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'type',
        'question_text',
        'config',
        'order',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function choices()
    {
        return $this->hasMany(AssessmentChoice::class, 'question_id');
    }
}
