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
        'shuffle',
        'is_library',
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
}
