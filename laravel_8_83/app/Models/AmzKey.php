<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AmzKey extends Model {

	protected $table = 'amz_keys';

	protected $primaryKey = 'id';
	   
	public function user() {
		return $this->belongsTo('App\User', 'user_id', 'id');
	}
	
	protected $fillable = [
        'user_id', 'associate_id', 'aws_access_id', 'aws_secret_key', 'aws_country', 'country'
    ];
}