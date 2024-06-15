<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ASIN extends Model {
	
    protected $fillable = [
        'shop_id', 'title', 'description', 'feature1', 'feature2', 'feature3', 'feature4', 'feature5', 'item_note', 'brand', 'product_type', 'option1name', 'option2name', 'option3name', 'shopifyproductid', 'newflag', 'quantityflag', 'priceflag','asin','image','parentasin'
	];
	
}
