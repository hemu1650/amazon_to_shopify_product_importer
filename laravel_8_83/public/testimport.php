<?php
 header('Access-Control-Allow-Origin: *');
	set_time_limit(0);
	//require_once("includes/config.php");
	$logfile = fopen("logs/importToShopify.txt", "a+") or die("Unable to open log file!");
	addlog("Execution Started", "INFO");
	addlog($_REQUEST['id'],"Product_id");
	$product_id = $_REQUEST['id'];
    //addlog("Execution data", $argv[0]." ".$argv[1]." ".$argv[2]);
   // include config.php;
   $conn = new mysqli('localhost', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
	// Check connection
	 if(!$conn){
        addlog("Error While connecting database","DATABASE CONNECTION ERROR");
    }
    mysqli_set_charset($conn, "utf8");
   /* $new_products = $conn->query("SELECT * FROM `product_variants` WHERE user_id = 21373 and status = 'Imported' and asin not in (SELECT product_asin from fetchReviews where user_id = 21373)");
	if($new_products->num_rows > 0){
		while($row = $new_products->fetch_assoc()){
		    $asin = $row['asin'];
			$asin = preg_replace("~[^a-z0-9:]~i", "", $asin); 
			$conn->query("insert into fetchReviews (user_id, product_asin, status, created_at, updated_at) values (21373, '".mysqli_real_escape_string($conn, $asin)."', 0, now(), now())");
			echo $asin;
		}
	}
    exit;*/
    $new_products = $conn->query("SELECT * FROM `product_variants` WHERE user_id = 21373");
	if($new_products->num_rows > 0){
		while($row = $new_products->fetch_assoc()){
		    $asin = $row['asin'];
			$asin = preg_replace("~[^a-z0-9:]~i", "", $asin); 
			$conn->query("update product_variants set asin = '".mysqli_real_escape_string($conn, $asin)."' where user_id = 21373 and id = ".$row['id']);
			echo $asin;
		}
	}
    exit;
	//$new_products = $conn->query("_variants where user_id = 3602 and price > 0)");
	$new_products = $conn->query("SELECT * FROM `products` WHERE `product_id` = ".$product_id." and shopifyproductid = '' and duplicate = 0 and block = 0");
	if($new_products->num_rows > 0){
		while($row = $new_products->fetch_assoc()){
		    addlog(json_encode($row),"PRODUCTS");
		    $user_id = $row['user_id'];
			insertToShopify($user_id,$row,$conn);
		}
	}else{
	    addlog(json_encode($new_products),"ERROR FETCHING ROWS");
	}
			
			
 	function getUser($user_id,$conn) {
 		$userresult = $conn->query("select * from users where installationstatus = 1 and id = ".$user_id);
			if ($userresult->num_rows > 0) {
				while($userrow = $userresult->fetch_assoc()) {
					return $userrow;	
 				}
 			}
 	}
  	function getSettings($user_id,$conn) {
       $settingsResult = $conn->query("select * from setting where user_id = ".$user_id);
		if($settingsResult->num_rows > 0){
			while($settingsRow = $settingsResult->fetch_assoc()) {
				return $settingsRow;
							
			}
		}
	}			
 	function insertToShopify($user_id,$productObject,$conn){
		addlog("insert to shopify: " . $user_id, "INFO");
		$currUser = getUser($user_id,$conn);
		//echo(json_encode($currUser));
		$settingObject = getSettings($user_id,$conn);
		addlog("setting accessed","INFO");
		$published = false;		
		$tags = array();
		$vendor = "";
		$product_type = "";
		$inventory_policy = null;
		$defquantity = 1;
		$markupenabled = 0;
		$markuptype = 'FIXED';
		$markupval = 0;
		$markupround = 0;
		$location_id = "";
		addlog(json_encode($settingObject),"SETTING");
		if($settingObject){
			$tags = $settingObject['tags'];
			if(strlen($tags) > 0){
				$tags = explode(",", $tags);
			} else {
				$tags = array();
			}
			if( $settingObject['published'] == 1){
				$published = true;
			}
			if(strlen($settingObject['vendor']) > 0){
				$vendor = $settingObject['vendor'];
			}
			if(strlen($settingObject['product_type']) > 0){
				$product_type = $settingObject['product_type'];
			}
			if($settingObject['inventory_policy']!="NO"){
				$inventory_policy = $settingObject['inventory_policy'];
			}
			if(isset($settingObject['defquantity'])){
				$defquantity = $settingObject['defquantity'];
			}
			if(isset($settingObject['markupenabled']) && $settingObject['markupenabled'] == 1){
				$markupenabled = true;
			}
			if(isset($settingObject['markuptype']) && strlen($settingObject['markuptype']) > 0){
				$markuptype = $settingObject['markuptype'];
			}
			if(isset($settingObject['markupval'])){
				$markupval = $settingObject['markupval'];
			}
			if(isset($settingObject['markupround']) && $settingObject['markupround'] == 1){
				$markupround = true;
			}
			if(isset($settingObject['shopifylocationid'])){
				$location_id = $settingObject['shopifylocationid'];
			}
		}
		
		$shopurl = $currUser['shopurl'];
		$token = $currUser['token'];
		$product_id = $productObject['product_id'];
		//print_r($productObject);
		$title = $productObject['title'];
		$description = $productObject['description'];
		$brand = $productObject['brand'];
		if($vendor != ''){
			$brand = $vendor;
		}	
		$productType = $productObject['product_type'];
		if($product_type != ''){
			$productType = $product_type;
		}
		$feature1 = $productObject['feature1'];
		//addlog("feature123".$feature1,"INFO");
		$feature2 = $productObject['feature2'];
		$feature3 = $productObject['feature3'];
		$feature4 = $productObject['feature4'];
		$feature5 = $productObject['feature5'];
		$featureStr = "";
		if(strlen($feature1) > 0){
			$featureStr .= '<li>'.$feature1.'</li>';
		}
		if(strlen($feature2) > 0){
			$featureStr .= '<li>'.$feature2.'</li>';
		}
		if(strlen($feature3) > 0){
			$featureStr .= '<li>'.$feature3.'</li>';
		}
		if(strlen($feature4) > 0){
			$featureStr .= '<li>'.$feature4.'</li>';
		}
		if(strlen($feature5) > 0){
			$featureStr .= '<li>'.$feature5.'</li>';
		}
		if(strlen($featureStr) > 0){
			$featureStr = '<br /><ul>'.$featureStr.'</ul>';
		}
		//$featureStr = '<p><b>Features</b></p>'.$featureStr;
		//$description = $featureStr.'<br/>'.$description;
		    $variantQuery = "select * from product_variants where product_id = ".$product_id." and user_id = ".$user_id." and block = 0 and duplicate = 0 and shopifyvariantid = ''";
		    addlog($variantQuery,"QUERY");
		        $variantResult = $conn->query("select * from product_variants where product_id = ".$product_id." and user_id = ".$user_id." and block = 0 and duplicate = 0 and shopifyvariantid = ''");
				$noOfVariants = $variantResult->num_rows;
				//print_r($variantResult->fetch_assoc()); 
				//echo $noOfVariants;
				
				
				
		$variantsArr = $variantResult; //$productObject->variants();
		$vCount = $variantResult->num_rows;
		if($vCount == 1){
		    addlog("count is  1<br/>","INFO");
			$variantObject = $variantResult->fetch_assoc();//$variantsArr->first();
			//addlog("variant Object : <br/>","INFO");
			//print_r($variantObject);
			//reset($variantObject);
            //$first = current($variantObject);
			$sku = $variantObject['sku'];
			$weight = $variantObject['weight'];
			$weight_unit = $variantObject['weight_unit'];
			$productid = $variantObject['product_id'];
			$producturl = $variantObject['detail_page_url'];
			addlog($price,"Currency Before Conversion");
			addlog($saleprice,"Currency Before Conversion");
			$price = $variantObject['price'];
			$saleprice = $variantObject['saleprice'];
			//$price =  currencyConverter($producturl, $variantObject['price'],$currUser,$conn);
			//$saleprice =  currencyConverter($producturl, $variantObject['saleprice'],$currUser,$conn);
            addlog($price,"Currency After Conversion");
			addlog($saleprice,"Currency After Conversion");
			
			$variant_id = $variantObject['id'];
			//echo  $variant_id."   pankaj <br/>";
			if($markupenabled == true){
				$price = applyPriceMarkup($price, $markuptype, $markupval, $markupround);
				$saleprice = applyPriceMarkup($saleprice, $markuptype, $markupval, $markupround);
			}			
			$detail_page_url = $variantObject['detail_page_url'];
			addlog("select * from product_images where user_id = ".$user_id." and variant_id = ".$variantObject['id'],"INFO");
			$imageResult = $conn->query("select imgurl from product_images where variant_id = ".$variantObject['id']);
			//print_r($imageResult->num_rows);
			$imagesArr = array();
			if($imageResult->num_rows > 0){
			    while($img_data = $imageResult->fetch_assoc()){
			      $imagesArr[] =  $img_data;
			    } 
				addlog($imageResult->num_rows,"INFO");
            }else{
                addlog("no data Found for Images","ERROR");
            }				
			//$imagesArr = $variantObject->images()->get();
			$images = array();
			$position = 1;
			foreach($imagesArr as $imageObject){
			    addlog(json_encode($imageObject['imgurl']),"Images To Be Uploaded Object");
				$imgUrl = $imageObject['imgurl'];
				if(!($strpos = stripos($imgUrl, "no-image"))){
				    addlog($imgUrl,"IMAGE URL");
					$images[] = array("src" => trim($imgUrl), "position" => $position++);
				}else{
				    addlog("Images not being parced","IMAGE PARSER ERROR");
				}
			}
			//print_r($imagesArr); 
			$productMetafields = array(array("key" => "isavailable", "value" => 1, "type" => "number_integer", "namespace" => "isaac"));
		    $variantMetafields = array(array("key" => "buynowurl", "value" => $detail_page_url, "type" => "single_line_text_field", "namespace" => "isaac"));
			addlog(json_encode($images),"Images To Be Uploaded");
			
			$data = array(
					"product"=>array(
						"title"=> mb_convert_encoding($title, "UTF-8"),
						"body_html"=> mb_convert_encoding($description, "UTF-8"),
						"vendor"=> $brand,
						"product_type"=> $productType,
						"published"=> $published,
						"tags"=> $tags,
						"published_scope" => "global",
						"images"=>$images,
						"metafields" => $productMetafields,
						"variants"=>array(
							array(
								"sku"=>$sku,
								"position"=>1,
								"price"=>number_format($saleprice, 2, '.', ''),
							/*	"compare_at_price"=>number_format($price, 2, '.', ''),*/
								"inventory_policy"=>"deny",
								"fulfillment_service"=> "manual",
								"inventory_management"=> $inventory_policy,
								"taxable"=>true,							
								"weight" => $weight,
								"weight_unit" => $weight_unit,
								"barcode" => '',
								"requires_shipping"=> true,
								"metafields" => $variantMetafields
							)
						)
					)
				);
		print_r($data);
			addlog(json_encode($data),"DATA PASSED TO ADDSHOPIFYPRODUCT");
			/*if($currUser->id == 279 || $currUser->id == 374){
				$data["product"]["template_suffix"] = "amazon";
			}*/
			$response = addShopifyProduct($token, $shopurl, $data);
			addlog(json_encode($response),"ADDSHOPIFYPRODUCT RESPONSE");
			//print_r($response);
			if($response){	
			    //addlog("<br/><h2>Response Generated</h2><br/>","INFO");
			    //print_r($response);
			    //addlog("<br/>updating .....<br/>","INFO");
				$shopifyproductid = $response["id"];
				$shopifyvariantid = $response["variants"][0]["id"];
				$shopifyinventoryid = $response["variants"][0]["inventory_item_id"];
				//addlog("<br/>Response data parsed properly upto line 239<br/>","INFO");
				//$variantObject->shopifyvariantid = $shopifyvariantid;
				//$variantObject->shopifyproductid = $shopifyproductid;
				//$variantObject->shopifyinventoryid = $shopifyinventoryid;
					//addlog("<br/>Response data saving properly upto line 244<br/>","INFO");
				//echo $response['handle'];
				echo $shopifyproductid;
				
				if($location_id == ""){
				    $location_id = getLocationId($token, $shopurl, $shopifyinventoryid);
				    if(!$conn->query("UPDATE `setting` SET `shopifylocationid` = '".$location_id."' WHERE `user_id` = '".$user_id."'")){
				       addlog("Error In Udating shopifylocationid in setting ","ERROR"); 
				    }
				}
				
				if($conn->query("UPDATE `product_variants` SET `handle`='".$response['handle']."',`shopifylocationid`='".$location_id."', `shopifyproductid`='".$shopifyproductid."',`shopifyvariantid`='".$shopifyvariantid."',`shopifyinventoryid`='".$shopifyinventoryid."' WHERE  `product_id`=".$product_id." AND `user_id`=".$user_id)){
				    //addlog("Product Variant Table Updated Properly<br/>","INFO");
				}	
			    //echo $variantObject->save();   
					
				//addlog("<br/>Response data saved properly upto line 245<br/>","INFO");
				$rowid = "";
				if($row = $conn->query("UPDATE `products` SET `shopifyproductid`= '".$shopifyproductid."',`status`='Imported' WHERE  `product_id`=".$product_id." AND `user_id`=".$user_id)){
				    addlog("Product table Updated Properly<br/>".$row->insert_id,"INFO");
				    $rowid= $row->insert_id;
				}
				
				if($conn->query("UPDATE `importToShopify` SET `status`=1 WHERE  `product_id`=".$product_id." AND `user_id`=".$user_id)){
				    addlog("Status Updated Properly<br/>","INFO");
				}
				//$productObject->shopifyproductid = $shopifyproductid;
				//$productObject->save();
				
				addlog("location_id : ".$location_id,"INFO");
				addlog("Response data parsed properly upto line 247<br/>","INFO");
				addlog("quantity".$defquantity,"INFO");
				if($inventory_policy == "shopify" && $location_id != ""){
				    addlog("<h3>updating shopify inventory</h3><br/>","INFO");
				    addlog("quantity".$defquantity,"INFO");
					updateShopifyInventory($token, $shopurl, $shopifyinventoryid, $location_id, $defquantity,$user_id,$conn,$rowid);
					//addlog("<h3>Updation Finished Properly</h3><br/>","INFO");
				}else{
				    //addlog("<br/><h3>Error in invetory or location at line 250</h3><br/>","INFO");
				    //addlog("inventory policy : ".$inventory_policy."<br/>","INFO");
				    //addlog("location id : ".$location_id."<br/>","INFO");
				}
			} else {
                //addlog("<br/><br/><h1>Error while excepting Response from shopify at line 245</h1>","INFO");
			}
		} else {
			addlog($vCount,"ERROR"); 
		}
		$skuconsumed = $currUser['skuconsumed'];
		$currUser['skuconsumed'] = $skuconsumed + 1;
		//$currUser->save();
	}
	function updateShopifyInventory($token, $shopurl, $inventory_item_id, $location_id, $quantity,$user_id,$conn,$rowid){
	    //addlog("entered to inventory funtion<br/>","INFO");
		$data = array("location_id" => $location_id, "inventory_item_id" => $inventory_item_id, "available" => $quantity);

		$url = "https://".$shopurl."/admin/api/2022-01/inventory_levels/set.json";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token, 'Content-Type: application/json; charset=utf-8'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec ($curl);
		curl_close ($curl);
		$response_arr = explode("\n", $response);
		$climit = -1;
		//addlog("response Generated <br/>","INFO");
		//print_r($response);addlog("<br/>","INFO");
		foreach($response_arr as $obj){
			if (strpos($obj, 'X-Shopify-Shop-Api-Call-Limit') !== false) {
				$tempArr = explode(":", $obj);
				$climit = substr(trim(end($tempArr)), 0, -3);
			}
			//addlog("<br/>","INFO");
		}
		if(intval($climit) > 35){
			sleep(5);
		}
		if( (strstr(($response_arr[0]), "200 OK")) || (strstr(($response_arr[1]), "200 OK")) || (strstr(($response_arr[2]), "200 OK")) ) {
			return true;
		} else if((strstr(($response_arr[0]), "HTTP/1.1 403 Forbidden")) || (strstr(($response_arr[0]), "HTTP/1.1 422 Unprocessable Entity"))) {
		    //addlog("entered in forbidden handler line 419","INFO");
			$new_location_id = getLocationId($token, $shopurl, $inventory_item_id);
			if(!$conn->query("UPDATE `setting` SET `shopifylocationid` = '".$new_location_id."' WHERE `user_id` = '".$user_id."'")){
			    addlog("Error In Udating shopifylocationid in setting ","ERROR"); 
			}
			if($new_location_id){
				$data = array("location_id" => $new_location_id, "inventory_item_id" => $inventory_item_id, "available" => $quantity);
				$url = "https://".$shopurl."/admin/api/2022-01/inventory_levels/set.json";
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token, 'Content-Type: application/json; charset=utf-8'));
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_VERBOSE, 0);
				curl_setopt($curl, CURLOPT_HEADER, 1);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				$response = curl_exec ($curl);						
				curl_close ($curl);
				$response_arr = explode("\n", $response);
				$climit = -1;
				foreach($response_arr as $obj){
					if (strpos($obj, 'X-Shopify-Shop-Api-Call-Limit') !== false) {
						$tempArr = explode(":", $obj);
						$climit = substr(trim(end($tempArr)), 0, -3);
					}
				}
				if(intval($climit) > 35){
					sleep(5);
				}
				if( (strstr(($response_arr[0]), "200 OK")) || (strstr(($response_arr[1]), "200 OK")) || (strstr(($response_arr[2]), "200 OK")) ) {
					$conn->query("update product_variants set shopifylocationid = '".mysqli_real_escape_string($conn, $new_location_id)."' where user_id = ".$user_id." and id = ".$rowid);
					$conn->query("update settings set shopifylocationid = '".mysqli_real_escape_string($conn, $new_location_id)."' where user_id = ".$user_id);
					return true;
				} else {
				    //addlog("Error in database Updation<br/>","INFO");
					return false;
				}
			}
		}		
		return false;
	}
	
		function getConversionRate($userDetails,$amz_currency){
	    global $conn;
	    addlog("shopcurrenct us ".$userDetails['shopcurrency'],"INFO");
	    addlog("SELECT DISTINCT(conversionrate) FROM `conversionrates` WHERE `basecurrency`='".$amz_currency."' AND `convertcurrency`='".$userDetails['shopcurrency']."'","CURRENCY QUERY");
	    $currencyObj = $conn->query("SELECT DISTINCT(conversionrate) FROM `conversionrates` WHERE `basecurrency`='".$amz_currency."' AND `convertcurrency`='".$userDetails['shopcurrency']."'");
        if($currencyObj->num_rows == 0){
        	addlog("Price Conversion Policies Not Found","ERROR");
        	die("price Conversion Error");
        }else{
        	$currency = $currencyObj->fetch_assoc();
        	addlog(json_encode($currency),"Result Array");
        	addlog($currency['conversionrate'] ,"Conversion Rate");
        	return $currency['conversionrate'];
        }
	}
	function currencyConverter($producturl,$price,$currUser,$conn){
	    //addlog("user id is".$user_id,"INFO");
	    $userDetails = $currUser;//getUser($user_id,$conn);
	    addlog("user details are ".json_encode($currUser),"INFO");
	    addlog("product url is ".$producturl,"INFO");
		    $domain = parse_url($producturl, PHP_URL_HOST);
		    addlog("domain is ".$domain,"INFO");
    		if($domain == "amazon.com" || $domain == "www.amazon.com"){
    		    addlog("domain is amazon.com yes  ".$domain,"INFO");
    		    $shopify_price_lock = getConversionRate($userDetails,"USD");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
    		if($domain == "amazon.ca" || $domain == "www.amazon.ca"){
    		    $shopify_price_lock = getConversionRate($userDetails,"CAD");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
    		if($domain == "amazon.in" || $domain == "www.amazon.in"){
    		    $shopify_price_lock = getConversionRate($userDetails,"INR");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
    		if($domain == "amazon.co.uk" || $domain == "www.amazon.co.uk"){
    		    $shopify_price_lock = getConversionRate($userDetails,"GBP");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
    		if($domain == "amazon.com.br" || $domain == "www.amazon.com.br"){
    		    $shopify_price_lock = getConversionRate($userDetails,"BRL");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
    		if($domain == "amazon.com.mx" || $domain == "www.amazon.com.mx"){
    		    $shopify_price_lock = getConversionRate($userDetails,"USD");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
    		if($domain == "amazon.de" || $domain == "www.amazon.de"){
    		    $shopify_price_lock = getConversionRate($userDetails,"EUR");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
    		if($domain == "amazon.es" || $domain == "www.amazon.es"){
    		    $shopify_price_lock = getConversionRate($userDetails,"EUR");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
    		if($domain == "amazon.fr" || $domain == "www.amazon.fr"){
    		    $shopify_price_lock = getConversionRate($userDetails,"EUR");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
    		if($domain == "amazon.it" || $domain == "www.amazon.it"){
    		    $shopify_price_lock = getConversionRate($userDetails,"EUR");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
    		if($domain == "amazon.co.jp" || $domain == "www.amazon.co.jp"){
    		    $shopify_price_lock = getConversionRate($userDetails,"JPY");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
    		if($domain == "amazon.cn" || $domain == "www.amazon.cn"){
    		    $shopify_price_lock = getConversionRate($userDetails,"CNY");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
    		if($domain == "amazon.com.au" || $domain == "www.amazon.com.au"){
    		    $shopify_price_lock = getConversionRate($userDetails,"AUD");
    		    $price = $shopify_price_lock*$price;
    			return $price;
    		}
	}
	
	function getLocationId($token, $shopurl, $inventory_item_id){
	    addlog("getLocation called","INFO");
		$apiurl = "https://".$shopurl."/admin/api/2022-01/inventory_levels.json?inventory_item_ids=".$inventory_item_id;
		addlog("getLocation called".$apiurl,"INFO");
		$session = curl_init();
		curl_setopt($session, CURLOPT_URL, $apiurl);
		curl_setopt($session, CURLOPT_HTTPGET, 1);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);
		$response = curl_exec($session);
		addlog($response,"INFO");
		curl_close ($session);
		if($response){
			$resObj = json_decode($response, true);
			if(isset($resObj['inventory_levels']) && isset($resObj['inventory_levels'][0]['location_id'])){
			    addlog(json_encode($resObj),"INFO");
				return trim($resObj['inventory_levels'][0]['location_id']);
			}			
		}
		addlog("not proper Response for getLocationId","ERROR");
		return false;
	}
    function applyPriceMarkup($price, $markuptype, $markupval, $markupround){
		$newprice = $price;
		if($markuptype == "FIXED"){
			$newprice = $price + $markupval;
		} else {
			$newprice = $price + $price*$markupval/100;
		}
		if($markupround){
			$newprice = round($newprice) - 0.01;
		}
		return $newprice;
	 }

    function addShopifyProduct($token, $shopurl, $data){
        addlog(sizeof($data['product']),"SIZE OF DATA PASSED TO SHOPIFY");
		$url = "https://".$shopurl."/admin/api/2022-01/products.json";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token, 'Content-Type: application/json; charset=utf-8'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec ($curl);
		curl_close ($curl);
echo $response;
		$response_arr = explode("\n",$response);
		if( (strstr(($response_arr[0]), "201")) || (strstr(($response_arr[1]), "201")) || (strstr(($response_arr[2]), "201")) ){
			$product_json = end($response_arr);
			$product_arr = json_decode($product_json, true);
			$product_arr = $product_arr["product"];
			return $product_arr;
		} else {
		    addlog($response,"Add Shopify Response");
		}
		return null;
	}
	function addlog($message, $type){
		global $logfile;
		$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
		fwrite($logfile, $txt);
	}

	addlog("Execution Finished", "INFO");
	fclose($logfile);

?>