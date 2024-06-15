<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;

class fetchReviews extends Model
{
    use SearchableTrait;
    protected $table = "fetchReviews";
    protected $searchable = [
    	"columns" => [
    		'fetchReviews.product_asin' => 11,
    		'fetchReviews.user_id' => 11,
    		'fetchReviews.status' => 5
    	]
    ];

    protected $fillable = ['product_asin','user_id','status'];
}
