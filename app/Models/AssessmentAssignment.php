<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'user_id',
        'assigned_at',
        'batch_id',
    ];

    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    // Assignment → Assessment
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    // Assignment → User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Assignment → Batch
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
