<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentAttempt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'user_id',
        'started_at',
        'submitted_at',
        'score',
        'question_order',
        'batch_id'
    ];

    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'question_order' => 'array',
    ];

    // Attempt → Assessment
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    // Attempt → User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Attempt → Batch
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    // Attempt → Answers
    public function answers()
    {
        return $this->hasMany(AssessmentAnswer::class, 'attempt_id');
    }
}
