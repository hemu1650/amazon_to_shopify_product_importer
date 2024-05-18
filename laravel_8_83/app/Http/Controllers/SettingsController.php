<?php

namespace App\Http\Controllers;

use App\Setting;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Requests;
use App\Http\Requests\SettingConfigRequest;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\ConversionRates;
use App\Currencies;
require '../Helpers/shopify.php';

class SettingsController extends Controller
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
		$settings = $currUser->settings()->first();
		
		$courrencies = Currencies::all();
		if($currUser->shopcurrency == "" || $currUser->shopcurrency == null){
		    //admin/shop.json
		    $details = $this->getShopDetails($currUser);
		    foreach($details as $key =>$value){
		        if($key == "errors"){
		            Log::info("Account Configuration Error for user : ".$currUser->id);
		            Log::info($value);
		        }else{
		            $currUser->shopcurrency = $details['currency'];
					$currUser->autoCurrencyConversion = $details['autoCurrencyConversion'];
		            $currUser->save();       
		        }
		    }
		}
		
		if(!$settings){
			$settingObj = new Setting;
			$settingObj->published = 1;
			$settingObj->tags = "";
			$settingObj->vendor = "";
			$settingObj->product_type = "";
			$settingObj->inventory_policy = "";
			$settingObj->defquantity = 1;
			$settingObj->inventory_sync = 0;
			$settingObj->price_sync = 0;
			$settingObj->outofstock_action = "outofstock";
			$settingObj->buynow = 0;
			$settingObj->buynowtext = "View on Amazon";
			$settingObj->scripttagid = "";
			$settingObj->markupenabled = 0;
			$settingObj->markuptype = 'FIXED';
			$settingObj->markupval = 0;
			$settingObj->markupround = 0;
			$settingObj->reviewenabled = 0;
			$settingObj->reviewwidth = 500;
			$settingObj->showreviews = 0;			
			$settingObj->starcolorreviews = 'yellow';	
			$settingObj->paginatereviews = 10;	
			$settingObj->paddingreviews = 10;	
			$settingObj->bordercolorreviews = 'yellow';			
			$currUser->settings()->save($settingObj);
			$settingObj->shopcurrency = $currUser->shopcurrency;
			$settingObj->autoCurrencyConversion = $currUser->autoCurrencyConversion;
			return $settingObj;
		} else {
		    $settings->courrencies = $courrencies;
		    $settings->shopcurrency = $currUser->shopcurrency;
		    $settings->autoCurrencyConversion = $currUser->autoCurrencyConversion;
			return $settings;
		}
    }
    
    private function getShopDetails($currUser){
        $curl = curl_init();
        
        $url = "https://".$currUser->shopurl."/admin/api/2022-01/shop.json";
       
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$currUser->token,'Content-Type: application/json; charset=utf-8'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close ($curl);
        $response_arr = explode("\n", $response);
        $climit = -1;
        Log::info($response);
        
        
        foreach($response_arr as $obj){
            if (strpos($obj, 'X-Shopify-Shop-Api-Call-Limit') !== false) {
                $tempArr = explode(":", $obj);
                $climit = substr(trim(end($tempArr)), 0, -3);
            }
           
        }
        if(intval($climit) > 35){
            sleep(5);
        }
        
        $jsonData = json_decode($response_arr[sizeof($response_arr)-1],true);
        return $jsonData;
    }

    public function store(SettingConfigRequest $request)
    {
		$currUser = Auth::User();
		$shopurl = $currUser->shopurl;
		$token = $currUser->token;
		Log::info($request);
		$published = 1;
		$tags = "";		
		$vendor = "";
		$product_type = "";
		$inventory_policy = "shopify";
		$defquantity = 1;
		$autoCurrencyConversion = 0;
		$showreviews = '';			
		$starcolorreviews = '';	
		$paginatereviews = 0;	
		$paddingreviews = 0;	
		$bordercolorreviews = '';	
		
		if($request->has("published")){
			$published = $request->input("published");
			Log::info("P Executed");
		}
		if($request->has("tags")){
			$tags = $request->input("tags");
			Log::info("T Executed");
		}
		if($request->has("vendor")){
			$vendor = $request->input("vendor");
			Log::info("V Executed");
		}
		if($request->has("product_type")){
			$product_type = $request->input("product_type");
			Log::info("P Executed");
		}
		if($request->has("inventory_policy")){
			$inventory_policy = $request->input("inventory_policy");
			Log::info("Executed");
		}
		
		if($request->has("autoCurrencyConversion")){
			$autoCurrencyConversion = $request->input("autoCurrencyConversion");
		}
		
		if($request->has("shopcurrency")){
          $shopcurrency = $request->input("shopcurrency");
           }
		
		if($request->has("defquantity")){
		    Log::info("D Executed");
			$defquantity = $request->input("defquantity");
		}		

		if($request->has("showreviews")){
		    Log::info("D Executed");
			$showreviews = $request->input("showreviews");
		}	

		if($request->has("starcolorreviews")){
		    Log::info("D Executed");
			$starcolorreviews = $request->input("starcolorreviews");
		}	

		if($request->has("paginatereviews")){
		    Log::info("D Executed");
			$paginatereviews = $request->input("paginatereviews");
		}	

		if($request->has("paddingreviews")){
		    Log::info("D Executed");
			$paddingreviews = $request->input("paddingreviews");
		}
		
		if($request->has("bordercolorreviews")){
		    Log::info("D Executed");
			$bordercolorreviews = $request->input("bordercolorreviews");
		}

		
		
		$settingObj = $currUser->settings()->first();
		$settingObj->published = $published;
		$settingObj->tags = $tags;
		$settingObj->vendor = $vendor;
		$settingObj->product_type = $product_type;
		$settingObj->inventory_policy = $inventory_policy;
		$settingObj->defquantity = $defquantity;
		
		$settingObj->showreviews	 = $showreviews;			
		$settingObj->starcolorreviews = $starcolorreviews;	
		$settingObj->paginatereviews = $paginatereviews;	
		$settingObj->paddingreviews = $paddingreviews;	
		$settingObj->bordercolorreviews = $bordercolorreviews;
		
		$valueStr = array('enable' => $showreviews,
				'star_color' => $starcolorreviews,
				'padding' => $paddingreviews,
				'cnt_per_page' => $paginatereviews);
		$value = json_encode($valueStr);
		
		$existingMetafieldsArr = fetchShopMetafields($shopurl, $token);
		$conditionupdated = false;
		if(is_array($existingMetafieldsArr) && count($existingMetafieldsArr) > 0){
			foreach($existingMetafieldsArr as $existingMetafield){
				if($existingMetafield['namespace'] == "isaac" && $existingMetafield['key'] == "shopconfig"){
					$conditionupdated = true;
					$metafield_id = $existingMetafield['id'];
					$data = array(
							"metafield"=>array(
								"id" => $metafield_id,					
								"value" => $value,
								"type" => "json"
								)
							);		
					updateMetafield($token, $shopurl, $metafield_id, $data);
				}
			}
		} 
		if(!$conditionupdated) {
			$data = array(
				"metafield"=>array(
					"namespace" => "isaac",					
					"key" => "shopconfig",
					"value" => $value,
					"type" => "json"
				)
			);		
			createShopMetafield($token, $shopurl, $data);
		}
		
		$currUser->autoCurrencyConversion = $autoCurrencyConversion;
		$currUser->shopcurrency = $shopcurrency;
        $currUser->save();
		
		Log::info($settingObj);
		
		$currUser->settings()->save($settingObj);
		return response()->json(['success' => $settingObj], 200);				
    }   
	
	public function saveBuyNowSettings(Request $request)
    {
		$currUser = Auth::User();
		$shopurl = $currUser->shopurl;
		$token = $currUser->token;
		
		$buynow = 0;
		$buynowtext = "";			
		if($request->has("buynow")){
			$buynow = $request->input("buynow");
		}
		if($request->has("buynowtext")){
			$buynowtext = $request->input("buynowtext");
		}

		$settingObj = $currUser->settings()->first();
		$oldBuyNow = $settingObj->buynow;
		$oldBuyNowText = $settingObj->buynowtext;
		$scripttagid = $settingObj->scripttagid;
		
		$settingObj->buynow = $buynow;
		$settingObj->buynowtext = $buynowtext;
		
		//if(strcmp($oldBuyNowText, $buynowtext) != 0){
			$this->addButtonText($shopurl, $token, $buynowtext);
	//	}
		if($oldBuyNow == 1){
			if($buynow == 0){//&& $currUser->id != 279 && $currUser->id != 262
				$res = $this->deleteScriptTag($shopurl, $token, $scripttagid);
				if($res){
					$settingObj->scripttagid = '';
				}
			} 
		} else if($oldBuyNow == 0) {//&& $currUser->id != 279 && $currUser->id != 262
			if($buynow == 1){
				$res = $this->addScriptTag($shopurl, $token);
				if($res){
					$settingObj->scripttagid = $res;
				}
			}
		}
		$currUser->settings()->save($settingObj);
		return response()->json(['success' => $settingObj], 200);				
    }
	
	public function savePricingRulesSettings(Request $request)
    {
		Log::info($request);
        if($request->apply == 'no'){        
	        $settings = $request->settings;
			$currUser = Auth::User();
			$settings = json_decode($settings);
			$markupenabled = 0;
			$markuptype = "FIXED";		
			$markupval = 0;
			$markupvalfixed = 0;
			$markupround = 0;		
			$markupenabled = $settings->markupenabled;
    		$markuptype = $settings->markuptype;
    		$markupval = $settings->markupval;
			if(isset($settings->markupvalfixed)){
				$markupvalfixed = $settings->markupvalfixed;
			}
    		$markupround = $settings->markupround;
    		$settingObj = $currUser->settings()->first();
    		$settingObj->markupenabled = $markupenabled;
    		$settingObj->markuptype = $markuptype;
    		$settingObj->markupval = $markupval;
			$settingObj->markupvalfixed = $markupvalfixed;
    		$settingObj->markupround = $markupround;    		
    		$currUser->settings()->save($settingObj);
    		return response()->json(['success' => $settingObj], 200);	
        }else{
            $settings = $request->settings;
    		$currUser = Auth::User();
    		$settings = json_decode($settings);
    		$markupenabled = 0;
    		$markuptype = "FIXED";		
    		$markupval = 0;
			$markupvalfixed = 0;
    		$markupround = 0;		
    		$markupenabled = $settings->markupenabled;    			
   			$markuptype = $settings->markuptype;
    		$markupval = $settings->markupval;
			if(isset($settings->markupvalfixed)){
				$markupvalfixed = $settings->markupvalfixed;
			}			
    		$markupround = $settings->markupround;

    		$settingObj = $currUser->settings()->first();
    		$settingObj->markupenabled = $markupenabled;
    		$settingObj->markuptype = $markuptype;
    		$settingObj->markupval = $markupval;
			$settingObj->markupvalfixed = $markupvalfixed;
    		$settingObj->markupround = $markupround;
    		$settingObj->change_status = 0;
    		
    		$currUser->settings()->save($settingObj);
            return response()->json(['success' => $settingObj], 200);
        }
    }
	
	public function saveSyncSettings(Request $request)
    {
		$currUser = Auth::User();		
		$inventory_sync = 0;
		$price_sync = 0;
		$outofstock_action = "unpublish";		
		if($request->has("inventory_sync")){
			$inventory_sync = $request->input("inventory_sync");
		}
		if($request->has("price_sync")){
			$price_sync = $request->input("price_sync");
		}
		if($request->has("outofstock_action")){
			$outofstock_action = $request->input("outofstock_action");
		}		

		$settingObj = $currUser->settings()->first();		
		$settingObj->inventory_sync = $inventory_sync;
		$settingObj->price_sync = $price_sync;
		$settingObj->outofstock_action = $outofstock_action;		
		$currUser->settings()->save($settingObj);
		return response()->json(['success' => $settingObj], 200);				
    }
	
	public function saveReviewsSettings(Request $request)
    {
        $currUser = Auth::User();	
		$shopurl = $currUser->shopurl;
		$token = $currUser->token;
		$reviewenabled = 0;
		$reviewwidth = 0;
		$showreviews = 0;			
		$starcolorreviews = 'yellow';	
		$paginatereviews = 10;	
		$paddingreviews = 10;	
		$bordercolorreviews = 'yellow';

		if($request->has("reviewenabled")){
			$reviewenabled = $request->input("reviewenabled");
		}
		if($request->has("reviewwidth")){
			$reviewwidth = $request->input("reviewwidth");
		}
		if($request->has("showreviews")){
			$showreviews = $request->input("showreviews");
		}
		if($request->has("starcolorreviews")){
			$starcolorreviews = $request->input("starcolorreviews");
		}
		if($request->has("paginatereviews")){
			$paginatereviews = $request->input("paginatereviews");
		}
		if($request->has("paddingreviews")){
			$paddingreviews = $request->input("paddingreviews");
		}
		if($request->has("bordercolorreviews")){
			$bordercolorreviews = $request->input("bordercolorreviews");
		}

		$settingObj = $currUser->settings()->first();		
		$settingObj->reviewenabled = $reviewenabled;
		$settingObj->reviewwidth = $reviewwidth;
		$settingObj->showreviews = $showreviews;
		$settingObj->starcolorreviews = $starcolorreviews;
		$settingObj->paginatereviews = $paginatereviews;
		$settingObj->paddingreviews = $paddingreviews;
		$settingObj->bordercolorreviews = $bordercolorreviews;
		
		$valueStr = array('enable' => $showreviews,
				'star_color' => $starcolorreviews,
				'padding' => $paddingreviews,
				'cnt_per_page' => $paginatereviews);
		$value = json_encode($valueStr);
		
		$existingMetafieldsArr = fetchShopMetafields($shopurl, $token);
		$conditionupdated = false;
		if(is_array($existingMetafieldsArr) && count($existingMetafieldsArr) > 0){
			foreach($existingMetafieldsArr as $existingMetafield){
				if($existingMetafield['namespace'] == "isaac" && $existingMetafield['key'] == "shopconfig"){
					$conditionupdated = true;
					$metafield_id = $existingMetafield['id'];
					$data = array(
							"metafield"=>array(
								"id" => $metafield_id,					
								"value" => $value,
								"type" => "json"
								)
							);		
					updateMetafield($token, $shopurl, $metafield_id, $data);
				}
			}
		} 
		if(!$conditionupdated) {
			$data = array(
				"metafield"=>array(
					"namespace" => "isaac",					
					"key" => "shopconfig",
					"value" => $value,
					"type" => "json"
				)
			);		
			createShopMetafield($token, $shopurl, $data);
		}
			
		$currUser->settings()->save($settingObj);
		return response()->json(['success' => $settingObj], 200);				
    }

	private function addScriptTag($shopurl, $token){
		$url = "https://".$shopurl."/admin/api/2022-01/script_tags.json";
		$data = array("script_tag" => array("event" => "onload", "src" => "https://shopify.infoshore.biz/aac/asdfa342dsffdsf1111wwccvs/is_aac.js"));		
		if($shopurl == 'devaise.myshopify.com'){
		    return "123";
		    //$data = array("script_tag" => array("event" => "onload", "src" => "https://shopify.infoshore.biz/aac/asdfa342dsffdsf1111wwccvs/is_aac_3176.js"));
		}
		if($shopurl == 'tidytown.myshopify.com'){
		    $data = array("script_tag" => array("event" => "onload", "src" => "https://shopify.infoshore.biz/aac/asdfa342dsffdsf1111wwccvs/is_aac_2536.js"));
		}
		if($shopurl == 'hardcasesuk.myshopify.com' || $shopurl == 'a-loja-be.myshopify.com'){
		    $data = array("script_tag" => array("event" => "onload", "src" => "https://shopify.infoshore.biz/aac/asdfa342dsffdsf1111wwccvs/is_aac_7373.js"));
		}
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'X-Shopify-Access-Token:'.$token));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec ($curl);
		Log::info($response);
		curl_close ($curl);	
		$response_arr = explode("\n", $response);
		if( (strstr(($response_arr[0]), "201")) || (strstr(($response_arr[1]), "201")) || (strstr(($response_arr[2]), "201")) ){
			$scriptJson = end($response_arr);
			$scriptArr = json_decode($scriptJson, true);
			if(isset($scriptArr["script_tag"]["id"])){
				return $scriptArr["script_tag"]["id"];
			}
		} else {
			return false;
		}
	}

	private function deleteScriptTag($shopurl, $token, $scripttagid){
		global $conn;
		$url = "https://".$shopurl."/admin/api/2022-01/script_tags/".$scripttagid.".json";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token, 'Content-Type: application/json; charset=utf-8'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
		
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec ($curl);
		Log::info($response);
		curl_close ($curl);
		$response_arr = explode("\n",$response);
		if(strstr(($response_arr[0]), "200")){
			return true;	
		} else {
			return false;	
		}
	}

	private function addButtonText($shopurl, $token, $buttontext){
		$url = "https://".$shopurl."/admin/api/2022-01/metafields.json";
		$data = array("metafield" => array("namespace" => "isaac", "key" => "buttontext", "value" => $buttontext, "type" => "single_line_text_field"));		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'X-Shopify-Access-Token:'.$token));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec ($curl);		
		Log::info($response);
		curl_close ($curl);	
		$response_arr = explode("\n", $response);
		if( (strstr(($response_arr[0]), "201")) || (strstr(($response_arr[1]), "201")) || (strstr(($response_arr[2]), "201")) ){
			return true;
		} else {
			return false;
		}
	}

}