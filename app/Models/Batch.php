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
        'expiry_date',
        'duration_minutes',
        'assessment_id',
        'capacity',
    ];

    // Batch → Assessment
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    // Batches explicitly created via Batch CRUD, for batch_wise assessments
    // only. Fixed/flexible assessments manage their single implicit batch
    // through the Assessment APIs instead, so it's excluded here.
    public function scopeBatchWiseOnly($query)
    {
        return $query->whereHas(
            'assessment',
            fn($q) => $q->where('scheduling_type', Assessment::SCHEDULING_BATCH_WISE)
        );
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