<?php

namespace App\Models;


use Zizaco\Entrust\EntrustRole;
use Nicolaslopezj\Searchable\SearchableTrait;

class Role extends EntrustRole
{
    use SearchableTrait;
    protected $searchable = [
        'columns' => [
            'roles.name' => 100,
            'roles.description' => 100,
        ],
    ];
    public function role_user()
    {
        return $this->belongsTo('App\Models\role_user');
    }
    protected $fillable = ['name','display_name', 'description'];
}
