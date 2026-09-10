<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use HasFactory, SoftDeletes;

    const SCHEDULING_BATCH_WISE = 'batch_wise';
    const SCHEDULING_FIXED = 'fixed';
    const SCHEDULING_FLEXIBLE = 'flexible';

    const SCHEDULING_TYPES = [
        self::SCHEDULING_BATCH_WISE,
        self::SCHEDULING_FIXED,
        self::SCHEDULING_FLEXIBLE,
    ];

    protected $fillable = [
        'admin_id',
        'assessment_type_id',
        'title',
        'description',
        'is_active',
        'difficulty_level',
        'shuffle',
        'is_library',
        'has_negative',
        'negative_marks',
        'scheduling_type',
    ];

    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    // relationships
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function type()
    {
        return $this->belongsTo(AssessmentType::class, 'assessment_type_id');
    }

    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }
}
