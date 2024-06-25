<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model {

	protected $table = 'setting';

	protected $primaryKey = 'id';
	   
	public function user() {
		return $this->belongsTo('App\Models\User', 'user_id', 'id');
	}
	
	protected $fillable = [
        'user_id', 'published', 'tags', 'vendor', 'product_type', 'inventory_policy', 'defquantity', 'price_sync', 'inventory_sync', 'outofstock_action', 'buynow', 'buynowtext','scripttagid','markupenabled','markuptype','markupval','markupvalfixed','markupround','reviewenabled','reviewwidth','showreviews', 'starcolorreviews', 'paginatereviews', 'paddingreviews', 'bordercolorreviews','shopifylocationid'
    ];
}