<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;

class Reviews extends Model
{
     use SearchableTrait;

     protected $searchable = [
     	"columns" => [
     	    'id' => 11,
            'product_asin' => 11,
     		'authorName' => 100,
		 	'reviewDate' => 20,
		 	'reviewDetails' => 1000,
		 	'reviewTitle' => 255,
		 	'rating' => 50,
		 	'verifiedFlag' => 30,
		 	'FoundHelpful' => 50,
		 	'status' => 50,
		 	'user_id'=> 11
     	],
     ];
	
	public function variants() {
		return $this->belongsTo('App\ProductVariant','product_asin','asin');
	}	
	
     protected $fillable = ['product_asin','authorName','reviewTitle','reviewDate','reviewDetails','rating','verifiedFlag','FoundHelpful','status','user_id']; 
}
