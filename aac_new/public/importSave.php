<?php
   header('Access-Control-Allow-Origin: *');
	set_time_limit(0);
	//require_once("includes/config.php");
	require("../app/Http/Controllers/getJson.php");
	require("proxycrawl/test.php");
	$logfile = fopen("logs/reimportSave.txt", "a+") or die("Unable to open log file!");
	addlog("Execution Started", "INFO");
	addlog("Execution data", $argv[0]." ".$argv[1]." ".$argv[2]." ".$argv[3]);
	//$conn = new mysqli('localhost', 'root', '', 'aac_dev');		
	$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
    
	// Check connection
	if ($conn->connect_error) {
		addlog("Database connection failed: " . $conn->connect_error, "ERROR");
		die("Database Connection Failed");
	}
	mysqli_set_charset($conn, "utf8");

	// $argv[3] = '499859524';
	// $argv[1] = 'B0746RDPCQ';	
	// $argv[4] = 'shopify';
	// $argv[2] = 1;
	
	$asin = $argv[1];
	$user_id = $argv[2];
	$product_id = $argv[3];
	$shopify = $argv[4];

	addlog("SELECT * FROM `products` WHERE `product_id`=$product_id and `user_id`=$user_id","INFO");
	$product = $conn->query("SELECT * FROM `products` WHERE `product_id`=$product_id and `user_id`=$user_id");

	if($pro = $product->fetch_assoc()){
		print_r($pro);
		//tusharforeach($pro as $proa){
		//echo $proa;}
	    $currUser = getUser($user_id,$conn);
	    $shopurl = $currUser['shopurl'];
		$token = $currUser['token'];		
		$detail_page_url = $conn->query("SELECT detail_page_url FROM product_variants WHERE product_id = $product_id AND user_id = $user_id LIMIT 1");
		$detail_page_url = $detail_page_url->fetch_assoc();
		$detail_page_url = $detail_page_url['detail_page_url'];		
		addlog($pro['shopifyproductid'],"INFO");
		if($shopify == "shopify"){
		    addlog("Deleting From Shopify","SHOPIFY");			
			$response = deleteShopifyProduct($token,$shopurl,$pro['shopifyproductid']);
			if($response == "Deleted"){
    	        $skuconsumed = $currUser['skuconsumed'];
    	        $skuconsumed = $skuconsumed - 1;
                addlog("UPDATE `users` SET `skuconsumed`='$skuconsumed' WHERE id=".$currUser['id'],"UPDATE QUERY");
                if(!$conn->query("UPDATE `users` SET `skuconsumed`='$skuconsumed' WHERE id=".$currUser['id'])){
                    addlog("Error In Updating The skuconsumed limit","ERROR");
                }
    	        addlog("Deleted From Shopify","SHOPIFY");
    	        deleteFromLocal($conn,$product_id,$user_id,$asin,$detail_page_url,$token,$shopurl);
    	    }else{
    	        addlog(json_decode($response),"ERROR");
    	        addlog("Data Cannot be deleted fromshopify","Shopify DELETE");
    	        deleteFromLocal($conn,$product_id,$user_id,$asin,$detail_page_url,$token,$shopurl);
    	    }   
		}else if($shopify == "local"){
		    //deleteFromLocal($conn,$product_id,$user_id,$asin);
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
	function getUser($user_id,$conn) {
 		$userresult = $conn->query("select * from users where installationstatus = 1 and id = ".$user_id);
			if ($userresult->num_rows > 0) {
				while($userrow = $userresult->fetch_assoc()) {
					return $userrow;	
 				}
 			}
 	}
 	function updateShopifyInventory($token, $shopurl, $inventory_item_id, $location_id, $quantity,$user_id,$conn,$rowid){
	    //addlog("entered to inventory funtion<br/>","INFO");
		$data = array("location_id" => $location_id, "inventory_item_id" => $inventory_item_id, "available" => $quantity);

		//$url = "https://".$shopurl."/admin/inventory_levels/set.json";
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
		echo $response;//tushar
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
		if( (strstr(($response_arr[0]), "200")) || (strstr(($response_arr[1]), "200")) || (strstr(($response_arr[2]), "200")) ) {
			return true;
		} else if((strstr(($response_arr[0]), "HTTP/1.1 403 Forbidden")) || (strstr(($response_arr[0]), "HTTP/1.1 422 Unprocessable Entity"))) {
		    //addlog("entered in forbidden handler line 419","INFO");
			$new_location_id = getLocationId($token, $shopurl, $inventory_item_id);
			if(!$conn->query("UPDATE `setting` SET `shopifylocationid` = '".$new_location_id."' WHERE `user_id` = '".$user_id."'")){
			    addlog("Error In Udating shopifylocationid in setting ","ERROR"); 
			}
			if($new_location_id){
				$data = array("location_id" => $new_location_id, "inventory_item_id" => $inventory_item_id, "available" => $quantity);
				//$url = "https://".$shopurl."/admin/inventory_levels/set.json";
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
				if( (strstr(($response_arr[0]), "200")) || (strstr(($response_arr[1]), "200")) || (strstr(($response_arr[2]), "200")) ) {
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
	function getLocationId($token, $shopurl, $inventory_item_id){
	    addlog("getLocation called","INFO");
		//$apiurl = "https://".$shopurl."/admin/inventory_levels.json?inventory_item_ids=".$inventory_item_id;
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
			$newprice = round($newprice) -.01;
		}
		return $newprice;
	 }

    function addShopifyProduct($token, $shopurl, $data){

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
		echo $response;
		curl_close ($curl);

		$response_arr = explode("\n",$response);

		if( (strstr(($response_arr[0]), "201")) || (strstr(($response_arr[1]), "201")) || (strstr(($response_arr[2]), "201")) ){
			echo '<br/>inside 201 done ';
			$product_json = end($response_arr);
			$product_arr = json_decode($product_json, true);
			$product_arr = $product_arr["product"];

			echo '<br/>';
			print_r($product_arr);
			//adding to test
			return $product_arr;
		} else {
			//print_r($data);
			//print_r($response_arr);
			//addlog("Error adding product with SKU - ".$product["product"]["variants"]["sku"].", Err Details: ".serialize($response_arr), "ERROR");
		}
		return null;
	}
 	function deleteFromLocal($conn,$product_id,$user_id,$asin,$detail_page_url,$token,$shopurl){
 	    if($conn->query("DELETE  FROM `product_images` WHERE `asin`='$asin' AND `user_id`='$user_id'")){
 	       if($conn->query("DELETE  FROM `product_variants` WHERE `product_id`='$product_id' AND `asin`='$asin' AND `user_id`='$user_id'")){
 	           if($conn->query("DELETE  FROM `products` WHERE `product_id`='$product_id' AND `user_id`='$user_id'")){																				
						$temp = getsource($detail_page_url);
						$res = getjsonrdata($temp,$detail_page_url,$user_id);
							//Adding price correction	
					 addlog(json_encode($res), "INFO");
					 $productObj = json_decode($res['message'], true);
					 
					 echo '<pre>'; print_r($res); echo '</pre>';	
					 echo 'now printing dataArr';					 			 					 
					 $title = "";
						$description = "";
						$brand = "";
						$product_type = "";
						$sku = "";
						$asin = "";
						$url = "";
						$price = "";
						$list_price = "";
						$images = array();
						$feature1 = "";
						$feature2 = "";
						$feature3 = "";
						$feature4 = "";
						$feature5 = "";
						$quantity = 0;
						addlog($productObj['Title'], "cINFO");
						if(isset($productObj['Title'])) {
							$title = $productObj['Title'];
						}
						if(isset($productObj['description'])) {
							$description = $productObj['description'];
						}
						if(isset($productObj['brand'])) {
							$brand = $productObj['brand'];
						}
						if(isset($productObj['category'])) {
							$product_type = $productObj['category'];
						}
						if(isset($productObj['asin'])) {
							$asin = $productObj['asin'];
							$sku = $productObj['asin'];
						}
						if(isset($productObj['url'])) {
							$url = $productObj['url'];
						}
						if(isset($productObj['currency'])) {
							$currency = $productObj['currency'];
						}
						if(isset($productObj['price'])) {
							$price = $productObj['price'];
							$price = getAmount($price);
						}
						if(isset($productObj['list_price'])) {
							$list_price = $productObj['list_price'];
							$list_price = getAmount($list_price);
						} else {
							$list_price = $price;
						}
						if(isset($productObj['in_stock___out_of_stock']) && $productObj['in_stock___out_of_stock'] == 'In stock.') {
							$quantity = 1;
						}
						if(isset($productObj['high_resolution_image_urls'])) {
							$high_resolution_image_urls = $productObj['high_resolution_image_urls'];
							$images = explode("|", $high_resolution_image_urls);
							$images = array_map("trim", $images);
							
						}

						if(isset($productObj['bullet_points'])) {			
							$bullet_points = $productObj['bullet_points'];				
							//$tempArr = explode("|", $bullet_points);
							//$tempArr = array_map("trim", $tempArr);
							$tempArr = $bullet_points;
							$feature1 = isset($tempArr[0])?$tempArr[0]:"";
							$feature2 = isset($tempArr[1])?$tempArr[1]:"";
							$feature3 = isset($tempArr[2])?$tempArr[2]:"";
							$feature4 = isset($tempArr[3])?$tempArr[3]:"";
							$feature5 = isset($tempArr[4])?$tempArr[4]:"";			
						}
						$query = "INSERT INTO products(title, description, brand, product_type, status, user_id) values ('".mysqli_real_escape_string($conn, $title)."', '".mysqli_real_escape_string($conn, $description)."', '".mysqli_real_escape_string($conn, $brand)."', '".mysqli_real_escape_string($conn, $product_type)."', 'Import in progress', ".$user_id.")";
						$conn->query($query);
						$product_id = $conn->insert_id;
						$query = "INSERT INTO product_variants(product_id, sku, asin, price, saleprice, currency, detail_page_url, user_id) values (".$product_id.", '".mysqli_real_escape_string($conn, $sku)."', '".mysqli_real_escape_string($conn, $asin)."', '".mysqli_real_escape_string($conn, $price)."', '".mysqli_real_escape_string($conn, $list_price)."', '".mysqli_real_escape_string($conn, $currency)."', '".mysqli_real_escape_string($conn, $detail_page_url)."', ".$user_id.")";
						$conn->query($query);    	
						$variant_id = $conn->insert_id;
						foreach ($images as $imageUrl) {
							if($imageUrl != ""){
							$query = "INSERT INTO product_images(variant_id, asin, imgurl, user_id) VALUES ('".mysqli_real_escape_string($conn, $variant_id)."', '".mysqli_real_escape_string($conn, $asin)."', '".mysqli_real_escape_string($conn, $imageUrl)."', ".$user_id.")";
							 $conn->query($query);
							}
						} 
					 //return "success";					 
					 $res = insertToShopify($user_id, $shopurl, $token, $productObj, $conn, $product_id);
					 if($res){
						 //return "success";
						 addlog('Product successfully added to shopify',"INFO");
					 } else {
						 //return false;
						 addlog('Product failed adding to shopify',"INFO");
					 }


 	                /*if($res != null && $res != "Product Already Exists"){
 	                    addlog("Product id : $res","INFO");
 	                    addlog("SELECT * FROM `products` WHERE `product_id`='$res' AND `user_id`='$user_id'","INFO");
 	                    $product = $conn->query("SELECT * FROM `products` WHERE `product_id`='$res' AND `user_id`='$user_id'");
 	                    if($productObject = $product->fetch_assoc()){
 	                        insertToShopify($user_id,$productObj,$conn);
 	                    }
 	                }*/
 	            }else{
         	        addlog("Error In deleteing products in local","ERROR");
         	    }
 	        }else{
 	            addlog("Error In deleteing product variants in local","ERROR");
 	        }
 	    }else{
 	        addlog("Error In deleteing product images in local","ERROR");
 	    }
	 }
	 
	function deleteShopifyProduct($token,$shopurl,$product_id){		
		//return 'Deleted';//remove this tushar
	    addlog($token,"INFO");
		//$url = "https://".$shopurl."/admin/products/$product_id.json";
		$url = "https://".$shopurl."/admin/api/2022-01/products/".$product_id.".json";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token, 'Content-Type: application/json; charset=utf-8'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
		//curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec ($curl);
		curl_close ($curl);
        
        $header_size = curl_getinfo($ch);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        addlog($header,"INFO");
        //addlog($body,"INFO");
        
        
		$response_arr = explode("\n",$response);

		if( (strstr(($response_arr[0]), "200")) || (strstr(($response_arr[1]), "200")) || (strstr(($response_arr[2]), "200")) ){
			$product_json = end($response_arr);
			$product_arr = json_decode($product_json, true);
			$product_arr = $product_arr["product"];
			echo 'PRODUCT DELETED FROM SHOPIFY';
			return 'Deleted';
		} else {
			//print_r($data);
			//print_r($response_arr);
			//addlog("Error adding product with SKU - ".$product["product"]["variants"]["sku"].", Err Details: ".serialize($response_arr), "ERROR");
		}
		return $response;
	}//$user_id, $shopurl, $token, $productObj
	function insertToShopify($user_id, $shopurl, $token, $productObject, $conn, $product_id){
		global $conn;
		addlog("insert to shopify: " . $user_id, "ERROR");
		print_r($productObject);		
		//$currUser = getUser($user_id,$conn);
		//echo(json_encode($currUser));
		$settingObject = getSettings($user_id,$conn);		
		$published = false;		
		$tags = array();
    	$vendor = "";
    	$product_type = "";
    	$inventory_policy = null;
    	$defquantity = 1;
    	$markupenabled = 0;
    	$currency = '';
    	$markuptype = 'FIXED';
    	$markupval = 0;
    	$markupround = 0;
    	$location_id = "";
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
    	$title = $productObject['Title'];
    	$description = $productObject['description'];
    	$brand = $productObject['brand'];
    	if($vendor != ''){
    		$brand = $vendor;
    	}	
    	$productType = $productObject['category'];
    	if($product_type != ''){
			$productType = $product_type;
    	}
    	if(isset($productObject['bullet_points'])) {			
			$bullet_points = $productObject['bullet_points'];				
			//$tempArr = explode("|", $bullet_points);
			//$tempArr = array_map("trim", $tempArr);
			$tempArr = $bullet_points;
			$feature1 = isset($tempArr[0])?$tempArr[0]:"";
			$feature2 = isset($tempArr[1])?$tempArr[1]:"";
			$feature3 = isset($tempArr[2])?$tempArr[2]:"";
			$feature4 = isset($tempArr[3])?$tempArr[3]:"";
			$feature5 = isset($tempArr[4])?$tempArr[4]:"";		
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
			$description = $description.$featureStr;    	
		}
						
			$variantResult = $conn->query("select * from product_variants where product_id = ".$product_id." and user_id = ".$user_id." and block = 0 and duplicate = 0 and shopifyvariantid = ''");
			addlog("select * from product_variants where product_id = ".$product_id." and user_id = ".$user_id." and block = 0 and duplicate = 0 and shopifyvariantid = ''", "cINFO");
    	$noOfVariants = $variantResult->num_rows;
    	$vCount = $variantResult->num_rows;
    	if($vCount == 1){
		    //addlog("count is  1<br/>","INFO");
			$variantObject = $variantResult->fetch_assoc();//$variantsArr->first();
			//addlog("variant Object : <br/>","INFO");
			//print_r($variantObject);
			//reset($variantObject);
            //$first = current($variantObject);
			$sku = $variantObject['sku'];
			$weight = $variantObject['weight'];
			$weight_unit = $variantObject['weight_unit'];
			$productid = $variantObject['product_id'];
			$price = $variantObject['price'];
			$saleprice = $variantObject['saleprice'];
			$variant_id = $variantObject['id'];
			if($price==$saleprice){
				$price = "";
			}
			//echo  $variant_id."   pankaj <br/>";

			addlog($markupval,"MARKUPVALUE");
			addlog(json_encode($markupenabled),"markupenabled");
			if($markupenabled){
				$price = applyPriceMarkup($price, $markuptype, $markupval, $markupround);
				$saleprice = applyPriceMarkup($saleprice, $markuptype, $markupval, $markupround);
				echo 'inside here markupprice'.$price.'  '.$saleprice;
			}
			echo '<br/>printing price: '.$price.' saleprice: '.$saleprice;//tushar
			echo '<br/>printing price: '.$price.' saleprice: '.$saleprice;//tushar
			addlog($price." ".$saleprice,"Price Sale");
			$detail_page_url = $variantObject['detail_page_url'];
			$imageResult = $conn->query("select imgurl from product_images where variant_id = ".$variantObject['id']);
			$imagesArr = array();
			if($imageResult->num_rows > 0){
    			while($row = mysqli_fetch_array($imageResult))
				{
					$imagesArr[] = $row['imgurl'];
				}
			} else {
				addlog("no data Found for Images","ERROR");
			}	
			$images = array();
			$position = 1;
    		foreach($imagesArr as $imageObject){    		
	        	$imgUrl = $imageObject;
		    	if(!($strpos = stripos($imgUrl, "no-image"))){
					$images[] = array("src" => trim($imgUrl), "position" => $position++);
        		}
    		}
			
			//print_r($imagesArr); 
			$productMetafields = array(array("key" => "isavailable", "value" => 1, "type" => "number_integer", "namespace" => "isaac"));
	    	$variantMetafields = array(array("key" => "buynowurl", "value" => $detail_page_url, "type" => "single_line_text_field", "namespace" => "isaac"));
			$data = array(
					"product"=>array(
						"title"=> $title,
						"body_html"=> $description,
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
								"compare_at_price"=>$price,
								"inventory_policy"=>"deny",
								"fulfillment_service"=> "manual",
								"inventory_management"=> $inventory_policy,
								"taxable"=>true,							
								// "weight" => $weight,
								// "weight_unit" => $weight_unit,
								"barcode" => '',
								"requires_shipping"=> true,
								"metafields" => $variantMetafields
							)
						)
					)
				);
			/*if($currUser->id == 279 || $currUser->id == 374){
				$data["product"]["template_suffix"] = "amazon";
			}*/						
			$response = addShopifyProduct($token, $shopurl, $data);
			addLog(json_encode($response),"INFO");
			//print_r($response);
			if($response){
				echo 'printing shopify added product and then response';
				print_r($response);
			    //addlog("<br/><h2>Response Generated</h2><br/>","INFO");
			    //print_r($response);
			    //addlog("<br/>updating .....<br/>","INFO");
				$shopifyproductid = $response["id"];
				$shopifyvariantid = $response["variants"][0]["id"];
				$shopifyinventoryid = $response["variants"][0]["inventory_item_id"];
				$shopifyimages = array();
				$shopifyimages = $response["images"];
				$imagesindb = $conn->query("SELECT * FROM `product_images` WHERE `variant_id` = '".$variantObject['id']."'");
				$i=0;
				$images=array();
				while ($row = mysqli_fetch_assoc($imagesindb)) {
					$images[$i] = $row;
					$i++;
				}

				// $num_results = mysqli_num_rows($imagesindb);
				// $row=array();
				// for($i=0; $i<$num_results; $i++) {
				// 	$row = mysqli_fetch_assoc($imagesindb);
				// }
				echo 'putiiiiiiiiiiiiiiiiing<br/><br/><br/> ';
					print_r($images);
				// $imagerowarray = array();
				// $counter = 0;
				// print_r($row);
				// foreach($row as $r){
				// 	$imagerowarray[$counter] = $r;
				// 	$counter = $counter+1;
				// }
				// print_r($row);
				// print_r($imagerowarray);
				 $counter = 0;
				foreach($shopifyimages as $shopifyimage){
					echo $images[$counter]['id'];
					$index = $imagerowarray[$counter];
					$shopifyimageid = $shopifyimage['id'];
					echo $shopifyimageid;
					$conn->query("UPDATE `product_images` SET `shopifyimageid`='$shopifyimageid' WHERE `id` = '".$images[$counter]['id']."'");
					$counter=$counter+1;
				}

				//addlog("<br/>Response data parsed properly upto line 239<br/>","INFO");
				//$variantObject->shopifyvariantid = $shopifyvariantid;
				//$variantObject->shopifyproductid = $shopifyproductid;
				//$variantObject->shopifyinventoryid = $shopifyinventoryid;
					//addlog("<br/>Response data saving properly upto line 244<br/>","INFO");
				//echo $response['handle'];
				
				if($location_id == ""){
				    $location_id = getLocationId($token, $shopurl, $shopifyinventoryid);
				    if(!$conn->query("UPDATE `setting` SET `shopifylocationid` = '".$location_id."' WHERE `user_id` = '".$user_id."'")){
				       addlog("Error In Udating shopifylocationid in setting ","ERROR"); 
				    }
				}
				echo '<br/>ID :'.$shopifyproductid;//testing
				echo '<br/>ID :'.$shopifyproductid ;
				echo '<br/>ID :'.$shopifyvariantid ;
				echo '<br/>ID :'.$shopifyinventoryid;
				echo '<br/>ID :'.$location_id;
				
				if($conn->query("UPDATE `product_variants` SET `handle`='".$response['handle']."',`shopifylocationid`='".$location_id."', `shopifyproductid`='".$shopifyproductid."',`shopifyvariantid`='".$shopifyvariantid."',`shopifyinventoryid`='".$shopifyinventoryid."' WHERE  `product_id`=".$product_id." AND `user_id`=".$user_id)){
				    addlog("Product Variant Table Updated Properly<br/>","INFO");					
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
		}else {
			// To 
		}
		$currUser = getUser($user_id,$conn);
		$skuconsumed = $currUser['skuconsumed']+1;
		$currUser['skuconsumed'] = $skuconsumed;
        addlog("UPDATE `users` SET `skuconsumed`='$skuconsumed' WHERE id=".$currUser['id'],"UPDATE QUERY");
        if(!$conn->query("UPDATE `users` SET `skuconsumed`='$skuconsumed' WHERE id=".$currUser['id'])){
            addlog("Error In Updating The skuconsumed limit","ERROR");
        }
		//$currUser->save();
	}
   
	
	 function get_html_scraper_api_content($url) {
		addlog("start","INFO");        
		//$url= strtok($url, '?');
		$ch = curl_init();
		//curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com/?key=bccfd6a1043eeef4b878ab667efac22b&url=".urlencode($url)."/ip&country_code=".$country_code);
		//curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com/?key=bccfd6a1043eeef4b878ab667efac22b&url=".urlencode($url));
		curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com?api_key=9b0f3100086c345f97d98f1784f75cce&url=".urlencode($url));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  "Accept: application/json"
		));

		$response = curl_exec($ch);
		curl_close($ch);
		addlog("closed","INFO");
		echo 'thosa scarpper'.$response;
		return $response;
	}

	

	
	 function getProxyData($proxy_port,$proxy_ip,$loginpassw,$url){
	    /*$aContext = array(
	    			'http' => array(
	        		'proxy' => "tcp://$proxy",
	        		'timeout' => 30,
	        		'request_fulluri' => true,
	        		'header' => "Proxy-Authorization: Basic ".$auth
			    ),
			);
			
			
	    $cxContext = stream_context_create($aContext);*/
	    addlog($proxy_port.$proxy_ip.$loginpassw.$url,"INFO");
	       // try{
				//$sFile = file_get_contents($url,true,$cxContext);
				//$cookie = tempnam ("/tmp", "CURLCOOKIE");
                $ch = curl_init();
                curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
                curl_setopt( $ch, CURLOPT_URL, $url );
                //curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
                curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
                curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');
                curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);
                curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
                curl_setopt( $ch, CURLOPT_ENCODING, "" );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
                curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
                curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
                curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
                curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
                $content = curl_exec( $ch );
                $response = curl_getinfo( $ch );
                curl_close ( $ch );
                //print_r($response);
                //print_r($content);
				return $content;
		//	}catch(\Exception $e){
			
			
			//	return null;
				//@mail("pankajnarang81@gmail.com", "ProductController: Proxy Crawel Not Responding", json_encode($currentProxy));
		//	}
	}
	
	
	 function getAmount($money) {
	    addlog("converting to amount","INFO");
	    addlog($money,"INFO");
	    $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
		$onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);
	    $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;
	    $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
		$removedThousendSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '',  $stringWithCommaOrDot);
	    return (float) str_replace(',', '.', $removedThousendSeparator);
	}

	 function verifyAmazonDomain($producturl){
		$domain = parse_url($producturl, PHP_URL_HOST);
		if($domain == "amazon.com" || $domain == "www.amazon.com"){
			return true;
		}
		if($domain == "amazon.ca" || $domain == "www.amazon.ca"){
			return true;
		}
		if($domain == "amazon.in" || $domain == "www.amazon.in"){
			return true;
		}
		if($domain == "amazon.co.uk" || $domain == "www.amazon.co.uk"){
			return true;
		}
		if($domain == "amazon.com.br" || $domain == "www.amazon.com.br"){
			return true;
		}
		if($domain == "amazon.com.mx" || $domain == "www.amazon.com.mx"){
			return true;
		}
		if($domain == "amazon.de" || $domain == "www.amazon.de"){
			return true;
		}
		if($domain == "amazon.es" || $domain == "www.amazon.es"){
			return true;
		}
		if($domain == "amazon.fr" || $domain == "www.amazon.fr"){
			return true;
		}
		if($domain == "amazon.it" || $domain == "www.amazon.it"){
			return true;
		}
		if($domain == "amazon.co.jp" || $domain == "www.amazon.co.jp"){
			return true;
		}
		if($domain == "amazon.cn" || $domain == "www.amazon.cn"){
			return true;
		}
		return false;
	}

	 function createsingle($asin)
    {
    	$temp = explode(",", $asin);
		$count = count($temp);
		if($count == 0){
			return response()->json(['error' => ["msg"=>["Please choose atleast one product."]]], 406);
		} else if($count > 10){
			return response()->json(['error' => ["msg"=>["Please choose less than 10 products."]]], 406);
		}

    	$currUser = Auth::User();
		$amzKey = $currUser->amzKey()->first();
		if(!$amzKey){
			return response()->json(['error' => ["msg"=>['Amazon AWS keys are required for this operation.']]], 406);
		}
		foreach($temp as $t){
			$vcount = $currUser->variants()->where('asin', $t)->count();			
			if($vcount != 0) {
				return response()->json(['error' => ["msg"=>['Product already exist.']]], 406);
				break;
			}	
		}
		try {			
			$conf = new GenericConfiguration();
			$client = new \GuzzleHttp\Client();
			$request1 = new \ApaiIO\Request\GuzzleRequest($client);
			$conf
			->setCountry($amzKey->country)
			->setAccessKey($amzKey->aws_access_id)
			->setSecretKey($amzKey->aws_secret_key)
			->setAssociateTag($amzKey->associate_id)
			->setRequest($request1)
			->setResponseTransformer(new \ApaiIO\ResponseTransformer\XmlToArray());
			$app = new ApaiIO($conf);
			$lookup = new Lookup();
			$lookup->setItemId($temp);
			$lookup->setResponseGroup(array('Large', 'Small'));
			$response = $app->runOperation($lookup);
		
			if(!isset($response['Items']['Item'])){
				addlog("Item tag not found.","INFO");
				return response()->json(['error' => ["msg"=>['There was some error processing the request. Please contact customer support.']]], 406);
			}
			$items = $response['Items'];
			if(isset($items['Item'][0]['ASIN'])){
				foreach($items['Item'] as $item){
					insertItemWithoutVariants($item);
				}
				return response()->json(['success'], 200);
			} else if(isset($items['Item']['ASIN'])) {
				insertItemWithoutVariants($items['Item']);
				return response()->json(['success'], 200);
			} else {
				addlog("ASIN not found.","INFO");
				return response()->json(['error' => ["msg"=>['There was some error processing the request. Please contact customer support.']]], 406);
			}
		} catch (\Exception $e) {
			addlog("Exception: ".$e->getMessage(),"INFO");
        	return response()->json(['error' => ["msg"=>['There were some error processing this request. Please try again.']]], 406);
		}
    }	

	 function insertItemWithoutVariants($item){	
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
		$detailPageURL = "";

		// For products table
		if(isset($item['ItemAttributes'])){
			$itemAttributes	= $item['ItemAttributes'];
			if(isset($itemAttributes['Title'])){
				$title = trim($itemAttributes['Title']);
			}
			if(isset($itemAttributes['Feature'])){
				$features = $itemAttributes['Feature'];
				if(isset($features[0])) {
					$feature1 = $features[0];
				}
				if(isset($features[1])) {
					$feature2 = $features[1];
				}
				if(isset($features[2])) {
					$feature3 = $features[2];
				}
				if(isset($features[3])) {
					$feature4 = $features[3];
				}
				if(isset($features[4])) {
					$feature5 = $features[4];
				}
			}
			if(isset($itemAttributes['Brand'])){
				$brand = $itemAttributes['Brand'];
			}

			if(isset($itemAttributes['ProductGroup'])){
				$product_type = $itemAttributes['ProductGroup'];
			}
			if(isset($itemAttributes['UPC'])){
				$upc = $itemAttributes['UPC'];
			}
			if(isset($itemAttributes['PackageDimensions']['Weight'])){
				$weight = $itemAttributes['PackageDimensions']['Weight'];
				$weight = $weight/100;
			}
		}

		if(isset($item['EditorialReviews']['EditorialReview']['Content'])) {
			$description = $item['EditorialReviews']['EditorialReview']['Content'];
		}

		if(isset($item['ParentASIN'])){
			$parentasin = $item['ParentASIN'];
		}

		// For product_variants
		if(isset($item['ASIN'])){
			$asin = $item['ASIN'];
		}

		if(isset($item['DetailPageURL'])){
			$detailPageURL = $item['DetailPageURL'];
		}
		
		// Fetch offer details
		$offerlistingId = "";
		$price = "";
		$saleprice = "";
		$condition = "";

		if(isset($item['Offers'])){
			$offers = $item['Offers'];
			$totalOffers = isset($offers['TotalOffers'])?$offers['TotalOffers']:0;
			if($totalOffers > 0){
				$offer = $offers['Offer'];
				if($totalOffers > 1) {
					$offer = $offers['Offer'][0];
				}
				if(isset($offer['OfferAttributes']['Condition'])){
					$condition = $offer['OfferAttributes']['Condition'];
				}
				if(isset($offer['OfferListing'])){
					$offerListing = $offer['OfferListing'];
					if(isset($offerListing['OfferListingId'])){
						$offerlistingId = $offerListing['OfferListingId'];
					}
					if(isset($offerListing['Price']['Amount'])){
						$price = $offerListing['Price']['Amount']/100;
					}
					if(isset($offerListing['SalePrice']['Amount'])){
						$saleprice = $offerListing['SalePrice']['Amount']/100;
					} else {
						$saleprice = $price;
					}
				}
			} else {
				if(isset($item['OfferSummary']['LowestNewPrice'])){
					$lowestNewPrice = $item['OfferSummary']['LowestNewPrice'];
					if(isset($lowestNewPrice['Amount'])){
						$price = $lowestNewPrice['Amount']/100;
						$saleprice = $price;
					}
				}
			}
		}

		// fetch Images
		$images = array();
		if(isset($item['ImageSets'])){
			$imageSets = $item['ImageSets'];
			if(isset($imageSets['ImageSet'])){
				$imageSets = $imageSets['ImageSet'];
			}
			if(is_array($imageSets) && isset($imageSets[0]['SmallImage'])){
				foreach($imageSets as $imageSet){
					$imageUrl = getImageURL($imageSet);
					$imgCat = 'variant';
					if(isset($imageSet['@attributes']['Category'])){
						$imgCat = trim($imageSet['@attributes']['Category']);
					}
					if(strlen($imageUrl) > 0){
						if($imgCat == 'primary'){
							array_unshift($images, $imageUrl);
						} else {
							$images[] = $imageUrl;
						}
					}					
				}
			} else {
				$imageUrl = getImageURL($imageSets);
				$imgCat = 'variant';
				if(isset($imageSets['@attributes']['Category'])){
					$imgCat = trim($imageSets['@attributes']['Category']);
				}
				if(strlen($imageUrl) > 0){
					if($imgCat == 'primary'){
						array_unshift($images, $imageUrl);
					} else {
						$images[] = $imageUrl;
					}
				}				
			}
		}

		// TODO: Add validation using laravel validator: Rohit
		$productObject = new Product(array("title" => $title, "description" => $description, "feature1" => $feature1, "feature2" => $feature2, "feature3" => $feature3, "feature4" => $feature4, "feature5" => $feature5, "brand" => $brand, "product_type" => $product_type, "raw_data" => json_encode($item)));
		$currUser->products()->save($productObject);		

		$variantObject = new ProductVariant(array("sku" => $asin, "asin" => $asin, "barcode" => $upc, "price" => $price, "saleprice" => $saleprice, "condition" => $condition,"amazonofferlistingid" => $offerlistingId, "weight" => $weight, "weight_unit" => "lb", "detail_page_url" => $detailPageURL, "user_id" => $currUser->id));
		$productObject->variants()->save($variantObject);

		foreach ($images as $imageUrl) {
			$productImageObject = new ProductImage(array("asin" => $asin, "imgurl" => $imageUrl));
			$variantObject->images()->save($productImageObject);
		}
		insertToShopify($productObject);
		return true;	
	}

	 function getImageURL($imageSetObj){
		$imageUrl = "";
		if(isset($imageSetObj['HiResImage']['URL'])){
			$imageUrl = $imageSetObj['HiResImage']['URL'];
		} else if(isset($imageSetObj['LargeImage']['URL'])){
			$imageUrl = $imageSetObj['LargeImage']['URL'];
		} else if(isset($imageSetObj['MediumImage']['URL'])){
			$imageUrl = $imageSetObj['MediumImage']['URL'];
		} else if(isset($imageSetObj['TinyImage']['URL'])){
			$imageUrl = $imageSetObj['TinyImage']['URL'];
		} else if(isset($imageSetObj['SmallImage']['URL'])){
			$imageUrl = $imageSetObj['SmallImage']['URL'];
		} else if(isset($imageSetObj['ThumbnailImage']['URL'])){
			$imageUrl = $imageSetObj['ThumbnailImage']['URL'];
		} else if(isset($imageSetObj['SwatchImage']['URL'])){
			$imageUrl = $imageSetObj['SwatchImage']['URL'];
		}
		return $imageUrl;
	}

	 function insertItemWithVariants($item) 
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
		$option1name = "";
		$option2name = "";
		$option3name = "";
		$variantsArr = array();
		$detailPageURL = "";

		// For products table
		if(isset($item['ItemAttributes'])){
			$itemAttributes	= $item['ItemAttributes'];
			if(isset($itemAttributes['Title'])){
				$title = trim($itemAttributes['Title']);
			}			
			if(isset($itemAttributes['Brand'])){
				$brand = $itemAttributes['Brand'];
			}
			if(isset($itemAttributes['ProductGroup'])){
				$product_type = $itemAttributes['ProductGroup'];
			}
		}
		if(isset($item['DetailPageURL'])){
			$detailPageURL = $item['DetailPageURL'];
		}
		if(isset($item['Variations'])){
			$variations = $item['Variations'];
			$totalVariations = 0;
			if(isset($variations['TotalVariations'])){
				$totalVariations = $variations['TotalVariations'];	
			}
			if($totalVariations < 1){
				return array("status" => false, "msg" => "Product - ".$title." - no active variant found.");			
			} else if($totalVariations > 100){
				return array("status" => false, "msg" => "Product - ".$title." - having more than 100 variants.");			
			}
			if(isset($variations['VariationDimensions']['VariationDimension'])){
				$variationDimensions = $variations['VariationDimensions']['VariationDimension'];
				if(is_array($variationDimensions) && count($variationDimensions) > 3){
					return array("status" => false, "msg" => "Product - ".$title." - having more than three variant options.");
				}
				if(is_array($variationDimensions)){
					if(isset($variations['VariationDimensions']['VariationDimension'][0])){
						$option1name = $variations['VariationDimensions']['VariationDimension'][0];
					}
					if(isset($variations['VariationDimensions']['VariationDimension'][1])){
						$option2name = $variations['VariationDimensions']['VariationDimension'][1];
					}
					if(isset($variations['VariationDimensions']['VariationDimension'][2])){
						$option3name = $variations['VariationDimensions']['VariationDimension'][2];
					}
				} else {
					$option1name = $variationDimensions;
				}
			}

			$variationItems = $variations['Item'];
			$isFirstVariant = true;
			if($currUser->id == 279 && isset($variationItems[0]['ItemAttributes']['Title'])){
				$title = trim($variationItems[0]['ItemAttributes']['Title']);
			}
			foreach($variationItems as $variationItem){
				$tempVariant = array();
				$tempVariant['option1val'] = "";
				$tempVariant['option2val'] = "";
				$tempVariant['option3val'] = "";
				if(isset($variationItem['ItemAttributes'])) {
					$vItemAttributes = $variationItem['ItemAttributes'];
					if($isFirstVariant){
						$isFirstVariant = false;
						if(isset($vItemAttributes['Feature'])){
							$features = $vItemAttributes['Feature'];
							if(isset($features[0])) {
								$feature1 = $features[0];
							}
							if(isset($features[1])) {
								$feature2 = $features[1];
							}
							if(isset($features[2])) {
								$feature3 = $features[2];
							}
							if(isset($features[3])) {
								$feature4 = $features[3];
							}
							if(isset($features[4])) {
								$feature5 = $features[4];
							}
						}
						if(isset($variationItem['EditorialReviews']['EditorialReview']['Content'])) {
							$description = $variationItem['EditorialReviews']['EditorialReview']['Content'];
						}
						if(isset($variationItem['ParentASIN'])){
							$parentasin = $variationItem['ParentASIN'];
						}
					}
					$tempVariant['upc'] = "";
					if(isset($vItemAttributes['UPC'])){
						$tempVariant['upc'] = $vItemAttributes['UPC'];
					}
					$tempVariant['weight'] = 0;
					if(isset($vItemAttributes['PackageDimensions']['Weight'])){
						$tempVariant['weight'] = $vItemAttributes['PackageDimensions']['Weight'];
						$tempVariant['weight'] = $tempVariant['weight']/100;
					}
				}
								
				$tempVariant['asin'] = "";
				if(isset($variationItem['ASIN'])){
					$tempVariant['asin'] = $variationItem['ASIN'];
				}
			
				// Fetch offer details
				$offerlistingId = "";
				$price = "";
				$saleprice = "";
				$condition = "";
		
				if(isset($variationItem['Offers'])){
					$offers = $variationItem['Offers'];
					$totalOffers = isset($offers['TotalOffers'])?$offers['TotalOffers']:0;
					$offer = $offers['Offer'];
					if($totalOffers > 0) {
						$offer = $offers['Offer'][0];
					}
					if(isset($offer['OfferAttributes']['Condition'])){
						$tempVariant['condition'] = $offer['OfferAttributes']['Condition'];
					}
					if(isset($offer['OfferListing'])){
						$offerListing = $offer['OfferListing'];
						if(isset($offerListing['OfferListingId'])){
							$tempVariant['offerlistingId'] = $offerListing['OfferListingId'];
						}
						if(isset($offerListing['Price']['Amount'])){
							$tempVariant['price'] = number_format($offerListing['Price']['Amount']/100, 2, ".", "");
						}
						if(isset($offerListing['SalePrice']['Amount'])){
							$tempVariant['saleprice'] = number_format($offerListing['SalePrice']['Amount']/100, 2, ".", "");
						} else {
							$tempVariant['saleprice'] = $tempVariant['price'];
						}
					}
				}

				if(isset($variationItem['VariationAttributes']['VariationAttribute'])){
					if(isset($variationItem['VariationAttributes']['VariationAttribute'][0]['Name'])){
						foreach($variationItem['VariationAttributes']['VariationAttribute'] as $variationAttributeObj){
							if(isset($variationAttributeObj['Name']) && $variationAttributeObj['Name'] == $option1name){
								$tempVariant['option1val'] = $variationAttributeObj['Value'];
							}
							if(isset($variationAttributeObj['Name']) && $variationAttributeObj['Name'] == $option2name){
								$tempVariant['option2val'] = $variationAttributeObj['Value'];
							}
							if(isset($variationAttributeObj['Name']) && $variationAttributeObj['Name'] == $option3name){
								$tempVariant['option3val'] = $variationAttributeObj['Value'];
							}
						}
					} else {
						if(isset($variationItem['VariationAttributes']['VariationAttribute']['Name']) && $variationItem['VariationAttributes']['VariationAttribute']['Name'] == $option1name){
								$tempVariant['option1val'] = $variationItem['VariationAttributes']['VariationAttribute']['Value'];
							}
					}
				}
				// fetch Images
				$tempVariant['images'] = array();				
				if(isset($variationItem['ImageSets']['ImageSet'])){
					$imageSets = $variationItem['ImageSets']['ImageSet'];
					if(is_array($imageSets) && isset($imageSets[0]['SmallImage'])){
						foreach($imageSets as $imageSet){
							$imageUrl = getImageURL($imageSet);
							$imgCat = 'variant';
							if(isset($imageSet['@attributes']['Category'])){
								$imgCat = trim($imageSet['@attributes']['Category']);
							}
							if(strlen($imageUrl) > 0){
								if($imgCat == 'primary'){
									array_unshift($tempVariant['images'], $imageUrl);
								} else {
									$tempVariant['images'][] = $imageUrl;
								}
							}
						}
					} else {
						$imageUrl = getImageURL($imageSets);
						$imgCat = 'variant';
						if(isset($imageSets['@attributes']['Category'])){
							$imgCat = trim($imageSets['@attributes']['Category']);
						}
						if(strlen($imageUrl) > 0){
							if($imgCat == 'primary'){
								array_unshift($tempVariant['images'], $imageUrl);
							} else {
								$tempVariant['images'][] = $imageUrl;
							}
						}						
					}
				}				
				$tempVariant['images'] = array_unique($tempVariant['images']);				
				$variantsArr[] = $tempVariant;
			}
		}

		// TODO: Add validation using laravel validator: Rohit
		$productObject = new Product(array("title" => $title, "description" => $description, "feature1" => $feature1, "feature2" => $feature2, "feature3" => $feature3, "feature4" => $feature4, "feature5" => $feature5, "option1name" => $option1name, "option2name" => $option2name, "option3name" => $option3name, "brand" => $brand, "product_type" => $product_type, "parentasin" => $parentasin, "raw_data" => json_encode($item)));
		$currUser->products()->save($productObject);		
		foreach($variantsArr as $v){
			$variantObject = new ProductVariant(array("sku" => $v['asin'], "asin" => $v['asin'], "option1val" => $v['option1val'], "option2val" => $v['option2val'], "option3val" => $v['option3val'], "price" => $v['price'], "saleprice" => $v['saleprice'], "condition" => $v['condition'], "amazonofferlistingid" => $v['offerlistingId'], "weight" => $v['weight'], "weight_unit" => "lb", "detail_page_url" => $detailPageURL, "user_id" => $currUser->id));
			$productObject->variants()->save($variantObject);
	
			foreach ($v['images'] as $imageUrl) {
				$productImageObject = new ProductImage(array("asin" => $v['asin'], "imgurl" => $imageUrl));
				$variantObject->images()->save($productImageObject);
			}
		}
		insertToShopify($productObject);
		return array("status" => true, "msg" => "success");
	}

	 function createmany($parentasin)
    {
    	$temp = explode(",", $parentasin);
		$count = count($temp);
		if($count == 0){
			return response()->json(['error' => ["msg"=>["Please choose atleast one product."]]], 406);
		} else if($count > 10){
			return response()->json(['error' => ["msg"=>["Please choose less than 10 products."]]], 406);
		}

    	$currUser = Auth::User();
		$amzKey = $currUser->amzKey()->first();
		if(!$amzKey){
			return response()->json(['error' => ["msg"=>['Amazon AWS keys are required for this operation.']]], 406);
		}
		foreach($temp as $t){
			$pcount = $currUser->products()->where('parentasin', $t)->count();			
			if($pcount != 0) {
				return response()->json(['error' => ["msg"=>['Product already exist.']]], 406);
				break;
			}	
		}
		
		try {
			$conf = new GenericConfiguration();
			$client = new \GuzzleHttp\Client();
			$request1 = new \ApaiIO\Request\GuzzleRequest($client);
			$conf
			->setCountry($amzKey->country)
			->setAccessKey($amzKey->aws_access_id)
			->setSecretKey($amzKey->aws_secret_key)
			->setAssociateTag($amzKey->associate_id)
			->setRequest($request1)
			->setResponseTransformer(new \ApaiIO\ResponseTransformer\XmlToArray());

			$app = new ApaiIO($conf);
			$lookup = new Lookup();
			$lookup->setItemId($temp);
			$lookup->setResponseGroup(array('Large', 'Small', 'Variations'));
	
			$response = $app->runOperation($lookup);
			addlog($response,"INFO");
			/*if(isset($response['Items']['Item']['Variations']['Item'][0]['ASIN']))
			{
				$asin = $response['Items']['Item']['Variations']['Item'][0]['ASIN'];
				$product_variant = ProductVariant::where('asin', $asin)->get();
				$length = count($product_variant);
				if($length != 0)
				{
					return response()->json(['error' => ["msg"=>['Product Already Exist.']]], 406);
				}
			}*/			
			if(isset($response['Items']['Request']['Errors']['Error']))
			{
				return response()->json(['error' => ["msg" => ['There was some error processing the request. Please contact customer support.']]], 406);
			}
			if(!isset($response['Items']['Item'])){
				return response()->json(['error' => ["msg" => ['There was some error processing the request. Please contact customer support.']]], 406);
			}

			$items = $response['Items'];
			if(isset($items['Item'][0]['ASIN'])){
				$errmsg = '';
				foreach($items['Item'] as $item){
					$res = insertItemWithVariants($item);
					if(!$res['status']){
						$errmsg = $errmsg.$res['msg'].'<br />';
					}
				}
				if($errmsg != ''){
					return response()->json(['error' => ["msg"=>[$errmsg]]], 406);
				} else {
					return response()->json(['success'], 200);
				}
			} else if(isset($items['Item']['ASIN'])) {
				$res = insertItemWithVariants($items['Item']);
				if($res['status']){
					return response()->json(['success'], 200);
				} else {
					return response()->json(['error' => ["msg"=>[$res['msg']]]], 406);
				}
			} else {
				return response()->json(['error' => ["msg"=>['There was some error processing the request. Please contact customer support.']]], 406);
			}
		} catch (\Exception $e) {
        	return response()->json(['error' => ["msg"=>['There were some error processing this request. Please try again.']]], 406);
		}
	}
	


	function fetchProductDataWithRetry($url){
		$temp = fetchProductData($url);
        if($temp == "ERROR" || strpos($temp,"Error") || $temp == []) {
	        sleep(2);
            $temp = fetchProductData($url);
            if($temp == "ERROR" || strpos($temp,"Error")){
		        sleep(4);
                $temp = fetchProductData($url);
			    if($temp == "ERROR" || strpos($temp,"Error")){
                                // sleep(7);
                                    //  $failedAsin = $asin; /*getDataFromDoc(fetchProductData($url))*/;
                                      return $temp;
                                                                                                             }
                                    else{
                                       $data[$asin] = $temp;
                                        }
                                                                                                         }
                                   else{
                                       $data[$asin] = $temp;
                                       }
        }
       else{
                                    $data[$asin] = $temp;
            }
         return  $temp;

    }

    function GenCode($size=6){
		global $conn;
		$code = '';
		$validchars = 'abcdefghijkmnopqrstuvwxyz23456789';
		mt_srand ((double) microtime() * 1000000);
		for ($i = 0; $i < $size; $i++) {
			$index = mt_rand(0, strlen($validchars));
			$code .= $validchars[$index];
		}
		return $code;
	}





	function fetchProductData($url){
        sleep(1);
        global $conn;
        $randomCode = GenCode(6);
        $url =  str_replace("\r", '', $url);
        $proxy_port = "22225";//60099";lum-customer-hl_c27ea444-zone-static
        $proxy_ip = "zproxy.lum-superproxy.io";//172.84.122.33";lum-customer-hl_c27ea444-zone-static
        $loginpassw = "lum-customer-hl_c27ea444-zone-static-session-".$randomCode.":0w29dqxs53i7";

        $user_agentObj = $conn->query("SELECT * FROM `user_agents` WHERE id=".rand(0,220));
        if($user_agentObj->num_rows > 0){
            $user_agents = $user_agentObj->fetch_assoc();
            $user_agent = $user_agents['ua_string'];
        }else{
            $user_agent = "Mozilla/6.0 (Macintosh; I; Intel Mac OS X 11_7_9; de-LI; rv:1.9b4) Gecko/2012010317 Firefox/10.0a4";
        }

    	$ch = curl_init();
    	
    	
    	
    	 curl_setopt( $ch, CURLOPT_USERAGENT,$user_agent );
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
            curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTPS');
            curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                //"Postman-Token: 47dd397c-06d7-461f-9873-b317e948d580",
                "cache-control: no-cache",
                "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
                "Connection: close",
                "X-Forwarded-For: ".$proxy_ip,
                "Content-Length: 0",
                "Cookie: timezone=Asia/Kolkata;",
                "Accept-Language: en-US,en;q=0.9",
                "Accept-Encoding: gzip, deflate, br",
                //"Host: www.amazon.com",
              ));
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt( $ch, CURLOPT_ENCODING, "UTF8" );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
            curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
            curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    	    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            //addlog("http code is ".json_encode(curl_getinfo($ch)),"INFO");
    	

      
        $response = curl_exec($ch);
	    $err = curl_error($ch);
	    curl_close($ch);
       // addlog("data crawled via luminiato ".json_encode($response),"INFO");
        if ($err) {
         addlog("data crawled via luminiato erroe ".$err,"INFO");    
	      return "ERROR";
	    } else {
	      return $response;
	    }
    }


	function addlog($message, $type){
		global $logfile;
		$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
		fwrite($logfile, $txt);
	}
    
?>
