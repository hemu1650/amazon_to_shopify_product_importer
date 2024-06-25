<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ProductVariant extends Model {
	
	protected $table = 'product_variants';

	protected $primaryKey = 'id';
	
	public function user() {
		return $this->belongsTo('App\Models\User', 'user_id', 'id');
	}

	public function product() {
		return $this->belongsTo('App\Models\Product', 'product_id', 'product_id');
	}	
	
	public function images() {
		return $this->hasMany('App\Models\ProductImage', 'variant_id', 'id');
	}

	public function mainImage() {
		return $this->hasOne('App\Models\ProductImage', 'variant_id', 'id');
	}

	public function reviews(){
		return $this->hasMany('App\Models\Reviews','product_asin','asin');
	}

	protected $fillable = [
        'product_id', 'sku', 'asin', 'option1val', 'option2val', 'option3val', 'price', 'saleprice', 'currency', 'quantity', 'newflag', 'quantityflag', 'priceflag', 'shopifyproductid', 'shopifyvariantid', 'amazonofferlistingid', 'custom_link', 'weight', 'weight_unit', 'detail_page_url','user_id'
    ];
	
}