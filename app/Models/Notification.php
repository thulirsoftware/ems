<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'admin_id',
        'type',
        'title',
        'message',
        'data',
        'is_read'
    ];

    // created_at/updated_at stay visible (unlike sibling models) because
    // notification recency is meaningful to the client; deleted_at never
    // carries information since default queries already exclude trashed
    // rows, so it's hidden like every other model hides it.
    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean'
    ];
}
