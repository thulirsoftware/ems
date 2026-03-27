<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'publish_date',
        'start_time',
        'end_time',
        'is_batch_wise',
        'assessment_id',
        'capacity',
    ];

    // Batch → Assessment
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    // Batch → Assignments
    public function assignments()
    {
        return $this->hasMany(AssessmentAssignment::class);
    }

    // Batch → Attempts
    public function attempts()
    {
        return $this->hasMany(AssessmentAttempt::class);
    }
}