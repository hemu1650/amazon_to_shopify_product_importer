<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Nicolaslopezj\Searchable\SearchableTrait;
use PhpSoft\Users\Models\UserTrait;

class User extends Model implements AuthenticatableContract,
                                    //AuthorizableContract,
                                    CanResetPasswordContract
{
	
    use Authenticatable, CanResetPassword,UserTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ownername', 'email', 'password','token',"shopcurrency"
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    public function products() {
		return $this->hasMany('App\Product', 'user_id', 'id');
	}

    public function Chargerequest() {
		return $this->hasMany('App\Chargerequest', 'user_id', 'id');
	}
	
    public function variants() {
        return $this->hasMany('App\ProductVariant', 'user_id', 'id');
    }

	public function amzKey(){
		return $this->hasOne('App\AmzKey', 'user_id', 'id')->select('id', 'associate_id', 'aws_access_id', 'aws_secret_key', 'country');
	}	
	
	public function settings() {
        return $this->hasOne('App\Setting', 'user_id', 'id');
    }
}