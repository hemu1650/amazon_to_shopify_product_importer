<?php

namespace App\Http\Controllers;

use App\AmzKey;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;

use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Search;
use ApaiIO\Operations\Lookup;
use ApaiIO\ApaiIO;
use App\Http\Requests;
use App\Http\Requests\AmzConfigRequest;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
require 'awsApi.php';

class AmzConfigController extends Controller
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
		$amzKey = $currUser->amzKey()->first();
		if(!$amzKey){
			return response()->json([], 201);
		} else {
			return Auth::User()->amzKey()->first();
		}
    }

    public function store(AmzConfigRequest $request)
    {
		$countryMapping = array("US" => "com", "CA" => "ca", "UK" => "co.uk", "IN" => "in", "BR" => "com.br", "MX" => "com.mx", "DE" => "de", "ES" => "es", "FR" => "fr", "IT" => "it", "JP" => "co.jp", "CN" => "cn");
		$currUser = Auth::User();
		$amzKey = $currUser->amzKey()->first();

		if($amzKey){
			$old_associate_id = $amzKey->associate_id;
			$associate_id = $request->input('associate_id');
			$aws_access_id = $request->input('aws_access_id');
			$aws_secret_key = $request->input('aws_secret_key');
			$category = 'iphone';$page = 1;
			$country = $request->input('country');
			if(array_key_exists($country, $countryMapping)){
				$country = $countryMapping[$country];
			}
			$payload="{"
			." \"Keywords\": \"".$category."\","
			." \"Resources\": ["
			."  \"BrowseNodeInfo.BrowseNodes\","
			."  \"BrowseNodeInfo.BrowseNodes.Ancestor\","
			."  \"BrowseNodeInfo.BrowseNodes.SalesRank\","
			."  \"BrowseNodeInfo.WebsiteSalesRank\","
			."  \"CustomerReviews.Count\","
			."  \"CustomerReviews.StarRating\","
			."  \"Images.Primary.Small\","
			."  \"Images.Primary.Medium\","
			."  \"Images.Primary.Large\","
			."  \"Images.Variants.Small\","
			."  \"Images.Variants.Medium\","
			."  \"Images.Variants.Large\","
			."  \"ItemInfo.ByLineInfo\","
			."  \"ItemInfo.ContentInfo\","
			."  \"ItemInfo.ContentRating\","
			."  \"ItemInfo.Classifications\","
			."  \"ItemInfo.ExternalIds\","
			."  \"ItemInfo.Features\","
			."  \"ItemInfo.ManufactureInfo\","
			."  \"ItemInfo.ProductInfo\","
			."  \"ItemInfo.TechnicalInfo\","
			."  \"ItemInfo.Title\","
			."  \"ItemInfo.TradeInInfo\","
			."  \"Offers.Listings.Availability.MaxOrderQuantity\","
			."  \"Offers.Listings.Availability.Message\","
			."  \"Offers.Listings.Availability.MinOrderQuantity\","
			."  \"Offers.Listings.Availability.Type\","
			."  \"Offers.Listings.Condition\","
			."  \"Offers.Listings.Condition.ConditionNote\","
			."  \"Offers.Listings.Condition.SubCondition\","
			."  \"Offers.Listings.DeliveryInfo.IsAmazonFulfilled\","
			."  \"Offers.Listings.DeliveryInfo.IsFreeShippingEligible\","
			."  \"Offers.Listings.DeliveryInfo.IsPrimeEligible\","
			."  \"Offers.Listings.DeliveryInfo.ShippingCharges\","
			."  \"Offers.Listings.IsBuyBoxWinner\","
			."  \"Offers.Listings.LoyaltyPoints.Points\","
			."  \"Offers.Listings.MerchantInfo\","
			."  \"Offers.Listings.Price\","
			."  \"Offers.Listings.ProgramEligibility.IsPrimeExclusive\","
			."  \"Offers.Listings.ProgramEligibility.IsPrimePantry\","
			."  \"Offers.Listings.Promotions\","
			."  \"Offers.Listings.SavingBasis\","
			."  \"Offers.Summaries.HighestPrice\","
			."  \"Offers.Summaries.LowestPrice\","
			."  \"Offers.Summaries.OfferCount\","
			."  \"ParentASIN\","
			."  \"RentalOffers.Listings.Availability.MaxOrderQuantity\","
			."  \"RentalOffers.Listings.Availability.Message\","
			."  \"RentalOffers.Listings.Availability.MinOrderQuantity\","
			."  \"RentalOffers.Listings.Availability.Type\","
			."  \"RentalOffers.Listings.BasePrice\","
			."  \"RentalOffers.Listings.Condition\","
			."  \"RentalOffers.Listings.Condition.ConditionNote\","
			."  \"RentalOffers.Listings.Condition.SubCondition\","
			."  \"RentalOffers.Listings.DeliveryInfo.IsAmazonFulfilled\","
			."  \"RentalOffers.Listings.DeliveryInfo.IsFreeShippingEligible\","
			."  \"RentalOffers.Listings.DeliveryInfo.IsPrimeEligible\","
			."  \"RentalOffers.Listings.DeliveryInfo.ShippingCharges\","
			."  \"RentalOffers.Listings.MerchantInfo\","
			."  \"SearchRefinements\""
			." ],"
			." \"ItemPage\": ".$page.","
			." \"PartnerTag\": \"".$associate_id."\","
			." \"PartnerType\": \"Associates\","
			." \"Marketplace\": \"www.amazon.".$country."\""
			."}";
			$path1 = 'searchitems';$path2 = 'SearchItems';
			$isValid = false;
			if(strlen($aws_access_id) > 0 || strlen($aws_secret_key) > 0 ){
				try {
					$response = getawsdata($aws_access_id,$aws_secret_key,$country,$payload,$path1,$path2);
					$isValid = true;
				} catch (\Exception $e) {
					Log::info($e->getMessage());
        			return response()->json(['error' => ["msg"=>['Invalid Keys.']]], 406);
        		}
			}
			$amzKey->associate_id = $associate_id;
			$amzKey->aws_access_id = $aws_access_id;
			$amzKey->aws_secret_key = $aws_secret_key;
			$amzKey->country = $country;
			$currUser->amzKey()->save($amzKey);
			if($old_associate_id != $associate_id){
				$this->addAssociateTag($currUser->shopurl, $currUser->token, $associate_id);
			}
			return response()->json(['success'], 200);
		} else {
			$associate_id = $request->input('associate_id');
			$aws_access_id = $request->input('aws_access_id');
			$aws_secret_key = $request->input('aws_secret_key');
			$category = 'iphone';$page = 1;
			$country = $request->input('country');
			if(array_key_exists($country, $countryMapping)){
				$country = $countryMapping[$country];
			}
			$payload="{"
			." \"Keywords\": \"".$category."\","
			." \"Resources\": ["
			."  \"BrowseNodeInfo.BrowseNodes\","
			."  \"BrowseNodeInfo.BrowseNodes.Ancestor\","
			."  \"BrowseNodeInfo.BrowseNodes.SalesRank\","
			."  \"BrowseNodeInfo.WebsiteSalesRank\","
			."  \"CustomerReviews.Count\","
			."  \"CustomerReviews.StarRating\","
			."  \"Images.Primary.Small\","
			."  \"Images.Primary.Medium\","
			."  \"Images.Primary.Large\","
			."  \"Images.Variants.Small\","
			."  \"Images.Variants.Medium\","
			."  \"Images.Variants.Large\","
			."  \"ItemInfo.ByLineInfo\","
			."  \"ItemInfo.ContentInfo\","
			."  \"ItemInfo.ContentRating\","
			."  \"ItemInfo.Classifications\","
			."  \"ItemInfo.ExternalIds\","
			."  \"ItemInfo.Features\","
			."  \"ItemInfo.ManufactureInfo\","
			."  \"ItemInfo.ProductInfo\","
			."  \"ItemInfo.TechnicalInfo\","
			."  \"ItemInfo.Title\","
			."  \"ItemInfo.TradeInInfo\","
			."  \"Offers.Listings.Availability.MaxOrderQuantity\","
			."  \"Offers.Listings.Availability.Message\","
			."  \"Offers.Listings.Availability.MinOrderQuantity\","
			."  \"Offers.Listings.Availability.Type\","
			."  \"Offers.Listings.Condition\","
			."  \"Offers.Listings.Condition.ConditionNote\","
			."  \"Offers.Listings.Condition.SubCondition\","
			."  \"Offers.Listings.DeliveryInfo.IsAmazonFulfilled\","
			."  \"Offers.Listings.DeliveryInfo.IsFreeShippingEligible\","
			."  \"Offers.Listings.DeliveryInfo.IsPrimeEligible\","
			."  \"Offers.Listings.DeliveryInfo.ShippingCharges\","
			."  \"Offers.Listings.IsBuyBoxWinner\","
			."  \"Offers.Listings.LoyaltyPoints.Points\","
			."  \"Offers.Listings.MerchantInfo\","
			."  \"Offers.Listings.Price\","
			."  \"Offers.Listings.ProgramEligibility.IsPrimeExclusive\","
			."  \"Offers.Listings.ProgramEligibility.IsPrimePantry\","
			."  \"Offers.Listings.Promotions\","
			."  \"Offers.Listings.SavingBasis\","
			."  \"Offers.Summaries.HighestPrice\","
			."  \"Offers.Summaries.LowestPrice\","
			."  \"Offers.Summaries.OfferCount\","
			."  \"ParentASIN\","
			."  \"RentalOffers.Listings.Availability.MaxOrderQuantity\","
			."  \"RentalOffers.Listings.Availability.Message\","
			."  \"RentalOffers.Listings.Availability.MinOrderQuantity\","
			."  \"RentalOffers.Listings.Availability.Type\","
			."  \"RentalOffers.Listings.BasePrice\","
			."  \"RentalOffers.Listings.Condition\","
			."  \"RentalOffers.Listings.Condition.ConditionNote\","
			."  \"RentalOffers.Listings.Condition.SubCondition\","
			."  \"RentalOffers.Listings.DeliveryInfo.IsAmazonFulfilled\","
			."  \"RentalOffers.Listings.DeliveryInfo.IsFreeShippingEligible\","
			."  \"RentalOffers.Listings.DeliveryInfo.IsPrimeEligible\","
			."  \"RentalOffers.Listings.DeliveryInfo.ShippingCharges\","
			."  \"RentalOffers.Listings.MerchantInfo\","
			."  \"SearchRefinements\""
			." ],"
			." \"ItemPage\": ".$page.","
			." \"PartnerTag\": \"".$associate_id."\","
			." \"PartnerType\": \"Associates\","
			." \"Marketplace\": \"www.amazon.".$country."\""
			."}";
			$path1 = 'searchitems';$path2 = 'SearchItems';
			$isValid = false;
			if(strlen($aws_access_id) > 0 || strlen($aws_secret_key) > 0 ){
				try {
					$response = getawsdata($aws_access_id,$aws_secret_key,$country,$payload,$path1,$path2);
					$isValid = true;
					$data = array("associate_id" => $associate_id, "aws_access_id" => $aws_access_id, "aws_secret_key" => $aws_secret_key, "country" => $country);
					$Request = AmzKey::create($data);
					Auth::User()->amzKey()->save($Request);
					$this->addAssociateTag($currUser->shopurl, $currUser->token, $associate_id);
					return response()->json(['success'], 200);
				} catch (\Exception $e) {
					Log::info($e->getMessage());
					return response()->json(['error' => ["msg"=>['Invalid Keys.']]], 406);
				}
			} else {
				$data = array("associate_id" => $associate_id, "aws_access_id" => "", "aws_secret_key" => "", "country" => $country);
				$Request = AmzKey::create($data);
				Auth::User()->amzKey()->save($Request);
				$this->addAssociateTag($currUser->shopurl, $currUser->token, $associate_id);
				return response()->json(['success'], 200);
			}
		}
    }

	private function addAssociateTag($shopurl, $token, $associate_id){
		$url = "https://".$shopurl."/admin/api/2022-01/metafields.json";
		$data = array("metafield" => array("namespace" => "isaac", "key" => "associateid", "value" => $associate_id, "type" => "single_line_text_field"));
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
		curl_close ($curl);
		$response_arr = explode("\n", $response);
		if( (strstr(($response_arr[0]), "201 Created")) || (strstr(($response_arr[1]), "201 Created")) || (strstr(($response_arr[2]), "201 Created")) ){
			return true;
		} else {
			return false;
		}
	}
}
