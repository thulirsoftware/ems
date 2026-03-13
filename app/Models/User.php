<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'avatar',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'email_verified_at',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
