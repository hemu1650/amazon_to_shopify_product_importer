<?php

// details url in case the associate id is added
namespace App\Http\Controllers;

use App\AmzKey;
use App\Product;
use App\ProductVariant;
use App\ProductImage;
use App\Failed_productimports;
use App\Setting;
use Carbon\Carbon;
use App\Proxy;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use App\Reviews;
use App\importToShopify;
use App\fetchReviews;
use App\Currencies;
use App\UserAgents;
use Illuminate\Support\Facades\DB;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Translation\Tests\Dumper\IniFileDumperTest;
use Validator;
use File;
use App\bulkImport;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;

require '../Helpers/getJson.php';
require '../Helpers/shopify.php';
require 'awsApi.php';

class ProductController extends Controller
{
	public function __construct()
	{
		\set_time_limit(0);
		// Apply the jwt.auth middleware to all methods in this controller
		// except for the authenticate method. We don't want to prevent
		// the user from retrieving their token if they don't already have it
		$this->middleware('jwt.auth', ['except' => ['authenticate']]);
	}


	public function productCount()
	{
		$currUser = Auth::User();
		return $currUser->products()->first();
	}

	public function index()
	{
		$per_page = \Request::get('per_page') ?: 20;
		$currUser = Auth::User();
		return $currUser->products()->where('status', 'Imported')->Orwhere('status', 'Import in progress')->Orwhere('status', 'Ready to Import')->with('variants')->with('variantsCount')->with('variants.reviews')->with('variants.mainImage')->orderBy('product_id', 'DESC')->paginate($per_page);
	}

