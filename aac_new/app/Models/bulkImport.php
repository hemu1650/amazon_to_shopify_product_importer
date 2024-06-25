<?php

namespace App\Models;
// use Nicolaslopezj\Searchable\SearchableTrait;
use Illuminate\Database\Eloquent\Model;

class bulkImport extends Model
{
    // use SearchableTrait;

    protected $searchable = [
    		"columns" => [
    			"bulk_imports.id" => 11,
    			"bulk_imports.asin" => 1,
    			"bulk_imports.amazon_base_url" => 200,
    			"bulk_imports.failed" => 1,
    			"bulk_imports.total" => 100,
    			"bulk_imports.failed_asin" => 1,
    			"bulk_imports.user_id" => 10,
    			"bulk_imports.created_at" => 6,
    			"bulk_imports.updated_at" => 6
    		],
    ];

    protected $fillable = ["id","asin","amazon_base_url","failed","user_id","total","failed_asin","created_at","updated_at"];
}