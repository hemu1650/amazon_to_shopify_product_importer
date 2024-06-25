<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class role_user extends Model
{
    public function role()
    {
        return $this->hasMany('App\Models\Role');
    }
    public function user()
    {
        return $this->hasMany('App\Models\User');
    }
    protected $fillable = ['user_id', 'role_id'];
}
