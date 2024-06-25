<?php

namespace App\Models;


use Nicolaslopezj\Searchable\SearchableTrait;
use Zizaco\Entrust\EntrustPermission;
class Permission extends EntrustPermission
{
    use SearchableTrait;
    protected $searchable = [
        'columns' => [
            'permissions.name' => 100,
            'permissions.description' => 100,
        ],
    ];
    public function permission_role()
    {
        return $this->belongsToMany('App\Models\permission_role');
    }

    protected $fillable = ['name','display_name', 'description'];
}
