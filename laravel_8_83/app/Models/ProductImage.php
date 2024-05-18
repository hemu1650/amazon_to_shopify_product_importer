<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model {

	protected $table = 'product_images';
	protected $primaryKey = 'id';
	
	public function productVariant() {
		return $this->belongsTo('App\ProductVariant', 'variant_id', 'id');
	}

	protected $fillable = [
        'variant_id', 'asin', 'imgurl',"user_id"
    ];
}
