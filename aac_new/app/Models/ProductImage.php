<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model {

	protected $table = 'product_images';
	protected $primaryKey = 'id';
	
	public function productVariant() {
		return $this->belongsTo('App\Models\ProductVariant', 'variant_id', 'id');
	}

	protected $fillable = [
        'variant_id', 'asin', 'imgurl',"user_id"
    ];
}
