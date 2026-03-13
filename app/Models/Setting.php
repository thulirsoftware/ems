<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key','value'];

    public static function get($key)
    {
        return static::where('key',$key)->value('value');
    }
}