	public function productlist2()
	{
		Log::info("product List 2 executed");
		$per_page = \Request::get('per_page') ?: 20;
		$currUser = Auth::User();
		return $currUser->products()->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 3 HOUR)')->with('variants')->with('variantsCount')->with('variants.mainImage')->orderBy('product_id', 'DESC')->paginate($per_page);
	}

	public function search(Request $request)
	{
		$per_page = \Request::get('per_page') ?: 20;
		$currUser = Auth::User();

		### search
		$query =$request['query'];
		if($query == 'Imported' || $query == 'Ready to Import' || $query == 'Import in progress'){
			$products = $currUser->products()->where('status', '=', $request['query'])->with('variants')->with('variantsCount')->with('variants.mainImage')->orderBy('product_id', 'DESC');
			return $products->paginate($per_page);	
		}
  		 else{
		
       $products = $currUser->products()->where('title', 'like', '%' . $query . '%')->orWhere('parentasin', 'like', '%' . $query . '%')->with('variants')->with('variantsCount')->with('variants.mainImage')->orderBy('product_id', 'DESC');
        return $products->paginate($per_page);



		}

	

	}


	public function incompletesearch(Request $request)
	{
		$per_page = \Request::get('per_page') ?: 20;
		$currUser = Auth::User();
		### search

		if ($request['query']) {
			$products = $currUser->products()->where('status', 'Incomplete')->where('title', 'like', '%' . $request['query'] . '%')->with('variants')->with('variantsCount')->with('variants.mainImage')->orderBy('product_id', 'DESC');
			return $products->paginate($per_page);
		} else {
			return $this->incompleteProducts();
		}
	}

	public function incompleteProducts(Request $request)
	{
		$per_page = \Request::get('per_page') ?: 20;
		$currUser = Auth::User();
		return $currUser->products()->where('status', 'Incomplete')->with('variants')->with('variantsCount')->with('variants.reviews')->with('variants.mainImage')->orderBy('product_id', 'DESC')->paginate($per_page);
	}

	public function update(Request $request)
	{




		$currUser = Auth::User();
		$array = $request->all();
		$price = $array['price'];
		if ($price <= 0 || $price == '') {
			return response()->json(['error' => ["msg" => ['Price field can not be 0']]], 406);
		}
		$product_id = $array['id'];
		$currUser->variants()->where('product_id', $product_id)->update(['price' => $price]);
		$currUser->products()->where('product_id', $product_id)->update(['status' => 'Imported']);
		

        $productObjects = $currUser->products()->where('product_id', $product_id)->with('variants')->get();
		$productObject = (object) $productObjects[0];

		$this->insertToShopify($productObject);
		$newProduct = $currUser->products()->where('product_id', $product_id)->get();
		return response()->json(['success', $newProduct[0]['product_id'], $newProduct[0]['shopifyproductid']], 200);
	}

	/*public function show(Request $request){
	    return response()->json(['error' => ["msg"=>['Please refine your search criteria.']]], 406);
	}*/

	public function show($id)
	{
		$currUser = Auth::User();
		if (isset($id)) {
			$productObj = $currUser->products()->where("product_id", $id)->with('variants')->first();
			return $productObj;
		}
	}

	public function update1(Request $request)
	{
		$data = $request->all();
		Log::info($data);
		$currUser = Auth::User();
		$shopurl = $currUser->shopurl;
		$token = $currUser->token;

		if (!$request->has("product_id")) {
			return response()->json(['error' => ["msg" => ['Invalid request.']]], 406);
		}
		$product_id = $request->input("product_id");
		$newtitle = "";
		$newdescription = "";
		$newsku = "";
		if ($request->has("title")) {
			$newtitle = $request->input("title");
		}
		if ($request->has("description")) {
			$newdescription = $request->input("description");
		}
		if (isset($data["variants"][0]["sku"])) {
			$newsku = $data["variants"][0]["sku"];
		}

		$productObj = $currUser->products()->find($product_id);
		$shopifyproductid = $productObj->shopifyproductid;
		$oldtitle = $productObj->title;
		$olddescription = $productObj->description;

		$titleChanged = false;
		$descriptionChanged = false;

		if ($newtitle != '' && $newtitle != $oldtitle) {
			$titleChanged = true;
			$productObj->title = $newtitle;
		}

		if ($newdescription != '' && $newdescription != $olddescription) {
			$descriptionChanged = true;
			$productObj->description = $newdescription;
		}

		if ($titleChanged || $descriptionChanged) {
			$productObj->save();
			if ($shopifyproductid != '') {
	  			$feature1 = $productObj->feature1;
				$feature2 = $productObj->feature2;
				$feature3 = $productObj->feature3;
				$feature4 = $productObj->feature4;
				$feature5 = $productObj->feature5;
				$featureStr = "";
				if (strlen($feature1) > 0) {
					$featureStr .= '<li>' . $feature1 . '</li>';
				}
				if (strlen($feature2) > 0) {
					$featureStr .= '<li>' . $feature2 . '</li>';
				}
				if (strlen($feature3) > 0) {
					$featureStr .= '<li>' . $feature3 . '</li>';
				}
				if (strlen($feature4) > 0) {
					$featureStr .= '<li>' . $feature4 . '</li>';
				}
				if (strlen($feature5) > 0) {
					$featureStr .= '<li>' . $feature5 . '</li>';
				}
				if (strlen($featureStr) > 0) {
					$featureStr = '<br /><ul>' . $featureStr . '</ul>';
				}
				$newdescription = $newdescription.$featureStr;
				$data = array("product" => array("id" => $shopifyproductid, "title" => $newtitle, "body_html" => $newdescription));
				updateShopifyProduct($token, $shopurl, $shopifyproductid, $data);
			}
		}

		$variantObj = $currUser->variants()->where("product_id", $product_id)->first();
		$shopifyvariantid = $variantObj->shopifyvariantid;
		$oldsku = $variantObj->sku;

		if ($newsku != '' && $newsku != $oldsku) {
			$variantObj->sku = $newsku;
			$variantObj->save();
			$data = array("variant" => array("id" => $shopifyvariantid, "sku" => $newsku));
			updateShopifyVariant($token, $shopurl, $shopifyvariantid, $data);
		}

		return response()->json(['success' => $productObj], 200);
	}

	public function asin_search(Request $request)
	{

		$per_page = \Request::get('per_page') ?: 10;
		$page = \Request::get('page') ?: 1;
		if ($page > 10) {
			return response()->json(['error' => ["msg" => ['Please refine your search criteria.']]], 406);
		}
		$currUser = Auth::User();
		$amzKey = $currUser->amzKey()->first();

		if (!$amzKey) {
			return response()->json(['error' => ["msg" => ['Amazon AWS keys are required for this operation.']]], 406);
		}

		$validator = Validator::make($request->all(), [
			'keyword' => 'required',
			'category' => 'required'
		]);
		if ($validator->fails()) {
			return response()->json(['error' => $validator->errors()], 406);
		}

		$keyword = trim($request->input("keyword"));
		$category = trim($request->input("category"));

		try {
			$payload = "{"
				. " \"Keywords\": \"" . $keyword . "\","
				. " \"Resources\": ["
				. "  \"BrowseNodeInfo.BrowseNodes\","
				. "  \"BrowseNodeInfo.BrowseNodes.Ancestor\","
				. "  \"BrowseNodeInfo.BrowseNodes.SalesRank\","
				. "  \"BrowseNodeInfo.WebsiteSalesRank\","
				. "  \"CustomerReviews.Count\","
				. "  \"CustomerReviews.StarRating\","
				. "  \"Images.Primary.Small\","
				. "  \"Images.Primary.Medium\","
				. "  \"Images.Primary.Large\","
				. "  \"Images.Variants.Small\","
				. "  \"Images.Variants.Medium\","
				. "  \"Images.Variants.Large\","
				. "  \"ItemInfo.ByLineInfo\","
				. "  \"ItemInfo.ContentInfo\","
				. "  \"ItemInfo.ContentRating\","
				. "  \"ItemInfo.Classifications\","
				. "  \"ItemInfo.ExternalIds\","
				. "  \"ItemInfo.Features\","
				. "  \"ItemInfo.ManufactureInfo\","
				. "  \"ItemInfo.ProductInfo\","
				. "  \"ItemInfo.TechnicalInfo\","
				. "  \"ItemInfo.Title\","
				. "  \"ItemInfo.TradeInInfo\","
				. "  \"Offers.Listings.Availability.MaxOrderQuantity\","
				. "  \"Offers.Listings.Availability.Message\","
				. "  \"Offers.Listings.Availability.MinOrderQuantity\","
				. "  \"Offers.Listings.Availability.Type\","
				. "  \"Offers.Listings.Condition\","
				. "  \"Offers.Listings.Condition.ConditionNote\","
				. "  \"Offers.Listings.Condition.SubCondition\","
				. "  \"Offers.Listings.DeliveryInfo.IsAmazonFulfilled\","
				. "  \"Offers.Listings.DeliveryInfo.IsFreeShippingEligible\","
				. "  \"Offers.Listings.DeliveryInfo.IsPrimeEligible\","
				. "  \"Offers.Listings.DeliveryInfo.ShippingCharges\","
				. "  \"Offers.Listings.IsBuyBoxWinner\","
				. "  \"Offers.Listings.LoyaltyPoints.Points\","
				. "  \"Offers.Listings.MerchantInfo\","
				. "  \"Offers.Listings.Price\","
				. "  \"Offers.Listings.ProgramEligibility.IsPrimeExclusive\","
				. "  \"Offers.Listings.ProgramEligibility.IsPrimePantry\","
				. "  \"Offers.Listings.Promotions\","
				. "  \"Offers.Listings.SavingBasis\","
				. "  \"Offers.Summaries.HighestPrice\","
				. "  \"Offers.Summaries.LowestPrice\","
				. "  \"Offers.Summaries.OfferCount\","
				. "  \"ParentASIN\","
				. "  \"RentalOffers.Listings.Availability.MaxOrderQuantity\","
				. "  \"RentalOffers.Listings.Availability.Message\","
				. "  \"RentalOffers.Listings.Availability.MinOrderQuantity\","
				. "  \"RentalOffers.Listings.Availability.Type\","
				. "  \"RentalOffers.Listings.BasePrice\","
				. "  \"RentalOffers.Listings.Condition\","
				. "  \"RentalOffers.Listings.Condition.ConditionNote\","
				. "  \"RentalOffers.Listings.Condition.SubCondition\","
				. "  \"RentalOffers.Listings.DeliveryInfo.IsAmazonFulfilled\","
				. "  \"RentalOffers.Listings.DeliveryInfo.IsFreeShippingEligible\","
				. "  \"RentalOffers.Listings.DeliveryInfo.IsPrimeEligible\","
				. "  \"RentalOffers.Listings.DeliveryInfo.ShippingCharges\","
				. "  \"RentalOffers.Listings.MerchantInfo\","
				. "  \"SearchRefinements\""
				. " ],"
				. " \"ItemPage\": " . $page . ","
				. " \"PartnerTag\": \"" . $amzKey->associate_id . "\","
				. " \"PartnerType\": \"Associates\","
				. " \"Marketplace\": \"www.amazon." . $amzKey->country . "\""
				. "}";
			$path1 = 'searchitems';
			$path2 = 'SearchItems';
			$response = getawsdata($amzKey->aws_access_id, $amzKey->aws_secret_key, $amzKey->country, $payload, $path1, $path2);

			$result = json_decode($response, true);
			$totalResults = $result['SearchResult']['TotalResultCount'];
			if ($totalResults == 0) {
				return response()->json(['error' => ["msg" => ['No result found for given criteria.']]], 406);
			}
			$products = array();
			$length = count($result['SearchResult']['Items']);
			if ($length < 1) {
				return response()->json(['error' => ["msg" => ['No result found for given criteria.']]], 406);
			}
			$items = $result['SearchResult']['Items'];
			if (!isset($items[0]['ASIN'])) {
				$items = array($items);
			}
			foreach ($items as $item) {
				$newProduct = array();
				$newProduct['ASIN'] = $item['ASIN'];

				if (isset($item['Images'])) {
					$newProduct['Image'] = $item['Images']['Primary']['Large']['URL'];
				} else {
					$newProduct['Image'] = '';
				}

				if (isset($item['ParentASIN'])) {
					$newProduct['ParentASIN'] = $item['ParentASIN'];
				} else {
					$newProduct['ParentASIN'] = '';
				}

				if (isset($item['ItemInfo']['Title'])) {
					$newProduct['Title'] = $item['ItemInfo']['Title']['DisplayValue'];
				} else {
					$newProduct['Title'] = '';
				}
				$products[] = $newProduct;
			}

			$models = array();
			foreach ($products as $product) {
				$models[] = new Product(array("image" => $product['Image'], "asin" => $product['ASIN'], "parentasin" => $product['ParentASIN'], "title" => $product['Title']));
			}
			$myCollection = new Collection($models);

			return new \Illuminate\Pagination\LengthAwarePaginator($myCollection, $totalResults, 10,  $page, ['path' => $request->url(), 'query' => $request->query()]);
		} catch (\Exception $e) {
			Log::info($e->getMessage());
			return response()->json(['error' => ["msg" => ['There were some error processing this request. Please try again.']]], 406);
		}
	}


	public function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	public function fetchReviews(Request $request)
	{
		$per_page = \Request::get('per_page') ?: 20;
		$currUser = Auth::User();
		Log::info($request->id);
		Log::info($currUser);
		return response()->json(Reviews::where('user_id', $currUser->id)->orderBy('id', 'asc')->paginate($per_page), 200);
	}

	public function fetchAmzReviews(Request $request)
	{
		Log::info('fetchAmzReviews');
		$currUser = Auth::User();
		$id = \Request::get('id');
		Log::info($id);

		$reviewsthismonth = fetchReviews::where('user_id', $currUser->id)->where('updated_at', '>', Carbon::now()->subDays(30)->toDateTimeString());
		$reviewsthismonth = json_decode(json_encode($reviewsthismonth), true);
		$reviewsCnt = count($reviewsthismonth);
		if ($currUser->plan == 2 && $reviewsCnt >= 100) {
			return response()->json(['You need to upgrade your plan to request reviews '], 200);
		}

		if ($currUser->plan == 3 && $reviewsCnt >= 500) {
			return response()->json(['You need to upgrade your plan to request reviews '], 200);
		}
		if ($currUser->plan == 4 && $reviewsCnt >= 1000) {
			return response()->json(['You need to upgrade your plan to reuest reviews '], 200);
		}


		$permission = fetchReviews::where('user_id', $currUser->id)->where('product_asin', strval($id))->get();
		if (sizeof($permission) == 0) {
			Log::info('fetch review request created');
			fetchReviews::create([
				"product_asin" => $id,
				"user_id" => $currUser->id,
				"status" => 0
			]);
			$variants = ProductVariant::where("asin", $id)->where("user_id", $currUser->id)->get();
			if (sizeof($variants) > 0) {
				//echo 'inside size of variants>0';
				$product_id = $variants[0]->product_id;
				$perm = Product::find($product_id);
				//echo 'printing $perm';
				//$res = exec("php tt.php ".$currUser->id." local");

				//print_r($perm);
				//echo $perm['shopifyproductid'];
				if ($perm) {
					$perm->update([
						"reviews" => -1
					]);
				} else {
					Log::info("ERROR in updating reviews in products");
					Log::info($variants);
				}
			}

			return response()->json([' Request to fetch reviews has been submitted'], 200);
		} else {
			Log::info('fetch review request created');
			return response()->json(['Request to fetch reviews is already in progress.'], 200);
		}
	}

	public function downloadAllSelected($id)
	{
		ini_set("memory_limit", "-1");
		Log::info("download reviews");
		Log::info($id);
		$ids = explode(',', $id);

		$currUser = Auth::User();

		//$products = $currUser->variants()->whereIn('asin',$ids)->with('reviews')->get();
		$products = $currUser->variants()->whereIn('product_id', $ids)->with('reviews')->get();
		//Log::info(sizeof($products));
		//$reviews = Reviews::where("user_id",$currUser->id)->where("product_asin",$id)->get()->toArray();
		// $products = $currUser->variants()->with('reviews')->get();
		if (sizeof($products) > 0) {
			$headers = ['Content-Type: application/csv'];
			//$newName = 'reviews-csv-file-'.time().'.csv';

			$filename = 'reviewsAll' . time() . '.csv';
			$counter = 0;
			$FH = fopen($filename, 'w');
			fputcsv($FH, ["product_handle", "state", "rating", "title", "author", "email", "location", "body", "reply", "created_at", "replied_at"]);
			foreach ($products as $key => $reviews) {
				$review = $reviews->reviews;
				Log::info($review);
				if (sizeof($review) > 0) {
					$counter = 1;
					//array_unshift($review, array_keys($review[0]));
					foreach ($review as $key => $row) {
						//Log::info($row);
						if ($key != 0) {
							$formatted_datetime = date("d/m/y, H:i:s", strtotime($row['reviewDate']));
							$reviewDetails = strip_tags($row['reviewDetails'], '<br>');
							$reviewDetails = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $reviewDetails);
							fputcsv($FH, [$reviews['handle'], $row['status'], $row['rating'], trim(strip_tags($row['reviewTitle'], '<br>')), strip_tags($row['authorName'], '<br>'), $currUser->email, "", trim(strip_tags($reviewDetails)), "", $formatted_datetime, ""]);
						}
					}
				}
			}
			if ($counter == 0) {
				return response()->json(["No Reviews Found"], 404);
			}
			fclose($FH);
			return response()->json(['https://shopify.infoshore.biz/aac/api/public/' . $filename], 200);
		} else {
			return response()->json(["No Reviews Found"], 404);
		}
	}

	public function downloadAllSelected_14308($id)
	{
		$ids = explode(',', $id);
		$currUser = Auth::User();

		$products = $currUser->variants()->with('reviews')->get()->toArray();

		Log::info(count($products));
		//$reviews = Reviews::where("user_id",$currUser->id)->where("product_asin",$id)->get()->toArray();

		if (count($products) > 0) {
			$headers = ['Content-Type: application/csv'];
			//$newName = 'reviews-csv-file-'.time().'.csv';

			$filename = 'reviewsAll' . time() . '.csv';
			$counter = 0;
			$FH = fopen($filename, 'w');
			fputcsv($FH, ["product_handle", "state", "rating", "title", "author", "email", "location", "body", "reply", "created_at", "replied_at"]);
			foreach ($products as $key => $reviews) {
				$reviewArr = $reviews['reviews'];
				$reviewCnt = count($reviewArr);
				if ($reviewCnt > 0) {
					$counter = 1;
					if ($reviewCnt > 50) {
						$reviewCnt = 50;
					}
					$randCnt = rand(1, $reviewCnt);
					$random_keys = array_rand($reviewArr, $randCnt);
					foreach ($random_keys as $key) {
						$row = $reviewArr[$key];
						$formatted_datetime = date("d/m/y, H:i:s", strtotime($row['reviewDate']));
						$reviewDetails = strip_tags($row['reviewDetails'], '<br>');
						$reviewDetails = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $reviewDetails);
						fputcsv($FH, [$reviews['handle'], $row['status'], $row['rating'], trim(strip_tags($row['reviewTitle'], '<br>')), strip_tags($row['authorName'], '<br>'), $currUser->email, "", trim(strip_tags($reviewDetails)), "", $formatted_datetime, ""]);
					}
				}
			}
			if ($counter == 0) {
				return response()->json(["No Reviews Found"], 404);
			}
			fclose($FH);
			return response()->json(['https://shopify.infoshore.biz/aac/api/public/' . $filename], 200);
		} else {
			return response()->json(["No Reviews Found"], 404);
		}
	}

	public function downloadAllSelected_17789($id)
	{
		ini_set('memory_limit', '2048M');
		$ids = explode(',', $id);
		$currUser = Auth::User();

		$products = $currUser->variants()->with('reviews')->get()->toArray();

		Log::info(count($products));
		//$reviews = Reviews::where("user_id",$currUser->id)->where("product_asin",$id)->get()->toArray();

		if (count($products) > 0) {
			$headers = ['Content-Type: application/csv'];
			//$newName = 'reviews-csv-file-'.time().'.csv';

			$filename = 'reviewsAll' . time() . '.csv';
			$counter = 0;
			$FH = fopen($filename, 'w');
			fputcsv($FH, ["product_handle", "state", "rating", "title", "author", "email", "location", "body", "reply", "created_at", "replied_at"]);
			foreach ($products as $key => $reviews) {
				$handle = trim($reviews['handle']);
				if ($handle == "") {
					continue;
				}
				$reviewArr = $reviews['reviews'];
				$reviewCnt = count($reviewArr);
				if ($reviewCnt > 0) {
					$counter = 1;
					if ($reviewCnt > 30) {
						$reviewCnt = 30;
					}
					$randCnt = rand(1, $reviewCnt);
					$random_keys = array_rand($reviewArr, $randCnt);
					if (!is_array($random_keys)) {
						continue;
					}
					foreach ($random_keys as $key) {
						$row = $reviewArr[$key];
						$formatted_datetime = date("d/m/y, H:i:s", strtotime($row['reviewDate']));
						$reviewDetails = strip_tags($row['reviewDetails'], '<br>');
						$reviewDetails = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $reviewDetails);
						fputcsv($FH, [$reviews['handle'], $row['status'], $row['rating'], trim(strip_tags($row['reviewTitle'], '<br>')), strip_tags($row['authorName'], '<br>'), $currUser->email, "", trim(strip_tags($reviewDetails)), "", $formatted_datetime, ""]);
					}
				}
			}
			if ($counter == 0) {
				return response()->json(["No Reviews Found"], 404);
			}
			fclose($FH);
			return response()->json(['https://shopify.infoshore.biz/aac/api/public/' . $filename], 200);
		} else {
			return response()->json(["No Reviews Found"], 404);
		}
	}

	public function exportProducts($id)
	{
		Log::info("download products");
		Log::info($id);
		$ids = explode(',', $id);
		$currUser = Auth::User();

		$products = $currUser->products()->whereIn('product_id', $ids)->with('variants')->get();
		Log::info(sizeof($products));

		if (sizeof($products) > 0) {
			$headers = ['Content-Type: application/csv'];
			$newName = 'products-csv-file-' . time() . '.csv';

			$filename = 'productsAll' . time() . '.csv';
			$counter = 0;
			$FH = fopen($filename, 'w');
			fputcsv($FH, ["Asin", "title", "description", "product Url", "price", "quantity", "created_at"]);
			foreach ($products as $key => $value) {
				$variant = $value->variants[0];
				//print_r($variant);
				$formatted_datetime = date("d/m/y, H:i:s", strtotime($variant['created_at']));
				Log::info($variant);
				if (sizeof($variant) > 0) {
					fputcsv($FH, [$variant['asin'], $value['title'], $value['description'], $variant['detail_page_url'], $variant['price'], $variant['quantity'], $formatted_datetime]);
				}
				$counter = 1;
			}
			if ($counter == 0) {
				return response()->json(["No Products Found"], 404);
			}
			fclose($FH);
			return response()->json(['https://shopify.infoshore.biz/aac/api/public/' . $filename], 200);
		} else {
			return response()->json(["No Products Found"], 404);
		}
	}

	public function syncAllSelected($id)
	{
		Log::info("syncAll Selected");
		$temps = explode(",", $id);
		$count = count($temps);
		Log::info($id);
		if ($count == 0) {
			return response()->json(['error' => ["msg" => ["Please choose atleast one product."]]], 406);
		}
		$currUser = Auth::User();

		foreach ($temps as $key => $temp) {
			$retryflag = 1;
			Log::info('Syncing price and quant of ' . $key . ' product ' . $temp);
			$res = $this->forceSync($temp);
			if (!$res && $retryflag == 1) {
				$retryflag++;
				$res = $this->forceSync($temp);
			}
		}
		return response()->json('success', 200);
	}

	public function fetchAllSelected($id)
	{
		Log::info("fetchAllSelected");
		$temps = explode(",", $id);
		$count = count($temps);
		Log::info($temps);
		$currUser = Auth::User();
		if ($count == 0) {
			return response()->json(['error' => ["msg" => ["Please choose atleast one product."]]], 406);
		}

		foreach ($temps as $key => $temp) {
			Log::info($temp);
			$asin = ProductVariant::where('product_id', $temp)->where('user_id', $currUser->id)->get();
			if (sizeof($asin) > 0) {
				$permission = fetchReviews::where('product_asin', $asin[0]->asin)->get();
				if (sizeof($permission) == 0) {
					fetchReviews::create([
						"product_asin" => $asin[0]->asin,
						"user_id" => $currUser->id,
						"status" => 0
					]);
				}
			} else {
				return response()->json(['error' => ['msg' => ['Error in Registring request']]], 406);
			}
		}
		return response()->json('Request to fetch reviews is already in progress.', 200);
	}

	public function hasReviews(Request $request)
	{
		Log::info("has reviews");
		$currUser = Auth::User();
		$id = \Request::get('id');
		$permission = fetchReviews::where('user_id', $currUser->id)->where('product_asin', $id)->first();
		if (sizeof($permission) > 0) {
			Log::info($permission);
			//if($permission)
			return response()->json(['Success'], 200);
		} else {
			return response()->json(['Failure'], 406);
		}
	}

	// public function addProductByCrawl(Request $request)
	// {

	// 	$currUser = Auth::User();
	// 	$user_id = $currUser->id;
	// 	$producturl = '';
	// 	if ($request->has('producturl')) {
	// 		$producturl = trim($request->input('producturl'));
	// 	}
	// 	Log::info("crawling start111");
	// 	if ($currUser->skuconsumed >= $currUser->skulimit) {
	// 		$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Import limit exceeded', "type" => 'Account'));
	// 		$failed_productimports->save();
	// 		return response()->json(['error' => ["msg" => ["Import limit exceeded. Please upgrade your plan."]], ['purl' => $producturl]], 406);
	// 	}
	// 	$validator = Validator::make($request->all(), [
	// 		'producturl' => 'required'
	// 	]);
	// 	if ($validator->fails()) {
	// 		Log::info("validator Error");
	// 		$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Not a valid product URL.', "type" => 'Validation'));
	// 		$failed_productimports->save();
	// 		return response()->json(['error' => ["msg" => ["Please enter a valid product URL."]], ['purl' => $producturl]], 406);
	// 	}

	// 	if (strlen($producturl) == 0) {
	// 		Log::info("Null Url");
	// 		$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Not a valid product URL.', "type" => 'Validation'));
	// 		$failed_productimports->save();
	// 		return response()->json(['error' => ["msg" => ["Please enter a valid product URL."]], ['purl' => $producturl]], 406);
	// 	}

	// 	/// Checking for existing product ///

	// 	$tmpurl = strtok($producturl, '?');
	// 	Log::info($tmpurl);
	// 	$res = preg_match_all("/dp\/(.*)\/ref/U", $tmpurl . "/ref", $matches);
	// 	if ($res) {
	// 		Log::info("278");
	// 		Log::info($matches[1][0]);
	// 		$permission = ProductVariant::where("user_id", $currUser->id)->where("asin", strtok($matches[1][0], '/'))->get();
	// 		if (sizeof($permission) > 0) {
	// 			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Product Already Exists', "type" => 'Validation'));
	// 			$failed_productimports->save();
	// 			return response()->json(['error' => ["msg" => ["Product Already Exists"]], ['purl' => $producturl]], 406);
	// 		}
	// 		$res = preg_match_all("/\/*([A-Z0-9]*)\/*ref/s", $tmpurl . "/ref", $matches);
	// 		Log::info("289");
	// 		if ($res) {
	// 			Log::info("292");
	// 			Log::info($matches);
	// 			foreach ($matches[1] as $key => $value) {
	// 				if (strlen($value) > 7) {
	// 					$permission = ProductVariant::where("user_id", $currUser->id)->where("asin", $matches[1][0])->get();
	// 					if (sizeof($permission) > 0) {
	// 						$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Product Already Exists', "type" => 'Validation'));
	// 						$failed_productimports->save();
	// 						return response()->json(['error' => ["msg" => ["Product Already Exists"]], ['purl' => $producturl]], 406);
	// 					} else {
	// 						Log::info($permission);
	// 					}
	// 				}
	// 				//Log::info($value);
	// 			}
	// 			//Log::info($matches);
	// 		}
	// 	} else {
	// 		$res = preg_match_all("/\/([A-Z0-9]*)\/ref/sU", $tmpurl . "/ref", $matches);
	// 		if ($res) {
	// 			$permission = ProductVariant::where("user_id", $currUser->id)->where("asin", $matches[1][0])->get();
	// 			if (sizeof($permission) > 0) {
	// 				$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Product Already Exists', "type" => 'Validation'));
	// 				$failed_productimports->save();
	// 				return response()->json(['error' => ["msg" => ["Product Already Exists"]], ['purl' => $producturl]], 406);
	// 			} else {
	// 				Log::info($permission);
	// 			}
	// 		}
	// 		Log::info("ASIN Not Found in database downloading product ...");
	// 	}
	// 	$domainVerification = verifyAmazonDomain($producturl);
	// 	if (!$domainVerification) {
	// 		Log::info("Domain Variation error");
	// 		$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Not a valid product URL.', "type" => 'Validation'));
	// 		$failed_productimports->save();
	// 		return response()->json(['error' => ["msg" => ["Please enter a valid product URL."]], ['purl' => $producturl]], 406);
	// 	}
	// 	Log::info("starting surrent user");
	// 	$domain = parse_url($producturl, PHP_URL_HOST);

	// 	//if($domain == "amazon.com" || $domain == "www.amazon.com"){
	// 	$time_start = $this->microtime_float();
	// 	Log::info($producturl);
	// 	$data = null;
	// 	/*if($data == null){
	// 		$data = get_html_scraper_api_content($producturl,$user_id);
	// 	    //$data = $this->get_html_luminato_crawl_content($producturl);
	// 	    if($data == null){
	// 	        Log::info("Luminato Not Worked");
	// 	    }
	// 	}*/
	// 	if ($data == null && $user_id != 17012 && $user_id != 20892) {
	// 		Log::info($producturl);
	// 		$data = proxycrawlapi($producturl);
	// 	}

	// 	if ($data == null) {
	// 		$data = get_html_scraper_api_content($producturl, $user_id);
	// 		//$data = $this->get_html_luminato_crawl_content($producturl);
	// 		if ($data == null) {
	// 			Log::info("Luminato Not Worked");
	// 		}
	// 	}
	// 	/*if($data == null){
	// 		$data = get_html_luminato2_crawl_content($producturl,$user_id);
	// 	}*/

	// 	if ($data == null) {
	// 		@mail("pankajnarang81@gmail.com", "ProductController: all proxy blocked and luminiti.i failed too critical state", "failed" . $producturl);
	// 	}

	// 	if ($data != null) {
	// 		$res1 = $data['message'];
	// 		$resObj = json_decode($data['message'], true);
	// 		$time_end = $this->microtime_float();
	// 		$time = $time_end - $time_start;
	// 		Log::info("Did crawling in $time seconds\n");
	// 		Log::info($resObj);
	// 		if (isset($resObj['Title'])) {
	// 			$results = $resObj;
	// 			$title = "";
	// 			$description = "";
	// 			$brand = "";
	// 			$product_type = "";
	// 			$asin = "";
	// 			$url = "";
	// 			$price = 0;
	// 			$list_price = 0;
	// 			$images = "";
	// 			$currency = "";
	// 			$feature1 = "";
	// 			$feature2 = "";
	// 			$feature3 = "";
	// 			$feature4 = "";
	// 			$feature5 = "";
	// 			$quantity = 0;
	// 			if (isset($results['Title'])) {
	// 				$title = $results['Title'];
	// 			}

	// 			if (isset($results['description'])) {
	// 				$description = $results['description'];
	// 			}
	// 			if (isset($results['brand'])) {
	// 				$brand = $results['brand'];
	// 			}
	// 			if (isset($results['category'])) {
	// 				$product_type = $results['category'];
	// 			}
	// 			if (isset($results['asin'])) {
	// 				$asin = $results['asin'];
	// 			}
	// 			if (isset($results['url'])) {
	// 				$url = $results['url'];
	// 			}
	// 			if (isset($results['price'])) {
	// 				$price = $results['price'];
	// 				$price = getAmount($price);
	// 			}

	// 			if (isset($results['list_price'])) {
	// 				$list_price = $results['list_price'];
	// 				$list_price = getAmount($list_price);
	// 			} else {
	// 				$list_price = $price;
	// 			}
	// 			if (isset($results['currency'])) {
	// 				$currency = $results['currency'];
	// 			}
	// 			if ($currency == "") {
	// 				$currency = verifyCurrency($producturl);
	// 			}
	// 			if (isset($results['in_stock___out_of_stock']) && $results['in_stock___out_of_stock'] == 'In stock.') {
	// 				$quantity = 1;
	// 			}
	// 			if (isset($results['high_resolution_image_urls'])) {
	// 				$high_resolution_image_urls = $results['high_resolution_image_urls'];
	// 				$images = explode("|", $high_resolution_image_urls);
	// 				$images = array_map("trim", $images);
	// 			}

	// 			if (isset($results['bullet_points'])) {
	// 				$bullet_points = $results['bullet_points'];
	// 				//$tempArr = explode("|", $bullet_points);
	// 				//$tempArr = array_map("trim", $tempArr);
	// 				$tempArr = $bullet_points;
	// 				$feature1 = isset($tempArr[0]) ? $tempArr[0] : "";
	// 				$feature2 = isset($tempArr[1]) ? $tempArr[1] : "";
	// 				$feature3 = isset($tempArr[2]) ? $tempArr[2] : "";
	// 				$feature4 = isset($tempArr[3]) ? $tempArr[3] : "";
	// 				$feature5 = isset($tempArr[4]) ? $tempArr[4] : "";
	// 			}

	// 			if (isset($results['bullet_points']) && isset($results['bullet_points'][0])) {
	// 				$description = $description . implode(" ", $results['bullet_points']);
	// 			}

	// 			Log::info("saving product");
	// 			if ($price < 1) {
	// 				$productObject = new Product(array("title" => $title, "description" => $description, "feature1" => $feature1, "feature2" => $feature2, "feature3" => $feature3, "feature4" => $feature4, "feature5" => $feature5, "brand" => $brand, "product_type" => $product_type, "raw_data" => $res1, "status" => 'Incomplete'));
	// 			} else {
	// 				$productObject = new Product(array("title" => $title, "description" => $description, "feature1" => $feature1, "feature2" => $feature2, "feature3" => $feature3, "feature4" => $feature4, "feature5" => $feature5, "brand" => $brand, "product_type" => $product_type, "raw_data" => $res1, "status" => 'Imported'));
	// 			}
	// 			$currUser->products()->save($productObject);
	// 			Log::info("saving product variant");
	// 			$variantObject = new ProductVariant(array("sku" => $asin, "asin" => $asin, "barcode" => "", "price" => $list_price, "saleprice" => $price, "currency" => $currency, "detail_page_url" => $producturl, "user_id" => $currUser->id));
	// 			$productObject->variants()->save($variantObject);
	// 			Log::info("saving product images");
	// 			foreach ($images as $imageUrl) {
	// 				Log::info($imageUrl);
	// 				if ($imageUrl == "") {
	// 					continue;
	// 				}
	// 				$productImageObject = new ProductImage(array("asin" => $asin, "imgurl" => $imageUrl, "user_id" => $currUser->id));
	// 				Log::info($currUser->id);
	// 				$variantObject->images()->save($productImageObject);
	// 			}

	// 			$currUser->skuconsumed = $currUser->skuconsumed + 1;
	// 			$currUser->save();
	// 			Log::info("Importing to shopyfy ");
	// 			Log::info("return 200");
	// 			if ($price > 0) {
	// 				$this->insertToShopify($productObject); // TODO: Are we adding products to shopify using this method or public/inserttoShopify.
	// 			}
	// 			$time_end1 = $this->microtime_float();
	// 			$time1 = $time_end1 - $time_start;
	// 			Log::info("Did crawling in $time1 seconds\n");
	// 			$res_data = Product::where('product_id', $productObject->product_id)->where('user_id', $currUser->id)->with('variantsCount')->with('variants.mainImage')->orderBy('product_id', 'DESC')->get();
	// 			Log::info("response Data");
	// 			Log::info($res_data);
	// 			return response()->json(['success', $res_data], 200);
	// 		} else {
	// 			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist.', "type" => 'Database'));
	// 			$failed_productimports->save();
	// 			return response()->json(['error' => ["msg" => ["There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist."]], ['purl' => $producturl]], 406);
	// 		}
	// 	} else {
	// 		$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist.', "type" => 'Database'));
	// 		$failed_productimports->save();
	// 		return response()->json(['error' => ["msg" => ["There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist."]], ['purl' => $producturl]], 406);
	// 	}
	// }

	public function addProductByCrawl(Request $request)
	{

		$currUser = Auth::User();
		$user_id = $currUser->id;
		$producturl = '';
		if ($request->has('producturl')) {
			$producturl = trim($request->input('producturl'));
		}
		Log::info("crawling start111");
		if ($currUser->skuconsumed >= $currUser->skulimit) {
			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Import limit exceeded', "type" => 'Account'));
			$failed_productimports->save();
			return response()->json(['error' => ["msg" => ["Import limit exceeded. Please upgrade your plan."]], ['purl' => $producturl]], 406);
		}
		$validator = Validator::make($request->all(), [
			'producturl' => 'required'
		]);
		if ($validator->fails()) {
			Log::info("validator Error");
			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Not a valid product URL.', "type" => 'Validation'));
			$failed_productimports->save();
			return response()->json(['error' => ["msg" => ["Please enter a valid product URL."]], ['purl' => $producturl]], 406);
		}

		if (strlen($producturl) == 0) {
			Log::info("Null Url");
			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Not a valid product URL.', "type" => 'Validation'));
			$failed_productimports->save();
			return response()->json(['error' => ["msg" => ["Please enter a valid product URL."]], ['purl' => $producturl]], 406);
		}

		/// Checking for existing product ///

		$tmpurl = strtok($producturl, '?');
		Log::info($tmpurl);
		$res = preg_match_all("/dp\/(.*)\/ref/U", $tmpurl . "/ref", $matches);
		if ($res) {
			Log::info("278");
			Log::info($matches[1][0]);
			$permission = ProductVariant::where("user_id", $currUser->id)->where("asin", strtok($matches[1][0], '/'))->get();
			if (sizeof($permission) > 0) {
				$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Product Already Exists', "type" => 'Validation'));
				$failed_productimports->save();
				return response()->json(['error' => ["msg" => ["Product Already Exists"]], ['purl' => $producturl]], 406);
			}
			$res = preg_match_all("/\/*([A-Z0-9]*)\/*ref/s", $tmpurl . "/ref", $matches);
			Log::info("289");
			if ($res) {
				Log::info("292");
				Log::info($matches);
				foreach ($matches[1] as $key => $value) {
					if (strlen($value) > 7) {
						$permission = ProductVariant::where("user_id", $currUser->id)->where("asin", $matches[1][0])->get();
						if (sizeof($permission) > 0) {
							$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Product Already Exists', "type" => 'Validation'));
							$failed_productimports->save();
							return response()->json(['error' => ["msg" => ["Product Already Exists"]], ['purl' => $producturl]], 406);
						} else {
							Log::info($permission);
						}
					}
					//Log::info($value);
				}
				//Log::info($matches);
			}
		} else {
			$res = preg_match_all("/\/([A-Z0-9]*)\/ref/sU", $tmpurl . "/ref", $matches);
			if ($res) {
				$permission = ProductVariant::where("user_id", $currUser->id)->where("asin", $matches[1][0])->get();
				if (sizeof($permission) > 0) {
					$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Product Already Exists', "type" => 'Validation'));
					$failed_productimports->save();
					return response()->json(['error' => ["msg" => ["Product Already Exists"]], ['purl' => $producturl]], 406);
				} else {
					Log::info($permission);
				}
			}
			Log::info("ASIN Not Found in database downloading product ...");
		}
		$domainVerification = verifyAmazonDomain($producturl);
		if (!$domainVerification) {
			Log::info("Domain Variation error");
			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Not a valid product URL.', "type" => 'Validation'));
			$failed_productimports->save();
			return response()->json(['error' => ["msg" => ["Please enter a valid product URL."]], ['purl' => $producturl]], 406);
		}
		Log::info("starting surrent user");
		$domain = parse_url($producturl, PHP_URL_HOST);

		//if($domain == "amazon.com" || $domain == "www.amazon.com"){
		$time_start = $this->microtime_float();
		Log::info($producturl);
		$data = null;
		/*if($data == null){
			$data = get_html_scraper_api_content($producturl,$user_id);
		    //$data = $this->get_html_luminato_crawl_content($producturl);
		    if($data == null){
		        Log::info("Luminato Not Worked");
		    }
		}*/
		if ($data == null && $user_id != 17012 && $user_id != 20892) {
			$data = proxycrawlapi($producturl);

			log::info(json_encode($data ,true));
		}

		if ($data == null) {
			$data = get_html_scraper_api_content($producturl, $user_id);
			//$data = $this->get_html_luminato_crawl_content($producturl);
			if ($data == null) {
				Log::info("Luminato Not Worked");
			}
		}
		/*if($data == null){
			$data = get_html_luminato2_crawl_content($producturl,$user_id);
		}*/

		if ($data == null) {
			@mail("pankajnarang81@gmail.com", "ProductController: all proxy blocked and luminiti.i failed too critical state", "failed" . $producturl);
		}

		if ($data != null) {
			$res1 = $data['message'];
			$resObj = json_decode($data['message'], true);
			$time_end = $this->microtime_float();
			$time = $time_end - $time_start;
			Log::info("Did crawling in $time seconds\n");
			
			if (isset($resObj['Title'])) {
				$results = $resObj;
              
				$title = "";
				$description = "";
				$brand = "";
				$product_type = "";
				$asin = "";
				$url = "";
				$price = 0;
				$list_price = 0;
				$images = "";
				$currency = "";
				$feature1 = "";
				$feature2 = "";
				$feature3 = "";
				$feature4 = "";
				$feature5 = "";
				$option1name = "";
				$option2name = "";
				$quantity = 0;
				if (isset($results['Title'])) {
					$title = $results['Title'];
				}

				if (isset($results['description'])) {
					$description = $results['description'];
				}
				if (isset($results['brand'])) {
					$brand = $results['brand'];
				}
				if (isset($results['category'])) {
					$product_type = $results['category'];
				}
				if (isset($results['asin'])) {
					$asin = $results['asin'];
				}
               
                if (isset($results['asinVariationValues'])) {
					$asinVariationValues = $results['asinVariationValues'];
				}
				log::info("fffffffffffffffffffffffffdfjsdfjsjfsjfsfsjfjnfjsfjsjnfsfsj");
				log::info(json_encode($asinVariationValues, true));
				
				if (isset($results['url'])) {
					$url = $results['url'];
				}
				if (isset($results['price'])) {
					$price = $results['price'];
					$price = getAmount($price);
				}

				if (isset($results['list_price'])) {
					$list_price = $results['list_price'];
					$list_price = getAmount($list_price);
				} else {
					$list_price = $price;
				}
				if (isset($results['currency'])) {
					$currency = $results['currency'];
				}
				if ($currency == "") {
					$currency = verifyCurrency($producturl);
				}
				if (isset($results['in_stock___out_of_stock']) && $results['in_stock___out_of_stock'] == 'In stock.') {
					$quantity = 1;
				}
				if (isset($results['high_resolution_image_urls'])) {
					$high_resolution_image_urls = $results['high_resolution_image_urls'];
					$images = explode("|", $high_resolution_image_urls);
					$images = array_map("trim", $images);
				}

				if (isset($results['bullet_points'])) {
					$bullet_points = $results['bullet_points'];
					//$tempArr = explode("|", $bullet_points);
					//$tempArr = array_map("trim", $tempArr);
					$tempArr = $bullet_points;
					$feature1 = isset($tempArr[0]) ? $tempArr[0] : "";
					$feature2 = isset($tempArr[1]) ? $tempArr[1] : "";
					$feature3 = isset($tempArr[2]) ? $tempArr[2] : "";
					$feature4 = isset($tempArr[3]) ? $tempArr[3] : "";
					$feature5 = isset($tempArr[4]) ? $tempArr[4] : "";
				}

				if (isset($results['bullet_points']) && isset($results['bullet_points'][0])) {
					$description = $description . implode(" ", $results['bullet_points']);
				}

				Log::info("saving product");
				if ($price < 1) {
					$productObject = new Product(array("title" => $title, "description" => $description, "feature1" => $feature1, "feature2" => $feature2, "feature3" => $feature3, "feature4" => $feature4, "feature5" => $feature5,"option1name" => $option1name,"option2name" => $option2name,"brand" => $brand, "product_type" => $product_type, "raw_data" => $res1, "status" => 'Incomplete'));
				} else {





					$productObject = new Product(array("title" => $title, "description" => $description, "feature1" => $feature1, "feature2" => $feature2, "feature3" => $feature3, "feature4" => $feature4, "feature5" => $feature5,"option1name" => $option1name,"option2name" => $option2name, "brand" => $brand, "product_type" => $product_type, "raw_data" => $res1, "status" => 'Imported'));
				}
				$currUser->products()->save($productObject);
				Log::info("saving product variant");


				$option3val =" ";
				$sku = " ";
				$option1Val = [];
				$option2Val = [];
				$allarray = [];

				log::info(json_encode($resObj['asinVariationValues']  ,true));

				foreach ($resObj['asinVariationValues'] as $variation) {
            
					if ($variation['variationName'] === $option1name) {
						
						$option1Val[] = $variation['variationValue'];
		 
		
					 
					} elseif ($variation['variationName'] === $option2name) {

						$option2Val[] = $variation['variationValue'];
					
					}
				}
		
			
			  
		
				foreach ($option1Val as $option1Valset) {


					foreach ($option2Val as $option2Valset) {



                $variantObject = new ProductVariant(array("sku" => $asin, "asin" => $asin, "barcode" => "", "price" => $list_price, "saleprice" => $price, "option1val" => $option1val, "option2val" => $option2val, "currency" => $currency, "detail_page_url" => $producturl, "user_id" => $currUser->id));
				$productObject->variants()->save($variantObject);
				Log::info("saving product images");
				foreach ($images as $imageUrl) {
					Log::info($imageUrl);
					if ($imageUrl == "") {
						continue;
					}
					$productImageObject = new ProductImage(array("asin" => $asin, "imgurl" => $imageUrl, "user_id" => $currUser->id));
					Log::info($currUser->id);
					$variantObject->images()->save($productImageObject);
				}

				$currUser->skuconsumed = $currUser->skuconsumed + 1;
				$currUser->save();
				Log::info("Importing to shopyfy ");
				Log::info("return 200");

			}}
				if ($price > 0) {
					$this->insertToShopify($productObject); // TODO: Are we adding products to shopify using this method or public/inserttoShopify.
				}
				$time_end1 = $this->microtime_float();
				$time1 = $time_end1 - $time_start;
				Log::info("Did crawling in $time1 seconds\n");
				$res_data = Product::where('product_id', $productObject->product_id)->where('user_id', $currUser->id)->with('variantsCount')->with('variants.mainImage')->orderBy('product_id', 'DESC')->get();
				Log::info("response Data");
				Log::info($res_data);
				return response()->json(['success', $res_data], 200);
			} else {
				$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist.', "type" => 'Database'));
				$failed_productimports->save();
				return response()->json(['error' => ["msg" => ["There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist."]], ['purl' => $producturl]], 406);
			}
		} else {
			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist.', "type" => 'Database'));
			$failed_productimports->save();
			return response()->json(['error' => ["msg" => ["There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist."]], ['purl' => $producturl]], 406);
		}
	}



	
	
	
	





	public function addProductByCrawl1(Request $request)
	{
		$currUser = Auth::User();
		$user_id = $currUser->id;
		$producturl = '';
		if ($request->has('producturl')) {
			$producturl = trim($request->input('producturl'));
		}
		Log::info("crawling start11");
		if ($currUser->skuconsumed >= $currUser->skulimit) {
			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Import limit exceeded', "type" => 'Account'));
			$failed_productimports->save();
			return response()->json(['error' => ["msg" => ["Import limit exceeded. Please upgrade your plan."]], ['purl' => $producturl]], 406);
		}
		$validator = Validator::make($request->all(), [
			'producturl' => 'required'
		]);
		if ($validator->fails()) {
			Log::info("validator Error");
			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Not a valid product URL.', "type" => 'Validation'));
			$failed_productimports->save();
			return response()->json(['error' => ["msg" => ["Please enter a valid product URL."]], ['purl' => $producturl]], 406);
		}

		if (strlen($producturl) == 0) {
			Log::info("Null Url");
			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Not a valid product URL.', "type" => 'Validation'));
			$failed_productimports->save();
			return response()->json(['error' => ["msg" => ["Please enter a valid product URL."]], ['purl' => $producturl]], 406);
		}

		/// Checking for existing product ///

		$tmpurl = strtok($producturl, '?');
		Log::info($tmpurl);
		$res = preg_match_all("/dp\/(.*)\/ref/U", $tmpurl . "/ref", $matches);
		if ($res) {
			Log::info("278");
			Log::info($matches[1][0]);
			$permission = ProductVariant::where("user_id", $currUser->id)->where("asin", strtok($matches[1][0], '/'))->get();
			if (sizeof($permission) > 0) {
				$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Product Already Exists', "type" => 'Validation'));
				$failed_productimports->save();
				return response()->json(['error' => ["msg" => ["Product Already Exists"]], ['purl' => $producturl]], 406);
			}
			$res = preg_match_all("/\/*([A-Z0-9]*)\/*ref/s", $tmpurl . "/ref", $matches);
			Log::info("289");
			if ($res) {
				Log::info("292");
				Log::info($matches);
				foreach ($matches[1] as $key => $value) {
					if (strlen($value) > 7) {
						$permission = ProductVariant::where("user_id", $currUser->id)->where("asin", $matches[1][0])->get();
						if (sizeof($permission) > 0) {
							$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Product Already Exists', "type" => 'Validation'));
							$failed_productimports->save();
							return response()->json(['error' => ["msg" => ["Product Already Exists"]], ['purl' => $producturl]], 406);
						} else {
							Log::info($permission);
						}
					}
					//Log::info($value);
				}
				//Log::info($matches);
			}
		} else {
			$res = preg_match_all("/\/([A-Z0-9]*)\/ref/sU", $tmpurl . "/ref", $matches);
			if ($res) {
				$permission = ProductVariant::where("user_id", $currUser->id)->where("asin", $matches[1][0])->get();
				if (sizeof($permission) > 0) {
					$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Product Already Exists', "type" => 'Validation'));
					$failed_productimports->save();
					return response()->json(['error' => ["msg" => ["Product Already Exists"]], ['purl' => $producturl]], 406);
				} else {
					Log::info($permission);
				}
			}
			Log::info("ASIN Not Found in database downloading product ...");
		}
		$domainVerification = verifyAmazonDomain($producturl);
		if (!$domainVerification) {
			Log::info("Domain Variation error");
			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'Not a valid product URL.', "type" => 'Validation'));
			$failed_productimports->save();
			return response()->json(['error' => ["msg" => ["Please enter a valid product URL."]], ['purl' => $producturl]], 406);
		}
		Log::info("starting surrent user");
		$domain = parse_url($producturl, PHP_URL_HOST);

		//if($domain == "amazon.com" || $domain == "www.amazon.com"){
		$time_start = $this->microtime_float();
		Log::info($producturl);
		$data = null;
		/*if($data == null){
			$data = get_html_scraper_api_content($producturl,$user_id);
		    //$data = $this->get_html_luminato_crawl_content($producturl);
		    if($data == null){
		        Log::info("Luminato Not Worked");
		    }
		}*/
		if ($data == null && $user_id != 17012 && $user_id != 20892) {
			$data = proxycrawlapi($producturl);


			log::info(json_decode($data  ,true));
		}

		if ($data == null) {
			$data = get_html_scraper_api_content($producturl, $user_id);
			//$data = $this->get_html_luminato_crawl_content($producturl);
			if ($data == null) {
				Log::info("Luminato Not Worked");
			}
		}
		/*if($data == null){
			$data = get_html_luminato2_crawl_content($producturl,$user_id);
		}*/

		if ($data == null) {
			@mail("pankajnarang81@gmail.com", "ProductController: all proxy blocked and luminiti.i failed too critical state", "failed" . $producturl);
		}

		if ($data != null) {
			$res1 = $data['message'];
			$resObj = json_decode($data['message'], true);
			$time_end = $this->microtime_float();
			$time = $time_end - $time_start;
			Log::info("Did crawling in $time seconds\n");
			Log::info($resObj);
			if (isset($resObj['Title'])) {
				$results = $resObj;
				$title = "";
				$description = "";
				$brand = "";
				$product_type = "";
				$asin = "";
				$url = "";
				$price = 0;
				$list_price = 0;
				$images = "";
				$currency = "";
				$feature1 = "";
				$feature2 = "";
				$feature3 = "";
				$feature4 = "";
				$feature5 = "";
				$quantity = 0;
				if (isset($results['Title'])) {
					$title = $results['Title'];
				}

				if (isset($results['description'])) {
					$description = $results['description'];
				}
				if (isset($results['brand'])) {
					$brand = $results['brand'];
				}
				if (isset($results['category'])) {
					$product_type = $results['category'];
				}
				if (isset($results['asin'])) {
					$asin = $results['asin'];
				}
				if (isset($results['url'])) {
					$url = $results['url'];
				}
				if (isset($results['price'])) {
					$price = $results['price'];
					$price = getAmount($price);
				}

				if (isset($results['list_price'])) {
					$list_price = $results['list_price'];
					$list_price = getAmount($list_price);
				} else {
					$list_price = $price;
				}
				if (isset($results['currency'])) {
					$currency = $results['currency'];
				}
				if ($currency == "") {
					$currency = verifyCurrency($producturl);
				}
				if (isset($results['in_stock___out_of_stock']) && $results['in_stock___out_of_stock'] == 'In stock.') {
					$quantity = 1;
				}
				if (isset($results['high_resolution_image_urls'])) {
					$high_resolution_image_urls = $results['high_resolution_image_urls'];
					$images = explode("|", $high_resolution_image_urls);
					$images = array_map("trim", $images);
				}

				if (isset($results['bullet_points'])) {
					$bullet_points = $results['bullet_points'];
					//$tempArr = explode("|", $bullet_points);
					//$tempArr = array_map("trim", $tempArr);
					$tempArr = $bullet_points;
					$feature1 = isset($tempArr[0]) ? $tempArr[0] : "";
					$feature2 = isset($tempArr[1]) ? $tempArr[1] : "";
					$feature3 = isset($tempArr[2]) ? $tempArr[2] : "";
					$feature4 = isset($tempArr[3]) ? $tempArr[3] : "";
					$feature5 = isset($tempArr[4]) ? $tempArr[4] : "";
				}

				if (isset($results['bullet_points']) && isset($results['bullet_points'][0])) {
					$description = $description . implode(" ", $results['bullet_points']);
				}

				Log::info("saving product");
				if ($price < 1) {
					$productObject = new Product(array("title" => $title, "description" => $description, "feature1" => $feature1, "feature2" => $feature2, "feature3" => $feature3, "feature4" => $feature4, "feature5" => $feature5, "brand" => $brand, "product_type" => $product_type, "raw_data" => $res1, "status" => 'Incomplete'));
				} else {
					$productObject = new Product(array("title" => $title, "description" => $description, "feature1" => $feature1, "feature2" => $feature2, "feature3" => $feature3, "feature4" => $feature4, "feature5" => $feature5, "brand" => $brand, "product_type" => $product_type, "raw_data" => $res1, "status" => 'Imported'));
				}
				$currUser->products()->save($productObject);
				Log::info("saving product variant");
				$variantObject = new ProductVariant(array("sku" => $asin, "asin" => $asin, "barcode" => "", "price" => $list_price, "saleprice" => $price, "currency" => $currency, "detail_page_url" => $producturl, "user_id" => $currUser->id));
				$productObject->variants()->save($variantObject);
				Log::info("saving product images");
				foreach ($images as $imageUrl) {
					Log::info($imageUrl);
					if ($imageUrl == "") {
						continue;
					}
					$productImageObject = new ProductImage(array("asin" => $asin, "imgurl" => $imageUrl, "user_id" => $currUser->id));
					Log::info($currUser->id);
					$variantObject->images()->save($productImageObject);
				}

				$currUser->skuconsumed = $currUser->skuconsumed + 1;
				$currUser->save();
				Log::info("Importing to shopyfy ");
				Log::info("return 200");
				if ($price > 0) {
					$this->insertToShopify($productObject); // TODO: Are we adding products to shopify using this method or public/inserttoShopify.
				}
				$time_end1 = $this->microtime_float();
				$time1 = $time_end1 - $time_start;
				Log::info("Did crawling in $time1 seconds\n");
				$res_data = Product::where('product_id', $productObject->product_id)->where('user_id', $currUser->id)->with('variantsCount')->with('variants.mainImage')->orderBy('product_id', 'DESC')->get();
				Log::info("response Data");
				Log::info($res_data);
				return response()->json(['success', $res_data], 200);
			} else {
				$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist.', "type" => 'Database'));
				$failed_productimports->save();
				return response()->json(['error' => ["msg" => ["There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist."]], ['purl' => $producturl]], 406);
			}
		} else {
			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $request->input('producturl'), "reason" => 'There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist.', "type" => 'Database'));
			$failed_productimports->save();
			return response()->json(['error' => ["msg" => ["There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist."]], ['purl' => $producturl]], 406);
		}
	}

	public function createsingle($asin)
	{
		$temp = explode(",", $asin);
		$count = count($temp);
		if ($count == 0) {
			return response()->json(['error' => ["msg" => ["Please choose atleast one product."]]], 406);
		} else if ($count > 10) {
			return response()->json(['error' => ["msg" => ["Please choose less than 10 products."]]], 406);
		}

		$currUser = Auth::User();
		$amzKey = $currUser->amzKey()->first();
		if (!$amzKey) {
			return response()->json(['error' => ["msg" => ['Amazon AWS keys are required for this operation.']]], 406);
		}
		foreach ($temp as $t) {
			$vcount = $currUser->variants()->where('asin', $t)->count();
			if ($vcount != 0) {
				return response()->json(['error' => ["msg" => ['Product already exist.']]], 406);
				break;
			}
		}
		try {
			$payload = "{"
				. " \"ItemIds\": ["
				. "  \"" . $temp[0] . "\""
				. " ],"
				. " \"Resources\": ["
				. "  \"BrowseNodeInfo.BrowseNodes\","
				. "  \"BrowseNodeInfo.BrowseNodes.Ancestor\","
				. "  \"BrowseNodeInfo.BrowseNodes.SalesRank\","
				. "  \"BrowseNodeInfo.WebsiteSalesRank\","
				. "  \"CustomerReviews.Count\","
				. "  \"CustomerReviews.StarRating\","
				. "  \"Images.Primary.Small\","
				. "  \"Images.Primary.Medium\","
				. "  \"Images.Primary.Large\","
				. "  \"Images.Variants.Small\","
				. "  \"Images.Variants.Medium\","
				. "  \"Images.Variants.Large\","
				. "  \"ItemInfo.ByLineInfo\","
				. "  \"ItemInfo.ContentInfo\","
				. "  \"ItemInfo.ContentRating\","
				. "  \"ItemInfo.Classifications\","
				. "  \"ItemInfo.ExternalIds\","
				. "  \"ItemInfo.Features\","
				. "  \"ItemInfo.ManufactureInfo\","
				. "  \"ItemInfo.ProductInfo\","
				. "  \"ItemInfo.TechnicalInfo\","
				. "  \"ItemInfo.Title\","
				. "  \"ItemInfo.TradeInInfo\","
				. "  \"Offers.Listings.Availability.MaxOrderQuantity\","
				. "  \"Offers.Listings.Availability.Message\","
				. "  \"Offers.Listings.Availability.MinOrderQuantity\","
				. "  \"Offers.Listings.Availability.Type\","
				. "  \"Offers.Listings.Condition\","
				. "  \"Offers.Listings.Condition.ConditionNote\","
				. "  \"Offers.Listings.Condition.SubCondition\","
				. "  \"Offers.Listings.DeliveryInfo.IsAmazonFulfilled\","
				. "  \"Offers.Listings.DeliveryInfo.IsFreeShippingEligible\","
				. "  \"Offers.Listings.DeliveryInfo.IsPrimeEligible\","
				. "  \"Offers.Listings.DeliveryInfo.ShippingCharges\","
				. "  \"Offers.Listings.IsBuyBoxWinner\","
				. "  \"Offers.Listings.LoyaltyPoints.Points\","
				. "  \"Offers.Listings.MerchantInfo\","
				. "  \"Offers.Listings.Price\","
				. "  \"Offers.Listings.ProgramEligibility.IsPrimeExclusive\","
				. "  \"Offers.Listings.ProgramEligibility.IsPrimePantry\","
				. "  \"Offers.Listings.Promotions\","
				. "  \"Offers.Listings.SavingBasis\","
				. "  \"Offers.Summaries.HighestPrice\","
				. "  \"Offers.Summaries.LowestPrice\","
				. "  \"Offers.Summaries.OfferCount\","
				. "  \"ParentASIN\","
				. "  \"RentalOffers.Listings.Availability.MaxOrderQuantity\","
				. "  \"RentalOffers.Listings.Availability.Message\","
				. "  \"RentalOffers.Listings.Availability.MinOrderQuantity\","
				. "  \"RentalOffers.Listings.Availability.Type\","
				. "  \"RentalOffers.Listings.BasePrice\","
				. "  \"RentalOffers.Listings.Condition\","
				. "  \"RentalOffers.Listings.Condition.ConditionNote\","
				. "  \"RentalOffers.Listings.Condition.SubCondition\","
				. "  \"RentalOffers.Listings.DeliveryInfo.IsAmazonFulfilled\","
				. "  \"RentalOffers.Listings.DeliveryInfo.IsFreeShippingEligible\","
				. "  \"RentalOffers.Listings.DeliveryInfo.IsPrimeEligible\","
				. "  \"RentalOffers.Listings.DeliveryInfo.ShippingCharges\","
				. "  \"RentalOffers.Listings.MerchantInfo\""
				. " ],"
				. " \"PartnerTag\": \"" . $amzKey->associate_id . "\","
				. " \"PartnerType\": \"Associates\","
				. " \"Marketplace\": \"www.amazon." . $amzKey->country . "\""
				. "}";
			$path1 = 'getitems';
			$path2 = 'GetItems';
			$response = getawsdata($amzKey->aws_access_id, $amzKey->aws_secret_key, $amzKey->country, $payload, $path1, $path2);

			$result = json_decode($response, true);
			if (!isset($result['ItemsResult']['Items'])) {
				Log::info("Item tag not found.");
				return response()->json(['error' => ["msg" => ['There was some error processing the request. Please contact customer support.']]], 406);
			}
			$items = $result['ItemsResult']['Items'];
			if (isset($items[0]['ASIN'])) {
				foreach ($items as $item) {
					$this->insertItemWithoutVariants($item);
				}
				return response()->json(['success'], 200);
			} else {
				Log::info("ASIN not found.");
				return response()->json(['error' => ["msg" => ['There was some error processing the request. Please contact customer support.']]], 406);
			}
		} catch (\Exception $e) {
			Log::info("Exception: " . $e->getMessage());
			return response()->json(['error' => ["msg" => ['There were some error processing this request. Please try again.']]], 406);
		}
	}

	private function insertItemWithoutVariants($item)
	{
		$currUser = Auth::User();
		$title = "";
		$description = "";
		$feature1 = "";
		$feature2 = "";
		$feature3 = "";
		$feature4 = "";
		$feature5 = "";
		$brand = "";
		$product_type = "";
		$parentasin = "";
		$asin = "";
		$upc = "";
		$weight = 0;
		$currency = "";
		$detailPageURL = "";

		// For products table
		if (isset($item['ItemInfo'])) {
			$itemAttributes	= $item['ItemInfo'];
			if (isset($itemAttributes['Title'])) {
				$title = trim($itemAttributes['Title']['DisplayValue']);
			}

			if (isset($itemAttributes['ByLineInfo']['Brand'])) {
				$brand = $itemAttributes['ByLineInfo']['Brand']['DisplayValue'];
			}

			if (isset($itemAttributes['Classifications'])) {
				$product_type = $itemAttributes['Classifications']['ProductGroup']['DisplayValue'];
			}

			if (isset($itemAttributes['ExternalIds'])) {
				if (isset($itemAttributes['ExternalIds']['UPCs'])) {
					$upc = $itemAttributes['ExternalIds']['UPCs']['DisplayValues'][0];
				}
			}
		}

		if (isset($item['ItemInfo']['Features']['DisplayValues'][0])) {
			$feature1 = $item['ItemInfo']['Features']['DisplayValues'][0];
		}
		if (isset($item['ItemInfo']['Features']['DisplayValues'][1])) {
			$feature2 = $item['ItemInfo']['Features']['DisplayValues'][1];
		}
		if (isset($item['ItemInfo']['Features']['DisplayValues'][2])) {
			$feature3 = $item['ItemInfo']['Features']['DisplayValues'][2];
		}
		if (isset($item['ItemInfo']['Features']['DisplayValues'][3])) {
			$feature4 = $item['ItemInfo']['Features']['DisplayValues'][3];
		}
		if (isset($item['ItemInfo']['Features']['DisplayValues'][4])) {
			$feature5 = $item['ItemInfo']['Features']['DisplayValues'][4];
		}

		if (isset($item['ItemInfo']['Features'])) {
			$description = $feature1 . '</br>' . $feature2 . '</br>' . $feature3 . '</br>' . $feature4 . '</br>' . $feature5;
		}

		if (isset($item['ParentASIN'])) {
			$parentasin = $item['ParentASIN'];
		}

		// For product_variants
		if (isset($item['ASIN'])) {
			$asin = $item['ASIN'];
		}

		if (isset($item['DetailPageURL'])) {
			$detailPageURL = $item['DetailPageURL'];
		}

		// Fetch offer details
		$offerlistingId = "";
		$price = "";
		$saleprice = "";
		$condition = "";

		if (isset($item['Offers'])) {
			$offers = $item['Offers'];
			if (isset($offers['Listings'])) {
				$offerlistingId = $offers['Listings'][0]['Id'];
			}
			if (isset($offers['Summaries'])) {
				$condition = $offers['Summaries'][0]['Condition'];
				$currency = $offers['Summaries'][0]['HighestPrice']['Currency'];
				$saleprice = $offers['Summaries'][0]['HighestPrice']['Amount'];
				$price = $offers['Summaries'][0]['LowestPrice']['Amount'];
			}
		}

		// fetch Images
		$images = array();
		if (isset($item['Images'])) {
			$imageSets = $item['Images'];
			$imageUrl = $this->getImageURL($imageSets['Primary']);
			$images[] = $imageUrl;
			if (isset($imageSets['Variants'])) {
				$imageSets = $imageSets['Variants'];
			}
			if (is_array($imageSets) && isset($imageSets[0]['Large'])) {
				foreach ($imageSets as $imageSet) {
					$imageUrl = $this->getImageURL($imageSet);
					$images[] = $imageUrl;
				}
			}
		}

		// TODO: Add validation using laravel validator: Rohit
		$productObject = new Product(array("title" => $title, "description" => $description, "feature1" => $feature1, "feature2" => $feature2, "feature3" => $feature3, "feature4" => $feature4, "feature5" => $feature5, "brand" => $brand, "product_type" => $product_type, "raw_data" => json_encode($item)));
		$currUser->products()->save($productObject);

		$variantObject = new ProductVariant(array("sku" => $asin, "asin" => $asin, "barcode" => $upc, "price" => $price, "saleprice" => $saleprice, "currency" => $currency, "condition" => $condition, "amazonofferlistingid" => $offerlistingId, "weight" => $weight, "weight_unit" => "lb", "detail_page_url" => $detailPageURL, "user_id" => $currUser->id));
		$productObject->variants()->save($variantObject);

		foreach ($images as $imageUrl) {
			$productImageObject = new ProductImage(array("asin" => $asin, "imgurl" => $imageUrl, "user_id" => $currUser->id));
			$variantObject->images()->save($productImageObject);
		}

		$this->insertToShopify($productObject);
		return true;
	}

	private function getImageURL($imageSetObj)
	{
		$imageUrl = "";
		if (isset($imageSetObj['Large']['URL'])) {
			$imageUrl = $imageSetObj['Large']['URL'];
		} else if (isset($imageSetObj['Medium']['URL'])) {
			$imageUrl = $imageSetObj['Medium']['URL'];
		} else if (isset($imageSetObj['Small']['URL'])) {
			$imageUrl = $imageSetObj['Small']['URL'];
		}
		return $imageUrl;
	}

	private function insertItemWithVariants($item)
	{
		$currUser = Auth::User();
		$title = "";
		$description = "";
		$feature1 = "";
		$feature2 = "";
		$feature3 = "";
		$feature4 = "";
		$feature5 = "";
		$brand = "";
		$product_type = "";
		$parentasin = "";
		$asin = "";
		$upc = "";
		$weight = 0;
		$currency = "";
		$detailPageURL = "";
		$option1name = "";
		$option2name = "";
		$option3name = "";
		$option1val = "";
		$option2val = "";
		$option3val = "";

		// For products table
		if (isset($item['ItemInfo'])) {
			$itemAttributes	= $item['ItemInfo'];
			if (isset($itemAttributes['Title'])) {
				$title = trim($itemAttributes['Title']['DisplayValue']);
			}

			if (isset($itemAttributes['ByLineInfo']['Brand'])) {
				$brand = $itemAttributes['ByLineInfo']['Brand']['DisplayValue'];
			}

			if (isset($itemAttributes['Classifications'])) {
				$product_type = $itemAttributes['Classifications']['ProductGroup']['DisplayValue'];
			}

			if (isset($itemAttributes['ExternalIds'])) {
				if (isset($itemAttributes['ExternalIds']['UPCs'])) {
					$upc = $itemAttributes['ExternalIds']['UPCs']['DisplayValues'][0];
				}
			}
		}

		if (isset($item['ItemInfo']['Features']['DisplayValues'][0])) {
			$feature1 = $item['ItemInfo']['Features']['DisplayValues'][0];
		}
		if (isset($item['ItemInfo']['Features']['DisplayValues'][1])) {
			$feature2 = $item['ItemInfo']['Features']['DisplayValues'][1];
		}
		if (isset($item['ItemInfo']['Features']['DisplayValues'][2])) {
			$feature3 = $item['ItemInfo']['Features']['DisplayValues'][2];
		}
		if (isset($item['ItemInfo']['Features']['DisplayValues'][3])) {
			$feature4 = $item['ItemInfo']['Features']['DisplayValues'][3];
		}
		if (isset($item['ItemInfo']['Features']['DisplayValues'][4])) {
			$feature5 = $item['ItemInfo']['Features']['DisplayValues'][4];
		}

		if (isset($item['ItemInfo']['Features'])) {
			$description = $feature1 . '</br>' . $feature2 . '</br>' . $feature3 . '</br>' . $feature4 . '</br>' . $feature5;
		}

		if (isset($item['ParentASIN'])) {
			$parentasin = $item['ParentASIN'];
		}

		if (isset($items[0]['VariationAttributes'])) {
			if (isset($items[0]['VariationAttributes'][0])) {
				$option1name = $items[0]['VariationAttributes'][0]['Name'];
			}
			if (isset($items[0]['VariationAttributes'][1])) {
				$option2name = $items[0]['VariationAttributes'][1]['Name'];
			}
			if (isset($items[0]['VariationAttributes'][2])) {
				$option3name = $items[0]['VariationAttributes'][2]['Name'];
			}
		}

		if (isset($items[0]['VariationAttributes'])) {
			if (isset($items[0]['VariationAttributes'][0])) {
				$option1val = $items[0]['VariationAttributes'][0]['Value'];
			}
			if (isset($items[0]['VariationAttributes'][1])) {
				$option1val = $items[0]['VariationAttributes'][1]['Value'];
			}
			if (isset($items[0]['VariationAttributes'][2])) {
				$option1val = $items[0]['VariationAttributes'][2]['Value'];
			}
		}
		// For product_variants
		if (isset($item['ASIN'])) {
			$asin = $item['ASIN'];
		}

		if (isset($item['DetailPageURL'])) {
			$detailPageURL = $item['DetailPageURL'];
		}

		// Fetch offer details
		$offerlistingId = "";
		$price = "";
		$saleprice = "";
		$condition = "";

		if (isset($item['Offers'])) {
			$offers = $item['Offers'];
			if (isset($offers['Listings'])) {
				$offerlistingId = $offers['Listings'][0]['Id'];
			}
			if (isset($offers['Summaries'])) {
				$condition = $offers['Summaries'][0]['Condition'];
				$currency = $offers['Summaries'][0]['HighestPrice']['Currency'];
				$saleprice = $offers['Summaries'][0]['HighestPrice']['Amount'];
				$price = $offers['Summaries'][0]['LowestPrice']['Amount'];
			}
		}

		// fetch Images
		$images = array();
		if (isset($item['Images'])) {
			$imageSets = $item['Images'];
			$imageUrl = $this->getImageURL($imageSets['Primary']);
			$images[] = $imageUrl;
			if (isset($imageSets['Variants'])) {
				$imageSets = $imageSets['Variants'];
			}
			if (is_array($imageSets) && isset($imageSets[0]['Large'])) {
				foreach ($imageSets as $imageSet) {
					$imageUrl = $this->getImageURL($imageSet);
					$images[] = $imageUrl;
				}
			}
		}

		// TODO: Add validation using laravel validator: Rohit
		$productObject = new Product(array("title" => $title, "description" => $description, "feature1" => $feature1, "feature2" => $feature2, "feature3" => $feature3, "feature4" => $feature4, "feature5" => $feature5, "option1name" => $option1name, "option2name" => $option2name, "option3name" => $option3name, "brand" => $brand, "product_type" => $product_type, "raw_data" => json_encode($item)));
		$currUser->products()->save($productObject);

		$variantObject = new ProductVariant(array("sku" => $asin, "asin" => $asin, "barcode" => $upc, "option1val" => $option1val, "option2val" => $option2val, "option3val" => $option3val, "price" => $price, "saleprice" => $saleprice, "currency" => $currency, "condition" => $condition, "amazonofferlistingid" => $offerlistingId, "weight" => $weight, "weight_unit" => "lb", "detail_page_url" => $detailPageURL, "user_id" => $currUser->id));
		$productObject->variants()->save($variantObject);

		foreach ($images as $imageUrl) {
			$productImageObject = new ProductImage(array("asin" => $asin, "imgurl" => $imageUrl, "user_id" => $currUser->id));
			$variantObject->images()->save($productImageObject);
		}

		$this->insertToShopify($productObject);
		return true;
	}

	public function createmany($parentasin)
	{
		$temp = explode(",", $parentasin);
		$count = count($temp);
		if ($count == 0) {
			return response()->json(['error' => ["msg" => ["Please choose atleast one product."]]], 406);
		} else if ($count > 10) {
			return response()->json(['error' => ["msg" => ["Please choose less than 10 products."]]], 406);
		}

		$currUser = Auth::User();
		$amzKey = $currUser->amzKey()->first();
		if (!$amzKey) {
			return response()->json(['error' => ["msg" => ['Amazon AWS keys are required for this operation.']]], 406);
		}
		foreach ($temp as $t) {
			$pcount = $currUser->products()->where('parentasin', $t)->count();
			if ($pcount != 0) {
				return response()->json(['error' => ["msg" => ['Product already exist.']]], 406);
				break;
			}
		}

		try {
			$payload = "{"
				. " \"ASIN\": \"" . $temp[0] . "\","
				. " \"Resources\": ["
				. "  \"BrowseNodeInfo.BrowseNodes\","
				. "  \"BrowseNodeInfo.BrowseNodes.Ancestor\","
				. "  \"BrowseNodeInfo.BrowseNodes.SalesRank\","
				. "  \"BrowseNodeInfo.WebsiteSalesRank\","
				. "  \"CustomerReviews.Count\","
				. "  \"CustomerReviews.StarRating\","
				. "  \"Images.Primary.Small\","
				. "  \"Images.Primary.Medium\","
				. "  \"Images.Primary.Large\","
				. "  \"Images.Variants.Small\","
				. "  \"Images.Variants.Medium\","
				. "  \"Images.Variants.Large\","
				. "  \"ItemInfo.ByLineInfo\","
				. "  \"ItemInfo.ContentInfo\","
				. "  \"ItemInfo.ContentRating\","
				. "  \"ItemInfo.Classifications\","
				. "  \"ItemInfo.ExternalIds\","
				. "  \"ItemInfo.Features\","
				. "  \"ItemInfo.ManufactureInfo\","
				. "  \"ItemInfo.ProductInfo\","
				. "  \"ItemInfo.TechnicalInfo\","
				. "  \"ItemInfo.Title\","
				. "  \"ItemInfo.TradeInInfo\","
				. "  \"Offers.Listings.Availability.MaxOrderQuantity\","
				. "  \"Offers.Listings.Availability.Message\","
				. "  \"Offers.Listings.Availability.MinOrderQuantity\","
				. "  \"Offers.Listings.Availability.Type\","
				. "  \"Offers.Listings.Condition\","
				. "  \"Offers.Listings.Condition.ConditionNote\","
				. "  \"Offers.Listings.Condition.SubCondition\","
				. "  \"Offers.Listings.DeliveryInfo.IsAmazonFulfilled\","
				. "  \"Offers.Listings.DeliveryInfo.IsFreeShippingEligible\","
				. "  \"Offers.Listings.DeliveryInfo.IsPrimeEligible\","
				. "  \"Offers.Listings.DeliveryInfo.ShippingCharges\","
				. "  \"Offers.Listings.IsBuyBoxWinner\","
				. "  \"Offers.Listings.LoyaltyPoints.Points\","
				. "  \"Offers.Listings.MerchantInfo\","
				. "  \"Offers.Listings.Price\","
				. "  \"Offers.Listings.ProgramEligibility.IsPrimeExclusive\","
				. "  \"Offers.Listings.ProgramEligibility.IsPrimePantry\","
				. "  \"Offers.Listings.Promotions\","
				. "  \"Offers.Listings.SavingBasis\","
				. "  \"Offers.Summaries.HighestPrice\","
				. "  \"Offers.Summaries.LowestPrice\","
				. "  \"Offers.Summaries.OfferCount\","
				. "  \"ParentASIN\","
				. "  \"RentalOffers.Listings.Availability.MaxOrderQuantity\","
				. "  \"RentalOffers.Listings.Availability.Message\","
				. "  \"RentalOffers.Listings.Availability.MinOrderQuantity\","
				. "  \"RentalOffers.Listings.Availability.Type\","
				. "  \"RentalOffers.Listings.BasePrice\","
				. "  \"RentalOffers.Listings.Condition\","
				. "  \"RentalOffers.Listings.Condition.ConditionNote\","
				. "  \"RentalOffers.Listings.Condition.SubCondition\","
				. "  \"RentalOffers.Listings.DeliveryInfo.IsAmazonFulfilled\","
				. "  \"RentalOffers.Listings.DeliveryInfo.IsFreeShippingEligible\","
				. "  \"RentalOffers.Listings.DeliveryInfo.IsPrimeEligible\","
				. "  \"RentalOffers.Listings.DeliveryInfo.ShippingCharges\","
				. "  \"RentalOffers.Listings.MerchantInfo\","
				. "  \"VariationSummary.Price.HighestPrice\","
				. "  \"VariationSummary.Price.LowestPrice\","
				. "  \"VariationSummary.VariationDimension\""
				. " ],"
				. " \"PartnerTag\": \"" . $amzKey->associate_id . "\","
				. " \"PartnerType\": \"Associates\","
				. " \"Marketplace\": \"www.amazon." . $amzKey->country . "\""
				. "}";
			$path1 = 'getvariations';
			$path2 = 'GetVariations';
			$response = getawsdata($amzKey->aws_access_id, $amzKey->aws_secret_key, $amzKey->country, $payload, $path1, $path2);

			$result = json_decode($response, true);
			Log::info($response);

			if (!isset($result['VariationsResult']['Items'])) {
				return response()->json(['error' => ["msg" => ['There was some error processing the request. Please contact customer support.']]], 406);
			}

			$items = $result['VariationsResult']['Items'];
			if (isset($items[0]['ASIN'])) {
				$errmsg = '';
				foreach ($items as $item) {
					$res = $this->insertItemWithVariants($item);
				}
				return response()->json(['success'], 200);
			} else {
				return response()->json(['error' => ["msg" => ['There was some error processing the request. Please contact customer support.']]], 406);
			}
		} catch (\Exception $e) {
			return response()->json(['error' => ["msg" => ['There were some error processing this request. Please try again.']]], 406);
		}
	}

	public function destroy($id)
	{
		$temp = explode(",", $id);
		if (count($temp) < 1) {
			return response()->json(['error' => ["msg" => ['Please choose a product to delete.']]], 406);
		}
		$currUser = Auth::User();
		$token = $currUser->token;
		$shopurl = $currUser->shopurl;
		$user_id = $currUser->id;
		foreach ($temp as $t) {
			$product = $currUser->products()->where('product_id', $t)->first();
			$shopifyproductid = $product->shopifyproductid;
			if (strlen($shopifyproductid) > 0) {
				deleteShopifyProduct($user_id, $token, $shopurl, $shopifyproductid);
				$currUser->skuconsumed -= 1;
				$currUser->save();
			}
			$variants = $product->variants()->get();
			foreach ($variants as $variant) {
				$variant->images()->delete();
				$variant->reviews()->delete();
			}
			$product->variants()->delete();
			$product->delete();
			return response()->json(['success'], 200);
		}
	}

	private function getSettingObject()
	{
		$currUser = Auth::User();
		$settings = $currUser->settings()->first();
		if (!$settings) {
			$settingObj = new Setting;
			$settingObj->published = 1;
			$settingObj->tags = "";
			$settingObj->inventory_sync = 0;
			$settingObj->price_sync = 0;
			$currUser->settings()->save($settingObj);
			return $settingObj;
		} else {
			return $currUser->settings()->first();
		}
	}

	private function applyPriceMarkup($price, $markuptype, $markupval, $markupround)
	{
		$newprice = $price;
		$currUser = Auth::User();
		if ($markuptype == "FIXED") {
			$newprice = $price + $markupval;
		} else {
			$newprice = $price + $price * $markupval / 100;
		}
		if ($currUser->id == 9578) {
			$newprice = $newprice * 3420;
		}
		if ($markupround) {
			$newprice = round($price) - 0.01;
		}
		return $newprice;
	}

	public function getVariants(Request $request){
		$currUser = Auth::User();
		if($request->has("product_id")){
			$product_id = $request->input("product_id");
			$variants = $currUser->variants()->where('product_id', $product_id)->get();
			return $variants;
		} else {
			return array();
		}		
	}


	private function insertToShopify(Product $productObject)
	{
		$currUser = Auth::User();
		$shopcurrency = $currUser->shopcurrency;
		$autoCurrencyConversion = $currUser->autoCurrencyConversion;

		$settingObject = $this->getSettingObject();
		$published = false;
		$tags = array();
		$vendor = "";
		$product_type = "";
		$inventory_policy = null;
		$defquantity = 1;
		$markupenabled = 0;
		$markuptype = 'FIXED';
		$markupval = 0;
		$markupvalfixed = 0;
		$markupround = 0;
		$location_id = "";
		if ($settingObject) {
			$tags = $settingObject->tags;
			if (strlen($tags) > 0) {
				$tags = explode(",", $tags);
			} else {
				$tags = array();
			}
			if (isset($settingObject->published) && $settingObject->published == 1) {
				$published = true;
			}
			if (isset($settingObject->vendor) && strlen($settingObject->vendor) > 0) {
				$vendor = $settingObject->vendor;
			}
			if (isset($settingObject->product_type) && strlen($settingObject->product_type) > 0) {
				$product_type = $settingObject->product_type;
			}
			if (isset($settingObject->inventory_policy) && $settingObject->inventory_policy != "NO") {
				$inventory_policy = $settingObject->inventory_policy;
			}
			if (isset($settingObject->defquantity)) {
				$defquantity = $settingObject->defquantity;
			}
			if (isset($settingObject->markupenabled) && $settingObject->markupenabled == 1) {
				$markupenabled = true;
			}
			if (isset($settingObject->markuptype) && strlen($settingObject->markuptype) > 0) {
				$markuptype = $settingObject->markuptype;
			}
			if (isset($settingObject->markupval)) {
				$markupval = $settingObject->markupval;
			}
			if (isset($settingObject->markupvalfixed)) {
				$markupvalfixed = $settingObject->markupvalfixed;
			}
			if (isset($settingObject->markupround) && $settingObject->markupround == 1) {
				$markupround = true;
			}
			if (isset($settingObject->shopifylocationid)) {
				$location_id = $settingObject->shopifylocationid;
			}
		}
		$shopurl = $currUser->shopurl;
		$token = $currUser->token;
		$title = $productObject->title;
		$description = $productObject->description;
		$brand = $productObject->brand;
		if ($vendor != '') {
			$brand = $vendor;
		}
		$productType = $productObject->product_type;
		if ($product_type != '') {
			$productType = $product_type;
		}
		$feature1 = $productObject->feature1;
		$feature2 = $productObject->feature2;
		$feature3 = $productObject->feature3;
		$feature4 = $productObject->feature4;
		$feature5 = $productObject->feature5;
		$featureStr = "";
		if (strlen($feature1) > 0) {
			$featureStr .= '<li>' . $feature1 . '</li>';
		}
		if (strlen($feature2) > 0) {
			$featureStr .= '<li>' . $feature2 . '</li>';
		}
		if (strlen($feature3) > 0) {
			$featureStr .= '<li>' . $feature3 . '</li>';
		}
		if (strlen($feature4) > 0) {
			$featureStr .= '<li>' . $feature4 . '</li>';
		}
		if (strlen($feature5) > 0) {
			$featureStr .= '<li>' . $feature5 . '</li>';
		}
		if (strlen($featureStr) > 0) {
			$featureStr = '<br /><ul>' . $featureStr . '</ul>';
		}

		$description = $description . $featureStr;
		$variantsArr = $productObject->variants();

		$vCount = $variantsArr->count();
		if ($vCount == 1) {
			$variantObject = $variantsArr->first();
			$sku = $variantObject->sku;
			$weight = $variantObject->weight;
			$weight_unit = $variantObject->weight_unit;
			$productid = $variantObject->productid;
			$currency = $variantObject->currency;
			$price = $variantObject->price;
			$saleprice = $variantObject->price;
			if ($markupenabled == 1) {
				if ($currUser->id == 5718 || $currUser->id == 1823) {
					$price = $this->applyPriceMarkup($price, $markuptype, $markupval, $markupround);
					$saleprice = $this->applyPriceMarkup($saleprice, $markuptype, $markupval, $markupround);
					$price = $price + $markupvalfixed;
					$saleprice = $saleprice + $markupvalfixed;
				} else {
					$price = $this->applyPriceMarkup($price, $markuptype, $markupval, $markupround);
					$saleprice = $this->applyPriceMarkup($saleprice, $markuptype, $markupval, $markupround);
				}
			}
			if ($currency != '' && $autoCurrencyConversion == 1) {
				$fromStr = Currencies::where("currency_code", $currency)->get();
				$toStr = Currencies::where("currency_code", $shopcurrency)->get();
				if (sizeof($fromStr) > 0 && sizeof($toStr) > 0) {
					$from = floatval($fromStr[0]['conversionrates']);
					$to   = floatval($toStr[0]['conversionrates']);
					$amount = floatval($saleprice);
					$conversion_rate  = $from / $to;
					$saleprice = round($amount / $conversion_rate, 2);
				}
				$currency = $shopcurrency;
			}
			$detail_page_url = $variantObject->detail_page_url;
			$imagesArr = $variantObject->images()->get();
			$images = array();
			$position = 1;
			foreach ($imagesArr as $imageObject) {
				$imgUrl = $imageObject->imgurl;
				if (!($strpos = stripos($imgUrl, "no-image"))) {
					$images[] = array("src" => trim($imgUrl), "position" => $position++);
				}
			}
			//Log::info($imagesArr); 
			$productMetafields = array(array("key" => "isavailable", "value" => 1, "type" => "number_integer", "namespace" => "isaac"));
			$variantMetafields = array(array("key" => "buynowurl", "value" => $detail_page_url, "type" => "single_line_text_field", "namespace" => "isaac"));
			$data = array(
				"product" => array(
					"title" => $title,
					"body_html" => $description,
					"vendor" => $brand,
					"product_type" => $productType,
					"published" => $published,
					"tags" => $tags,
					"published_scope" => "global",
					"images" => $images,
					"metafields" => $productMetafields,
					"variants" => array(
						array(
							"sku" => $sku,
							"position" => 1,
							"price" => number_format($saleprice, 2, '.', ''),
							"inventory_policy" => "deny",
							"fulfillment_service" => "manual",
							"inventory_management" => $inventory_policy,
							"taxable" => true,
							"weight" => $weight,
							"weight_unit" => $weight_unit,
							"barcode" => $productid,
							"requires_shipping" => true,
							"metafields" => $variantMetafields
						)
					)
				)
			);
			if ($currUser->id == 5246) {
				$data["product"]["variants"][0]["taxable"] = false;
			}
			if ($currUser->id == 279 || $currUser->id == 374) {
				$data["product"]["template_suffix"] = "amazon";
			}
			if ($currUser->id == 22495) {
				$data["product"]["template_suffix"] = "aac";
			}
			$response = addShopifyProduct($token, $shopurl, $data);
			if ($response) {
				$shopifyproductid = $response["id"];
				$shopifyvariantid = $response["variants"][0]["id"];
				$shopifyinventoryid = $response["variants"][0]["inventory_item_id"];
				$variantObject->shopifyvariantid = $shopifyvariantid;
				$variantObject->shopifyproductid = $shopifyproductid;
				$variantObject->shopifyinventoryid = $shopifyinventoryid;
				$variantObject->save();
				$productObject->shopifyproductid = $shopifyproductid;
				$productObject->save();
				if ($inventory_policy == "shopify" && $location_id == "") {
					$location_id = getLocationId($token, $shopurl, $shopifyinventoryid);
					if ($location_id) {
						$settingObject->shopifylocationid = $location_id;
						$settingObject->save();
					}
				}
				if ($inventory_policy == "shopify" && $location_id != "") {
					updateShopifyInventory1($token, $shopurl, $shopifyinventoryid, $location_id, $defquantity);
				}
			} else {
				$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $detail_page_url, "reason" => 'There was some error importing the product, please verify the product URL again. Contact support if the issue still persist.', "type" => 'Shopify'));
				$failed_productimports->save();
			}
		} else if ($vCount > 1) {
			$option1name = $productObject->option1name;
			$option2name = $productObject->option2name;
			$option3name = $productObject->option3name;
			$options = array();
			if ($option1name != '') {
				$options[] = array("name" => $option1name, "position" => 1);
			}
			if ($option2name != '') {
				$options[] = array("name" => $option2name, "position" => 2);
			}
			if ($option3name != '') {
				$options[] = array("name" => $option3name, "position" => 3);
			}
			$images = array();
			$imgSecondaryArr = array();
			$shopifyVariants = array();
			$position = 1;
			foreach ($variantsArr->get() as $variantObject) {
				$sku = $variantObject->sku;
				$weight = $variantObject->weight;
				$weight_unit = $variantObject->weight_unit;
				$productid = $variantObject->productid;
				$price = $variantObject->price;
				$saleprice = $variantObject->saleprice;
				if ($markupenabled == 1) {
					if ($currUser->id == 5718 || $currUser->id == 1823) {
						$price = $this->applyPriceMarkup($price, $markuptype, $markupval, $markupround);
						$saleprice = $this->applyPriceMarkup($saleprice, $markuptype, $markupval, $markupround);
						$price = $price + $markupvalfixed;
						$saleprice = $saleprice + $markupvalfixed;
					} else {
						$price = $this->applyPriceMarkup($price, $markuptype, $markupval, $markupround);
						$saleprice = $this->applyPriceMarkup($saleprice, $markuptype, $markupval, $markupround);
					}
				}
				$option1val = $variantObject->option1val;
				$option2val = $variantObject->option2val;
				$option3val = $variantObject->option3val;
				$detail_page_url = $variantObject->detail_page_url;
				$imagesArr = $variantObject->images()->get();
				$isFirstImage = true;
				foreach ($imagesArr as $imageObject) {
					$imgUrl = $imageObject->imgurl;
					if (!($strpos = stripos($imgUrl, "no-image"))) {
						if ($isFirstImage) {
							$images[trim($imgUrl)][] = $sku;
						} else {
							$imgSecondaryArr[] = trim($imgUrl);
						}
						$isFirstImage  = false;
					}
				}
				$variantMetafields = array(array("key" => "buynowurl", "value" => $detail_page_url, "type" => "single_line_text_field", "namespace" => "isaac"));
				$shopifyVariant = array(
					"sku" => $sku,
					"position" => $position++,
					"price" => number_format($saleprice, 2, '.', ''),
					"inventory_policy" => "deny",
					"fulfillment_service" => "manual",
					"inventory_management" => $inventory_policy,
					"taxable" => true,
					"weight" => $weight,
					"weight_unit" => $weight_unit,
					"barcode" => $productid,
					"requires_shipping" => true,
					"metafields" => $variantMetafields
				);
				if ($currUser->id == 5246) {
					$shopifyVariant["taxable"] = false;
				}
				if ($option1val != '') {
					$shopifyVariant['option1'] = trim($option1val);
				}
				if ($option2val != '') {
					$shopifyVariant['option2'] = trim($option2val);
				}
				if ($option3val != '') {
					$shopifyVariant['option3'] = trim($option3val);
				}
				$shopifyVariants[] = $shopifyVariant;
			}
			$imgSecondaryArr = array_unique($imgSecondaryArr);
			$productMetafields = array(array("key" => "isavailable", "value" => 1, "type" => "number_integer", "namespace" => "isaac"));
			$data = array(
				"product" => array(
					"title" => $title,
					"body_html" => $description,
					"vendor" => $brand,
					"product_type" => $productType,
					"published" => $published,
					"tags" => $tags,
					"published_scope" => "global",
					"variants" => $shopifyVariants,
					"options" => $options,
					"metafields" => $productMetafields
				)
			);
			if ($currUser->id == 279 || $currUser->id == 374) {
				$data["product"]["template_suffix"] = "amazon";
			}
			if ($currUser->id == 22495) {
				$data["product"]["template_suffix"] = "aac";
			}
			$response = addShopifyProduct($token, $shopurl, $data);
			if ($response) {
				$shopifyproductid = $response["id"];
				$rvariants = $response["variants"];
				$varr = array();
				foreach ($rvariants as $rvariant) {
					$shopifyvariantid = $rvariant['id'];
					$shopifyinventoryid = $rvariant['inventory_item_id'];
					$rsku = $rvariant["sku"];
					$varr[$rsku] = $shopifyvariantid;
					$productObject->variants()->where("sku", '=', $rsku)->update(['shopifyvariantid' => $shopifyvariantid, 'shopifyinventoryid' => $shopifyinventoryid, 'shopifyproductid' => $shopifyproductid]);
					if ($inventory_policy == "shopify" && $location_id != "") {
						updateShopifyInventory1($token, $shopurl, $shopifyinventoryid, $location_id, $defquantity);
					}
				}
				$productObject->shopifyproductid = $shopifyproductid;
				$productObject->save();
				$imgdata = array();
				$position = 1;
				$processedImgVariants = array();
				foreach ($images as $k => $v) {
					$variantids = array();
					foreach ($v as $vobj) {
						if (!in_array($varr[$vobj], $processedImgVariants)) {
							$variantids[] = $varr[$vobj];
							$processedImgVariants[]  = $varr[$vobj];
						}
					}
					$imgdata[] = array("src" => trim($k), "position" => $position++, "variant_ids" => $variantids);
				}
				foreach ($imgSecondaryArr as $v) {
					$imgdata[] = array("src" => trim($v), "position" => $position++);
				}
				$data = array("product" => array("id" => $shopifyproductid, "images" => $imgdata));
				updateShopifyProduct($token, $shopurl, $shopifyproductid, $data);
			}
		} else {
			$failed_productimports = new Failed_productimports(array("user_id" => Auth::user()->id, "url" => $detail_page_url, "reason" => 'There was some error importing the product, please verify the product URL again. Contact support if the issue still persist.', "type" => 'Shopify'));
			$failed_productimports->save();
		}
		//$skuconsumed = $currUser->skuconsumed;
		//$currUser->skuconsumed = $skuconsumed + 1;
		//$currUser->save();
	}

	public function block($id)
	{
		$temp = explode(",", $id);
		$count = count($temp);
		if ($count == 0) {
			return response()->json(['error' => ["msg" => ["Please choose atleast one product."]]], 406);
		}
		$currUser = Auth::User();
		$currUser->products()->whereIn('product_id', $temp)->update(['block' => 1]);
		$currUser->variants()->whereIn('product_id', $temp)->update(['block' => 1]);
		return response()->json(['success'], 200);
	}

	public function unblock($id)
	{
		$temp = explode(",", $id);
		$count = count($temp);
		if ($count == 0) {
			return response()->json(['error' => ["msg" => ["Please choose atleast one product."]]], 406);
		}
		$currUser = Auth::User();
		$currUser->products()->whereIn('product_id', $temp)->update(['block' => 0]);
		$currUser->variants()->whereIn('product_id', $temp)->update(['block' => 0]);
		return response()->json(['success'], 200);
	}

	public function changeLink(Request $request)
	{
		$currUser = Auth::User();
		$validator = Validator::make($request->all(), [
			'product_id' => 'required'
		]);
		if ($validator->fails()) {
			return response()->json(['error' => $validator->errors()], 406);
		} else {
			$product_id = $request->input('product_id');
			$variants = $request->input('variants');
			$custom_link = $variants[0]['custom_link'];
			$shopifyvariantid = $variants[0]['shopifyvariantid'];
			$shopifyproductid = $variants[0]['shopifyproductid'];
			$shopurl = $currUser->shopurl;
			$token = $currUser->token;
			$currUser->variants()->where('product_id', $product_id)->update(['custom_link' => $custom_link]);

			$existingMetafieldsArr = fetchMetafields($shopurl, $token, $shopifyproductid, $shopifyvariantid);
			$conditionupdated = false;
			if (is_array($existingMetafieldsArr) && count($existingMetafieldsArr) > 0) {
				foreach ($existingMetafieldsArr as $existingMetafield) {
					if ($existingMetafield['namespace'] == "isaac" && $existingMetafield['key'] == "buynowurl") {
						$conditionupdated = true;
						$metafield_id = $existingMetafield['id'];
						$data = array(
							"metafield" => array(
								"id" => $metafield_id,
								"value" => $custom_link,
								"type" => "single_line_text_field"
							)
						);
						updateMetafield($token, $shopurl, $metafield_id, $data);
					}
				}
			}
			if (!$conditionupdated) {
				$data = array("metafield" => array("key" => "buynowurl", "value" => $custom_link, "type" => "single_line_text_field", "namespace" => "isaac"));
				createMetafield($token, $shopurl, $shopifyvariantid, $data);
			}
			return response()->json(['success'], 200);
		}
	}

	public function forceSync($id)
	{
		$temp = explode(",", $id);
		$count = count($temp);
		Log::info($temp);
		Log::info($id);

		if ($count == 0) {
			return response()->json(['error' => ["msg" => ["Please choose atleast one product."]]], 406);
		}

		$currUser = Auth::User();
		$user_id = $currUser->id;

		$product = ProductVariant::where('product_id', $temp[0])->where('user_id', $currUser->id)->get();
		$settingObject = $this->getSettingObject();
		$defquantity = $settingObject->defquantity;
		//print_r($product);						
		if (sizeof($product) > 0) {

			Log::info($product);
			$asin = $product[0]->asin;
			$producturl = $product[0]->detail_page_url;
			if ($producturl == "") {
				return response()->json(['error' => ["msg" => ["There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist."]]], 406);
			}
			Log::info($producturl);


			$tmpurl = strtok($producturl, '?');
			Log::info($tmpurl);

			$domainVerification = verifyAmazonDomain($producturl);
			if (!$domainVerification) {
				Log::info("Domain Verification error");
				return response()->json(['error' => ["msg" => ["Please enter a valid product URL."]]], 406);
			}
			Log::info("starting surrent user");
			$domain = parse_url($producturl, PHP_URL_HOST);
			$time_start = $this->microtime_float();
			Log::info($producturl);
			$data = null;
			if ($data == null && $user_id != 17012 && $user_id != 20892) {
			    $data = proxycrawlapi($producturl);
    		}
    		if ($data == null) {
    			$data = get_html_scraper_api_content($producturl, $user_id);
    		}
			
			if ($data == null) {
				return response()->json(['error' => ["msg" => ["There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist."]]], 406);
			} else {
				//print_r($data);
				$res1 = $data['message'];
				$resObj = json_decode($res1, true);
				Log::info($resObj);
				if (isset($resObj['Title'])) {
					$results = $resObj;
					$price = "";
					$list_price = "";
					$quantity = 0;
					//print_r($resObj);

					if (isset($results['price'])) {
						$price = $results['price'];
						$price = getAmount($price);
					}
					if (isset($results['list_price'])) {
						$list_price = $results['list_price'];
						$list_price = getAmount($list_price);
					} else {
						$list_price = $price;
					}

					// if(isset($results['in_stock___out_of_stock']) && $results['in_stock___out_of_stock'] == 'In stock.') {
					// 	$quantity = 1;
					// }
					if (isset($results['quantity'])) {
						$quantity = $results['quantity'];
					}
					$productVariantObj = $product[0];

					$priceflag = 0;
					$quantityflag = 0;
					if ($price > 0 && $price != $product[0]->saleprice) {
						$priceflag = 1;
					}
					if ($list_price > 0 && $list_price != $product[0]->price) {
						$priceflag = 1;
					}
					if ($quantity != $product[0]->quantity) {
						$quantityflag = 1;
					}
					$productVariantObj->quantity = $quantity;
					$productVariantObj->price = $list_price;
					$productVariantObj->saleprice = $price;
					$productVariantObj->priceflag = $priceflag;
					$productVariantObj->quantityflag = $quantityflag;
					$productVariantObj->save();
					// TODO: update on Shopify
					$shopurl = $currUser->shopurl;
					$token = $currUser->token;
					$product = ProductVariant::where('product_id', $id)->get()->toArray();
					//print_r($product);	
					//echo $shopurl.''.$token;				
					$location_id = $product['0']['shopifylocationid'];
					$shopifyinventoryid = $product['0']['shopifyinventoryid'];
					$shopifyvariantid = $product['0']['shopifyvariantid'];
					//Updating Quantity on shopify				
					//commenting inventory update 
					if ($list_price <= $price) {
						$list_price = "";
					}
					$quantitUpdateRes = updateShopifyInventory1($token, $shopurl, $shopifyinventoryid, $location_id, $defquantity);
					//Updating Price on shopify			
					//echo 'updating price';	
					if ($price > 0) {
						$data = array(
							"variant" => array(
								"id" => $shopifyvariantid,
								"price" => $price
							)
						);
						$url = "https://" . $shopurl . "/admin/api/2022-01/variants/" . $shopifyvariantid . ".json";
						$curl = curl_init();
						curl_setopt($curl, CURLOPT_URL, $url);
						curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:' . $token, 'Content-Type: application/json; charset=utf-8'));
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($curl, CURLOPT_VERBOSE, 0);
						curl_setopt($curl, CURLOPT_HEADER, 1);
						curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
						curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
						$response = curl_exec($curl);
						curl_close($curl);
						$response_arr = explode("\n", $response);
					}
					return response()->json(['success'], 200);
				} else {
					return response()->json(['error' => ["msg" => ["There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist."]]], 406);
				}
			}
		} else {
			return response()->json(['error' => ["msg" => ["There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist."]]], 406);
		}
	}


	public function reimport($id)
	{
		$temp = explode(",", $id);
		$count = count($temp);
		Log::info($id);

		if ($count == 0) {
			return response()->json(['error' => ["msg" => ["Please choose atleast one product."]]], 406);
		}
		$currUser = Auth::User();

		$product = ProductVariant::where('product_id', $temp)->where('user_id', $currUser->id)->get();
		if ($product) {
			$count = $currUser->products()->whereIn('product_id', $temp)->where('status', "==", "Imported")->count();
			if ($count > 0) {
				$product[0]->asin;
				$res = exec("php importSave.php " . $product[0]->asin . " " . $currUser->id . " " . $product[0]->product_id . " local");
				Log::info($res);
				return response()->json(['success', $res], 200);
			} else {
				$product[0]->asin;
				$res = exec("php importSave.php " . $product[0]->asin . " " . $currUser->id . " " . $product[0]->product_id . " shopify");
				Log::info($res);
				return response()->json(['success', $res], 200);
			}
		}

		/*$count = $currUser->products()->whereIn('product_id', $temp)->where('status',"==","Imported")->count();
		if($count > 0){
			return response()->json(['error' => ["msg"=>['The selected products could not be re-imported to shopify.']]], 406);
		} else {
			$permission = Product::find($temp);
			//Log::info($permission);
			$permission[0]->update(["status" => "re-import In Progress"]);
			return response()->json(['success'], 200);
		}*/
		return response()->json(['success'], 200);
	}
};
