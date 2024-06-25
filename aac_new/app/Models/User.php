<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Nicolaslopezj\Searchable\SearchableTrait;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract, JWTSubject
{
    use Authenticatable, CanResetPassword;

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
        'ownername', 'email', 'password', 'token', 'shopcurrency'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Define a one-to-many relationship with Product model.
     */
    public function products() {
        return $this->hasMany('App\Models\Product', 'user_id', 'id');
    }

    /**
     * Define a one-to-many relationship with Chargerequest model.
     */
    public function chargerequest() {
        return $this->hasMany('App\Models\Chargerequest', 'user_id', 'id');
    }

    /**
     * Define a one-to-many relationship with ProductVariant model.
     */
    public function variants() {
        return $this->hasMany('App\Models\ProductVariant', 'user_id', 'id');
    }

    /**
     * Define a one-to-one relationship with AmzKey model.
     */
    public function amzKey() {
        return $this->hasOne('App\Models\AmzKey', 'user_id', 'id')->select('id', 'associate_id', 'aws_access_id', 'aws_secret_key', 'country');
    }

    /**
     * Define a one-to-one relationship with Setting model.
     */
    public function settings() {
        return $this->hasOne('App\Models\Setting', 'user_id', 'id');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
