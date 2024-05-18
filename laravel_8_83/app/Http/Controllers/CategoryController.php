<?php

namespace App\Http\Controllers;
use App\AmzKey;
use App\Category;
use Illuminate\Http\Request;

use App\Http\Requests;
use Validator;
use App\Http\Controllers\Controller;
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