<?php

namespace App\Http\Controllers;

use Mail;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;

use App\Http\Requests;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function __construct()
    {
        // Apply the jwt.auth middleware to all methods in this controller
        // except for the authenticate method. We don't want to prevent
        // the user from retrieving their token if they don't already have it
        $this->middleware('jwt.auth', ['except' => ['authenticate']]);
    }  

    public function requestquote(Request $request)
    {
		$currUser = Auth::User();		
		$description = "";		
		if($request->has("description")){
			$description = $request->input("description");
		}
		if(strlen($description) == 0){
			return response()->json(['error' => ["msg"=>['Invalid input data.']]], 406);
		}
		$data = array("fromemail" => $currUser->email, "fromname" => $currUser->ownername, "description" => $description, "shopurl" => $currUser->shopurl);		
		Mail::send('emails.requestquote', ['data' => $data], function ($m) use ($data) {
			Log::info($data['fromemail']);
            $m->from($data['fromemail'], $data['fromname']);
			$m->replyTo($data['fromemail'], $data['fromname']);
            $m->to("khariwal.rohit@gmail.com", "Rohit Khariwal")->subject('New AAC Feature Request!');
        });
		//@mail("khariwal.rohit@gmail.com", "New AAC Feature Request!", "<p>".$description."</p><p>".$currUser->shopurl."</p>");
		return response()->json(['success'], 200);				
    } 
}