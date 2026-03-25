<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'admin_id',
        'assessment_type_id',
        'title',
        'description',
        'is_active',
        'publish_date',
        'start_time',
        'end_time',
        'difficulty_level',
        'shuffle',
        'is_library',
        'has_negative',
        'negative_marks',
        'is_batch_wise'
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
        return $this->hasMany(\App\Models\Batch::class);
    }
}
