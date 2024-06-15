<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;

class Failed_productimports extends Model
{    

     protected $fillable = ['url','reason','type','user_id']; 
}
