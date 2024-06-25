<?php

namespace App\Http\Controllers;
use App\Models\AmzKey;
use App\Models\Category;
use Illuminate\Http\Request;

use App\Models\Http\Requests;
use Validator;
use App\Models\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function __construct()
    {
        // Apply the jwt.auth middleware to all methods in this controller
        // except for the authenticate method. We don't want to prevent
        // the user from retrieving their token if they don't already have it
        $this->middleware('jwt.auth', ['except' => ['authenticate']]);
    }


    public function index()
    {
        $currUser = Auth::User();
        $country = 'US';        
        $amzKey = $currUser->amzKey()->first();
        if($amzKey){
           $country = $amzKey->country;
        }        
		$categories = Category::where('country', $country)->get();
        return $categories;
    }

}