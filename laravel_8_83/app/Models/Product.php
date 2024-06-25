<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model {
	
	protected $table = 'products';

	protected $primaryKey = 'product_id';

    public function user() {
		return $this->belongsTo('App\Models\User', 'user_id', 'id');
	}
	public function Category()
    {
        return $this->belongsTo('App\Models\Category');
    }
	
	public function variants() {
		return $this->hasMany('App\Models\ProductVariant', 'product_id', 'product_id');
	}

	public function variantsCount() {
		return $this->hasOne('App\Models\ProductVariant', 'product_id', 'product_id')->selectRaw('product_id, count(*) as no_of_variants, sum(quantity) as quantity')->groupBy('product_id');
	}

     protected $fillable = [
        'product_id', 'title', 'description', 'feature1', 'feature2', 'feature3', 'feature4', 'feature5', 'item_note', 'brand', 'product_type', 'option1name', 'option2name', 'option3name', 'shopifyproductid', 'newflag', 'quantityflag', 'priceflag','asin','image','parentasin','status','reviews'
	];
	
}
