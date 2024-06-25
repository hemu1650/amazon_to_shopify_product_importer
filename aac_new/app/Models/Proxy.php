<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proxy extends Model {

	protected $table = 'proxy';

	protected $primaryKey = 'id';
	   
	protected $fillable = [
        'plan', 'url', 'port', 'flag', 'username', 'password'
    ];
}