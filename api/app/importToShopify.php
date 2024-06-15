<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;

class importToShopify extends Model
{
    use SearchableTrait;
    protected $table = "importToShopify";
    protected $searchable = [
    	"columns" => [
    		'importToShopify.product_id' => 11,
    		'importToShopify.user_id' => 11,
    		'importToShopify.status' => 5
    	]
    ];

    protected $fillable = ['product_id','user_id','status'];
}
