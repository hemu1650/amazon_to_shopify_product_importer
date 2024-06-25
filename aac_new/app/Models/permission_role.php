<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class permission_role extends model
{
    public function permission()
    {
        return $this->hasMany('App\Models\Permission');
    }
}
