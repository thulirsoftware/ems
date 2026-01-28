<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentAnswer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'answer',
        'is_correct',
    ];

    protected $casts = [
        'answer' => 'array',
    ];

    public function attempt()
    {
        return $this->belongsTo(AssessmentAttempt::class, 'attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(AssessmentQuestion::class, 'question_id');
    }
}
