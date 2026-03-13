<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    // Users belonging to this batch
    public function users()
    {
        return $this->hasMany(User::class);
    }
}