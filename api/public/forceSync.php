<?php
 header('Access-Control-Allow-Origin: *');
	set_time_limit(0);
	//require_once("includes/config.php");
	$logfile = fopen("logs/forceSync.txt", "a+") or die("Unable to open log file!");
	addlog("Execution Started", "INFO");
	addlog("Execution data", $argv[0]." ".$argv[1]." ".$argv[2]);
    //addlog("Execution data", $argv[0]." ".$argv[1]." ".$argv[2]);
	$conn = new mysqli('localhost', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
    $product_id = $argv[1];
    $user_id = $argv[2];
	// Check connection
	if ($conn->connect_error) {
		addlog("Database connection failed: " . $conn->connect_error, "ERROR");
		die("Database Connection Failed");
	}

	mysqli_set_charset($conn, "utf8");

	$new_products = $conn->query("SELECT*FROM `products` WHERE `product_id` = '$product_id' AND `user_id`='$user_id'");
	if($new_products->num_rows > 0){
		while($row = $new_products->fetch_assoc()){
		    addlog(json_encode($row),"INFO");
		    $user_id = $row['user_id'];
		    $product_id = $row['product_id'];
		    //addlog("Execution data", $argv[0]." ".$argv[1]);
		    $productQuery = "select * from products where product_id = ".$product_id."  and duplicate = 0 and block = 0 and user_id = ".$user_id;
			//echo $productQuery;
				$productResult = $conn->query($productQuery);
				if ($productResult->num_rows > 0) {
					while($productRow = $productResult->fetch_assoc()) {
					    addlog(json_encode($productRow),"INFO productRow");
					    $varinatQuery = "SELECT * FROM `product_variants` WHERE `product_id`='$product_id'";
					    addlog($varinatQuery,"varinat Query");
					    $varinatObject = $conn->query($varinatQuery); 
					    if($varinatObject->num_rows > 0){
    					    while($variantRow = $varinatObject->fetch_assoc()){
    					        addlog("Calling add Product By Crawl","INFO");
    					        addlog($variantRow['asin'],"variantdata");
    					        $price = addProductByCrawl($variantRow['asin'],$user_id);
    					        if($price != null){
    					            addlog(json_encode($price),"Prices");
    					            insertToShopify($user_id,$price,$productRow,$conn);
    					        }else{
    					            addlog("Null Values Returned From addProductByCrawl","ERROR");
    					            echo "Error";
    					        }
    					    }
					    }else{
					        addlog(json_encode($productObject),"Error");
					        addlog(json_encode($conn),"ERROR");
					    }
					}
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
  	function getSettings($user_id,$conn) {
  	    addlog("GetSetting","INFO");
       $settingsResult = $conn->query("select * from setting where user_id = ".$user_id);
		if($settingsResult->num_rows > 0){
			while($settingsRow = $settingsResult->fetch_assoc()) {
				return $settingsRow;
							
			}
		}
	}			
 	function insertToShopify($user_id,$priceObject,$productObject,$conn){
 	    addlog(json_decode($priceObject),"Updated Price");
		addlog("insert to shopify: " . $user_id, "ERROR");
		$currUser = getUser($user_id,$conn);
		//echo(json_encode($currUser));
		$settingObject = getSettings($user_id,$conn);
		addlog("GetSetting Acessed","INFO");
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
		if($settingObject){
	        
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
	        addlog("Finding Variant","INFO");
		
		        $variantResult = $conn->query("select * from `product_variants` where `product_id` = '".$product_id."' and user_id = ".$user_id." and block = 0 and duplicate = 0");
				$noOfVariants = $variantResult->num_rows;
				//print_r($variantResult->fetch_assoc()); 
				//echo $noOfVariants;
			addlog($noOfVariants,"INFO");
				
				
				
		$variantsArr = $variantResult; //$productObject->variants();
		$vCount = $variantResult->num_rows;
		if($vCount == 1){
		    //addlog("count is  1<br/>","INFO");
			$variantObject = $variantResult->fetch_assoc();
			addlog(json_encode($variantObject),"INFO");
			$shopifyproductid = $variantObject["shopifyproductid"];
		    $shopifyvariantid = $variantObject["shopifyvariantid"];
			$shopifyinventoryid = $variantObject["shopifyinventoryid"];
			
			$sku = $variantObject['sku'];
			$productid = $variantObject['product_id'];
			$price = $variantObject['price'];
			$saleprice = $variantObject['saleprice'];
			$variant_id = $variantObject['id'];
			//echo  $variant_id."   pankaj <br/>";
			if($markupenabled == true){
				$price = applyPriceMarkup($priceObject[0], $markuptype, $markupval, $markupround);
				$saleprice = applyPriceMarkup($priceObject[1], $markuptype, $markupval, $markupround);
			}			
			$detail_page_url = $variantObject['detail_page_url'];
			
			//print_r($imagesArr); 
			$data = array(
					"product"=>array(
					    "id" => $shopifyproductid,
						"variants"=>array(
							array(
							    "id" => $shopifyvariantid,
								"sku"=>$sku,
								"price"=>number_format($saleprice, 2, '.', ''),
								/*"compare_at_price"=>number_format($price, 2, '.', ''),*/
								"inventory_policy"=>"deny",
                                "fulfillment_service"=> "manual",
                                "inventory_management"=> $inventory_policy

							)
						)
					)
				);
			$response = updateShopifyProduct($token, $shopurl, $data,$shopifyproductid);
			//addLog(json_encode($data),"INFO");
			//echo($response);
			if($response){	
				echo $shopifyproductid;
				
				if($location_id == ""){
				    $location_id = getLocationId($token, $shopurl, $shopifyinventoryid);
				    if(!$conn->query("UPDATE `setting` SET `shopifylocationid` = '".$location_id."' WHERE `user_id` = '".$user_id."'")){
				       addlog("Error In Udating shopifylocationid in setting ","ERROR"); 
				    }
				}
				
			    //echo $variantObject->save();   
					
				addlog("<br/>Response data saved properly upto line 245<br/>","INFO");
				$rowid = "";
				
				addlog("location_id : ".$location_id,"INFO");
				addlog("Response data parsed properly upto line 247<br/>","INFO");
				addlog("quantity".$defquantity,"INFO");
				if($inventory_policy == "shopify" && $location_id != ""){
				    addlog("<h3>updating shopify inventory</h3><br/>","INFO");
				    addlog("quantity".$defquantity,"INFO");
					updateShopifyInventory($token, $shopurl, $shopifyinventoryid, $location_id, $defquantity,$user_id,$conn,$rowid);
					addlog("<h3>Updation Finished Properly</h3><br/>","INFO");
				}else{
				    addlog($inventory_policy.$location_id,"INFO");
				    addlog("Error in invetory or location at line 250","INFO");
				    //addlog("inventory policy : ".$inventory_policy."<br/>","INFO");
				    //addlog("location id : ".$location_id."<br/>","INFO");
				}
			} else {
			    print_r($response);
                addlog("Error while excepting Response from shopify at line 179","INFO");
			}
		}else {
			// To 
		}
		$skuconsumed = $currUser['skuconsumed'];
		$currUser['skuconsumed'] = $skuconsumed + 1;
		//$currUser->save();
	}
	function updateShopifyInventory($token, $shopurl, $inventory_item_id, $location_id, $quantity,$user_id,$conn,$rowid){
	    addlog("entered to inventory funtion<br/>","INFO");
		$data = array("location_id" => $location_id, "inventory_item_id" => $inventory_item_id, "available" => $quantity);

		$url = "https://".$shopurl."/admin/api/2021-07/inventory_levels/set.json";
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
		addlog("response Generated <br/>","INFO");
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
				$url = "https://".$shopurl."/admin/api/2021-07/inventory_levels/set.json";
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
	function getLocationId($token, $shopurl, $inventory_item_id){
	    addlog("getLocation called","INFO");
		$apiurl = "https://".$shopurl."/admin/api/2021-07/inventory_levels.json?inventory_item_ids=".$inventory_item_id;
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
			$newprice = round($newprice) - .01;
		}
		return $newprice;
	 }

    function updateShopifyProduct($token, $shopurl, $data,$product_id){
		$url = "https://".$shopurl."/admin/api/2021-07/products/$product_id.json";
		//print_r($url);
		//print_r($token);
		//print_r($data);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token, 'Content-Type: application/json; charset=utf-8'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec ($curl);
		curl_close ($curl);
		
		
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
		
		//print_r($header);echo "<br/><br/>";
		//print_r($body);

		$response_arr = explode("\n",$response);

		if( (strstr(($response_arr[0]), "201 Created")) || (strstr(($response_arr[1]), "201 Created")) || (strstr(($response_arr[2]), "201 Created")) ){
			$product_json = end($response_arr);
			$product_arr = json_decode($product_json, true);
			$product_arr = $product_arr["product"];
			return $product_arr;
		} else {
			//print_r($data);
			//print_r($response_arr);
			//addlog("Error adding product with SKU - ".$product["product"]["variants"]["sku"].", Err Details: ".serialize($response_arr), "ERROR");
		}
		return $response;
	}
	function addlog($message, $type){
		global $logfile;
		$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
		fwrite($logfile, $txt);
	}
	
	 function addProductByCrawl($asin,$user_id){
		$conn = new mysqli('localhost', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
        addlog($asin,"ASIN");
		if(!$conn){
			die("connection error");
		}
		addlog("crawling start","INFO");
			$producturl = 'https://www.amazon.com/gp/product/'.$asin;
			//$user_id = 3;
			//$asin = "B07J315R4F";
			/// Checking for existing product ///
			
			$tmpurl = strtok($producturl,'?');
			addlog($tmpurl,"INFO");
			$res = preg_match_all("/dp\/(.*)\/ref/U",$tmpurl."/ref",$matches);
			if($res){
			    addlog("278","INFO");
			    addlog($matches[1][0],"INFO");
			    $res = preg_match_all("/\/*([A-Z0-9]*)\/*ref/s",$tmpurl."/ref",$matches);
			    addlog("289","INFO");
			    if($res){
			        addlog("292","INFO");
			        addlog($matches,"INFO");
			        foreach($matches[1] as $key => $value){
			            if(strlen($value)>7){
			                $permission = ProductVariant::where("user_id",$currUser->id)->where("asin",$matches[1][0])->get();
            			    if(sizeof($permission)>0){
            				    return "Product Already Exists";
            			    }else{
            			        addlog($permission,"INFO");
            			    }
			            }
			            //Log::info($value);
			        }
			        //Log::info($matches);
			    }
			}else{
			    $res = preg_match_all("/\/([A-Z0-9]*)\/ref/sU",$tmpurl."/ref",$matches);
			    if($res){
			        $permission = mysqli_query($conn,"SELECT * FROM `product_variants` WHERE `user_id` = '".$user_id."' AND `asin` = '".$asin."'");
            		if($perm = mysqli_fetch_array($permission)){
            			addlog("Product Already Exists","INFO");
            	    }
			    }
			    addlog("ASIN Not Found in database downloading product ...","INFO");   
			}
			$domainVerification = verifyAmazonDomain($producturl);
			if(!$domainVerification){
				addlog("Domain Variation error","INFO");
				return response()->json(['error' => ["msg"=>["Please enter a valid product URL."]]], 406);
			}
    		addlog("starting surrent user","INFO");
	    	$domain = parse_url($producturl, PHP_URL_HOST);
			if($domain == "amazon.com" || $domain == "www.amazon.com"){
				//$time_start =microtime_float();
				addlog($producturl,"INFO");
				$data = null;//get_html_proxy_content( $producturl );
				if($data!= null){
					$res1 = $data;
		    	    $resObj = json_decode($data, true);
		    	    //$time_end = microtime_float();
		            //$time = $time_end - $time_start;
		            //addlog("Did crawling in $time seconds\n");            
				    addlog($resObj,"INFO");
					if(isset($resObj['Title'])){	
						$results = $resObj['results'][0];
    					$title = "";
    					$description = "";
    					$brand = "";
    					$product_type = "";
    					$asin = "";
    					$url = "";
    					$price = "";
    					$list_price = "";
    					$images = "";
    					if(isset($results['price'])) {
    						$price = $results['price'];
    						$price = getAmount($price);
    					}
    					if(isset($results['list_price'])) {
    						$list_price = $results['list_price'];
    						$list_price = getAmount($list_price);
    					} else {
    						$list_price = $price;
    					}
    					
    					$updateQuery = "UPDATE `product_variants` SET `price`='$price',`saleprice`= '$list_price' WHERE user_id='$user_id' AND asin= '$asin'";
                        addlog($updateQuery,"UPDATE QUERY");
   	                    if(!$conn->query($updateQuery)){
   	                        addlog(json_encode($conn),"PRICE UPDATE ERROR");
   	                     }
    					
    					return [$price,$list_price];
					
					}//if of results validation
					else {
						@mail("pankajnarang81@gmail.com", "ProductController: Proxy Crawel code Failed", "ProductController: Proxy crawel code failed");
						return null;
					}
				}else{
					addlog("Proxy Failed","INFO");
					addlog($producturl,"INFO");
					$data = get_html_scraper_api_content($producturl);
					addlog("data parsing 468","Info");
					$res1 = getjsonrdata($data,$producturl);
					$resObj = json_decode($res1, true);
				    if(isset($resObj['Title'])){	
						$results = $resObj;
						addlog(json_encode($resObj),"Title");
    					$title = "";
    					$description = "";
    					$brand = "";
    					$product_type = "";
    					$asin = "";
    					$url = "";
    					$price = "";
    					$list_price = "";
    					$images = "";
    					addlog($resObj->Title,"INFO Parse Results");
    					if(isset($results['title'])) {
    						$title = $results['title'];
    					}
    					if(isset($results['description'])) {
    						$description = $results['description'];
    					}
    					if(isset($results['brand'])) {
    						$brand = $results['brand'];
    					}
    					if(isset($results['category'])) {
    						$product_type = $results['category'];
    					}
    					if(isset($results['asin'])) {
    						$asin = $results['asin'];
    					}
    					if(isset($results['url'])) {
    						$url = $results['url'];
    					}
    					if(isset($results['price'])) {
    						$price = $results['price'];
    						$price = getAmount($price);
    					}
    					if(isset($results['list_price'])) {
    						$list_price = $results['list_price'];
    						$list_price = getAmount($list_price);
    					} else {
    						$list_price = $price;
    					}
    					
    					$updateQuery = "UPDATE `product_variants` SET `price`='$price',`saleprice`= '$list_price' WHERE user_id='$user_id' AND asin= '$asin'";
                        addlog($updateQuery,"UPDATE QUERY");
   	                    if(!$conn->query($updateQuery)){
   	                        addlog(json_encode($conn),"PRICE UPDATE ERROR");
   	                     }
    					
    					
    					return [$price,$list_price];
					}//if of results validation
					else {
						@mail("pankajnarang81@gmail.com", "ProductController: Scrapper Call Failed", "ProductController: Proxy crawel code failed");
						return null;
					}	
				}
			}else {
				// if closed
				$url = "https://search.promptcloud.com/api/v1/live_crawl/infoshore_software__limited?id=infos_f1d3ce8538&version=v2&site_name=amazon_com_amz_set2_infoshore_software__limited&url=".urlencode($producturl);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);		
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$res = curl_exec($ch);				
				curl_close($ch);
				$resObj = json_decode($res, true);
				if(isset($resObj['results'][0])){	
					$results = $resObj['results'][0];
					$title = "";
					$description = "";
					$brand = "";
					$product_type = "";
					$asin = "";
					$url = "";
					$price = "";
					$list_price = "";
					$images = "";
					if(isset($results['title'])) {
						$title = $results['title'];
					}
					if(isset($results['description'])) {
						$description = $results['description'];
					}
					if(isset($results['brand'])) {
						$brand = $results['brand'];
					}
					if(isset($results['category'])) {
						$product_type = $results['category'];
					}
					if(isset($results['asin'])) {
						$asin = $results['asin'];
					}
					if(isset($results['url'])) {
						$url = $results['url'];
					}
					if(isset($results['price'])) {
						$price = $results['price'];
						$price = getAmount($price);
					}
					if(isset($results['list_price'])) {
						$list_price = $results['list_price'];
						$list_price = getAmount($list_price);
					} else {
						$list_price = $price;
					}
					
					
					$updateQuery = "UPDATE `product_variants` SET `price`='$price',`saleprice`= '$list_price' WHERE user_id='$user_id' AND asin= '$asin'";
                        addlog($updateQuery,"UPDATE QUERY");
   	                    if(!$conn->query($updateQuery)){
   	                        addlog(json_encode($conn),"PRICE UPDATE ERROR");
   	                     }
					
					
                    return [$price,$list_price];
				}else {
					return null;
				}
			}//else closed	
			//return response()->json(['success'], 200);
	} 
	
	 function get_html_scraper_api_content($url) {
		addlog("start","INFO");        
		//$url= strtok($url, '?');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com/?key=bccfd6a1043eeef4b878ab667efac22b&url=".urlencode($url));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  "Accept: application/json"
		));

		$response = curl_exec($ch);
		curl_close($ch);
		addlog("closed","INFO");
		return $response;
	}

	
	 function getjsonrdata($data,$producturl) {
		$doc = new \DOMDocument();
	    $doc->recover = true;
	    $errors = libxml_get_errors();
	    $saved = libxml_use_internal_errors(true);
	    $doc->loadHTML($data);
	    $handle = fopen('Product.html', 'wr');
	    fwrite($handle, $data);
		$xp = new \DOMXPath($doc);
	    $dataArr = array();
	    $dataArr['url'] = $producturl;
		$titleBlock = $doc->getElementById('title');
    	//$dataArr['Title'] = $xp->evaluate('string(.//*[@class="a-size-extra-large"])', $titleBlock);
	    $dataArr['Title'] = trim( $xp->evaluate('string(.//*[@class="a-size-large"])', $titleBlock) );
	   		
	   	if(strlen($dataArr['Title'])==0){addlog("No Data Available <br/>","INFO");return null;}

	    $productDescriptionBlock = $doc->getElementById('productDescription');
	    $dataArr['description'] =  trim(  $xp->evaluate('string(.//p)', $productDescriptionBlock) );
	   
	    $asinBlock = $doc->getElementById('productDetails_detailBullets_sections1'); //->childNodes;
	    $dataArr['asin'] =  $xp->evaluate('(string(.//tr[3]/td))', $asinBlock);
	    $asinBlock  =     $doc->getElementById('ASIN');
	    if($asinBlock != null){
	      $dataArr['asin'] =  $asinBlock->getAttribute('value');
	    }
	  
	    $values =  $dataArr['asin'];
	    $featurebullets = $doc->getElementById('feature-bullets');
	    $count = $xp->evaluate('count(.//ul//li)',$featurebullets);
	    $bulletData = array();
	   
	    for($x=0; $x <= $count - 1 ;$x++){
		   $bdata =  $xp->evaluate('(.//ul//li)',$featurebullets )->item($x)->nodeValue ;
		   $str = str_replace(array("\r\n", "\r", "\n", "\t"), '', $bdata);
		   array_push($bulletData,$str);
	    }
	   
	    $dataArr['bullet_points'] = $bulletData;
	   
	   
	   $salepricediv = $xp->evaluate('string(//span[contains(@id,"ourprice") or contains(@id,"saleprice") or contains(@id,"priceblock_ourprice") or contains(@id,"buyNew_noncbb") or contains(@id,"priceblock_dealprice")]/text())',$doc);
	   
	   	if(strpos($salepricediv, '-') !== false){
	   	  $pricediv = explode("-", $salepricediv);
	   	  addlog($pricediv,"INFO");
	   	  $salepricediv = trim($pricediv[0]);
	   	}
	    addlog($salepricediv,"INFO");
	    if($salepricediv == ""){
	   	 $salepricediv =$xp->evaluate('string(//div[@id="cerberus-data-metrics"]//@data-asin-price)',$doc);
	   	  if(strpos($salepricediv, '-') !== false){
		   	  $pricediv = explode("-", $salepricediv);
		   	  addlog($pricediv,"INFO");
		   	  $salepricediv = trim($pricediv[0]);
	   	  }
	    }
	    $dataArr['price'] = $salepricediv;
	   
	    $originalpricediv = $xp->evaluate('//td[contains(text(),"List Price") or contains(text(),"M.R.P") or contains(text(),"Price")]/following-sibling::td/text()', $doc);
	   
	    $dataArr['list_price'] = $salepricediv;
	    //vendorPoweredCoupon_feature_div
	   
	    $categoryDiv = $xp->evaluate('string(//a[@class="a-link-normal a-color-tertiary"]//text())',$doc);
	  
	    $dataArr['category'] = $categoryDiv;
	   
	   //$availabilityDiv = $xp->evaluate('string(//div[@id="availability"])',$doc);
	   
	    $brandDiv = $xp->evaluate('string(//a[@id="bylineInfo"]//text())',$doc);
	    $dataArr['brand'] = $brandDiv;
	   
	    $imageArr = array();
	    $imagepipe = "";
	    $scriptBlock = $doc->getElementsByTagName('script');
	    foreach ($scriptBlock as $key => $value) {
	        $res = preg_match_all('/P\.when\(\'A\'\)\.register\("ImageBlockATF", function\(A\){(.*)}/s', $doc->saveHTML($value), $matches);
	        if($res){
	            $file =$doc->saveHTML($value);
	            $res1 = preg_match_all('/"hiRes":"(.*)"/U', $file, $match);
	            if($res1){
	                $imageArr= $match[1];
	                        //$imagepipe = $imagepipe."|".$match[1];
	            }
	        }
	    }
	    
	    foreach ($imageArr as $image) {
	     	$imagepipe = $imagepipe."|".$image;
	    }
	    
	   
	    $dataArr['high_resolution_image_urls'] =  $imagepipe;  //$imageArr[0]."|".$imageArr[1]."|".$imageArr[2]."|".$imageArr[3]."|".$imageArr[4]."|".$imageArr[5]."|".$imageArr[6]."|".$imageArr[7]."|".$imageArr[8]."|".$imageArr[9];
	    
	    
	    if( !isset( $dataArr['Title'] ) || !isset( $dataArr['description'] ) || !isset( $dataArr['asin'] ) || !isset( $dataArr['bullet_points'] ) || !isset( $dataArr['price'] ) || !isset( $dataArr['list_price'] )  || !isset( $dataArr['category'] ) || !isset( $dataArr['brand'] ) || !isset( $dataArr['high_resolution_image_urls'] )       ){
	        return json_encode(array());
	    }else{
	        return json_encode($dataArr);
	    }
	}
	//https://www.amazon.com/gp/product/B07C5992QM
	  function get_html_proxy_content($url) {
	    //return null;
	    //return null;
		$start_proxy = 0;$first = 0;
		while($first!=2){
			$conn = new mysqli('localhost', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
			if(!$conn){
				die("Database connection Error");
			}

			$lastUsedProxy = $conn->query("SELECT * FROM `proxy` WHERE `flag`=1");
			addlog("777 Last Used Proxy : ","INFO");//print_r($lastUsedProxy);
			
			$currentused = 0;
			if($data = mysqli_fetch_array($lastUsedProxy)){
				
				$currentProxy = $conn->query("SELECT * FROM `proxy` WHERE `id`>".$data['id']." AND `flag`> '-1'");
				//print_r($lastUsedProxy);
				/*if($lastUsedProxy->id ==10){
					$currentused = 1;
				}else{*/
				if($lastUsedProxy = $lastUsedProxy->fetch_assoc()){
					$currentused = ($lastUsedProxy['id']) + 1;
				}
				$currentProxy = $currentProxy->fetch_assoc();
				//}
			  //$currentProxy = Proxy::where('id', '>', $lastUsedProxy->id)->where('flag',0)->first();
			  if(!isset($currentProxy)){
			      $currProxyObj = $conn->query("SELECT * FROM `proxy` WHERE `flag` = '0'");
			      if($currProxyObj->num_rows > 0){
			          $row = $currProxyObj->fetch_assoc();
			          $currentProxy = $row;
			      }
			  }
			}else{
			    $currProxyObj = $conn->query("SELECT * FROM `proxy` WHERE `flag` = '0'");
			      if($currProxyObj->num_rows > 0){
			          $row = $currProxyObj->fetch_assoc();
			          $currentProxy = $row;
			      }
			    addlog("CurrentProxy :","INFO");
			}

			if(!isset($currentProxy)){
				addlog("No Active proxy Available","INFO");
				return null;
			}

			if($first == 0){$start_proxy = $currentProxy;}

			$first = 1;
			 
			//print_r("493current/last/current Proxy :");
			//print_r($currentused);
			//print_r($lastUsedProxy);
			//print_r($currentProxy);
			//print_r("Calling Product Url");
			$PROXY_USER = $currentProxy['username'];   //"pankajnarang81";
			$PROXY_PASS = $currentProxy['password'];     //"6KkuuDVH";
			$PROXY_IP = $currentProxy['url'];
			$PROXY_PORT =$currentProxy['port'];
			$proxyd = $PROXY_IP.':'.$PROXY_PORT;
			$auth = "$PROXY_USER:$PROXY_PASS";
			
			$sFile = getProxyData($PROXY_PORT,$PROXY_IP,$auth,strtok($url, "?"));
			
			if($sFile!=null){
					$d = getjsonrdata($sFile,$url);
					if($d == null){
						$currentProxy->flag=-1;
						$currentProxy->save();
						//@mail("pankajnarang81@gmail.com", "ProductController: Proxy Crawel Blocked", json_encode($currentProxy));
						addlog("Null data recieved Using Proxy","INFO");
						addlog($currentProxy->url,"INFO");
						return null;
					}else{
						$currentProxy->flag=1;
						addlog("Seting Proxy Used Flag","INFO");
				        if(isset($lastUsedProxy)){
						   $lastUsedProxy->flag=0;
						   $lastUsedProxy->save();
						}
						$currentProxy->save();
						addlog("Foramted data","INFO");
						addlog($d,"INFO");
						return $d;
					}
			}else{
					if(!$res = $conn->query('UPDATE `proxy` SET `flag`=-1 WHERE id='.$currentProxy['id'])){
						addlog ("Error in Updating proxy at line 610 ","INFO");
					}

					//@mail("pankajnarang81@gmail.com", "ProductController: Proxy Crawel code Failed", json_encode($currentProxy));
					addlog("Proxy Failed :","INFO");
					//print_r($sFile);
					//print_r($currentProxy);
					return null;
				}
		}
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
			//	Log::info("Proxy Not Responding :");
			//	Log::info($e);
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
		//	Log::info($response);
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

	addlog("Execution Finished", "INFO");
	fclose($logfile);
?>
