<?php

namespace App;
use Nicolaslopezj\Searchable\SearchableTrait;
use Illuminate\Database\Eloquent\Model;

class UserAgents extends Model
{
    use SearchableTrait;

    protected $searchable = [
    		"columns" => [
    			"use_agents.id" => 11,
    			"use_agents.ua_string" => 500,
    		],
    ];

    protected $fillable = ["id","ua_string","created_at","updated_at"];
}