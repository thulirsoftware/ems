<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentAttempt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'user_id',
        'started_at',
        'submitted_at',
        'score',
        'question_order'
    ];

    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'question_order' => 'array',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(AssessmentAnswer::class, 'attempt_id');
    }
}
