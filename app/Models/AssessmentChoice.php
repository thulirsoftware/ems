<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentChoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'question_id',
        'option',
        'is_correct',
        'order',
    ];

    public function question()
    {
        return $this->belongsTo(AssessmentQuestion::class, 'question_id');
    }
}
