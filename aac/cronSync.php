<?php
  
	ini_set('memory_limit','2048M');

	set_time_limit(0);
	
	$conn = new mysqli('localhost', 'root', '', 'infoshoreapps_aac');
	

	$failed = array();
	if($conn){
	
    } else {
	
        die("Database Connection Error");
    }
	
	$result = $conn->query("SELECT u.*, s.inventory_sync, s.price_sync, s.markupenabled, s.markuptype, s.markupval, s.markupround, s.defquantity, s.outofstock_action, s.shopifylocationid, s.inventory_policy FROM users u, setting s WHERE u.id = s.user_id AND u.plan > 2 AND u.installationstatus = 1 AND (s.inventory_sync = 1 OR s.price_sync = 1) AND u.id IN (1)");

	if ($result->num_rows > 0) {		
		while ($row = $result->fetch_assoc()) {  
			$user_id = $row['id'];
		
            $membershiptype = $row['membershiptype']; 
			$shopurl = $row['shopurl']; 
		
			$token = $row['token'];
		
			$id = $row['id'];
			if($row['plan'] == 3){
				$per_user_sync_limit = 2000;
			}else if($row['plan'] == 4){
				$per_user_sync_limit = 10000;
			}
			// if($membershiptype == 'free'){
			// 	continue;
		    // }
			// $location_id = $row['shopifylocationid'];
			// if($location_id == ""){
			// 	$location_id = getMainLocation($id, $shopurl, $token);
			// 	if(!$location_id){
			// 		echo 'no location id found';
			// 		@mail("khariwal.rohit@gmail.com", "AAC:without_aws_keys Location ID missing", $id);
			// 		continue;				
			// 	}
			// 	$row['shopifylocationid'] = $location_id;			
			// }		
			$inventory_sync = $row['inventory_sync'];
		
			$price_sync = $row['price_sync'];
		
		
			$noOfVariantsQuery = $conn->query("select count(*) as cnt from product_variants where user_id = ".$user_id." and deleted = 0");
			$noOfVariantsRow = $noOfVariantsQuery->fetch_assoc();		
			$noOfVariants = $noOfVariantsRow['cnt'];
			
		
		
			if($noOfVariants > $per_user_sync_limit){
			
				continue;
			}
			processUser($user_id, $row, $inventory_sync, $price_sync);
			mysqli_commit($conn);	
	    }
	}
	$conn->query("update crons set lastrun = now(), isrunning = 0 where crontype = 'updateshopifyinventory'"); 

	
    function processUser($user_id, $userRow, $inventory_sync, $price_sync) {
		global $conn;
		$shopurl = $userRow["shopurl"];
		$token = $userRow["token"];
		$settingObject = getSettings($user_id, $conn);
		$inventory_policy = null;
		$defquantity = 1;
		$markupenabled = 0;
		$markuptype = 'FIXED';
		$markupval = 0;
		$markupround = 0;
		$location_id = "";
		if ($settingObject) {
			if ($settingObject['inventory_policy'] != "NO") {
				$inventory_policy = $settingObject['inventory_policy'];
			}
			if (isset($settingObject['defquantity'])) {
				$defquantity = $settingObject['defquantity'];
			}
			if (isset($settingObject['markupenabled']) && $settingObject['markupenabled'] == 1) {
				$markupenabled = true;
			}
			if (isset($settingObject['markuptype']) && strlen($settingObject['markuptype']) > 0) {
				$markuptype = $settingObject['markuptype'];
			}
			if (isset($settingObject['markupval'])) {
				$markupval = $settingObject['markupval'];
			}
			if (isset($settingObject['markupround']) && $settingObject['markupround'] == 1) {
				$markupround = true;
			}
			if (isset($settingObject['shopifylocationid'])) {
				$location_id = $settingObject['shopifylocationid'];
			}
		}
	
		$bulkupdateindb = array();
	
		$variantsResult = $conn->query("SELECT * FROM `product_variants` WHERE user_id = " . $user_id . " and deleted = 0 and shopifyproductid != ''");
		if ($variantsResult->num_rows < 1) {
			return true;
		}
		try {
			while ($variantsRow = $variantsResult->fetch_assoc()) {

				

				$producturl = $variantsRow['detail_page_url'];
				$oldquantity = $variantsRow['quantity'];
				$oldprice = $variantsRow['saleprice'];
				$asin = $variantsRow['asin'];
				$product_id = $variantsRow['product_id'];
				$variant_id = $variantsRow['shopifyvariantid'];
	
				if ($producturl == "") {
					continue;
				}
	
				$res = proxycrawlapi($user_id, $producturl);
				if ($res['status'] != "success") {
					$res = proxycrawlapi($user_id, $producturl);
				}
				if ($res['status'] == "success") {
					$productObj = $res['message'];
					$priceflag = 0;
					$quantityflag = 0;
					$newprice = $productObj["price"];

					$newprice = getAmount($newprice);
					$newquantity = $productObj["quantity"];
					
	
					if ($newprice > 0 && $newprice != $oldprice) {
						$priceflag = 1;
					}
					if ($newquantity != $oldquantity) {
						$quantityflag = 1;
					}
	
					$bulkupdateindb[] = array(
						'product_id' => $product_id,
						'price' => $newprice,
						'quantity' => $newquantity,
						'priceflag' => $priceflag,
						'quantityflag' => $quantityflag
					);
				}
				print_r(json_encode($bulkupdateindb, true));


	
				$sql = "UPDATE product_variants SET price = (CASE ";
				foreach ($bulkupdateindb as $data) {
					$product_id = $data['product_id'];
					$price = $data['price'];
					echo $price;
					$sql .= "WHEN product_id  = $product_id  THEN $price ";
				}
				$sql .= "ELSE price END),
	
					 priceflag=(CASE ";
				foreach ($bulkupdateindb as $data) {
					$product_id = $data['product_id'];
					$priceflag = 0;
					$sql .= "WHEN product_id  = $product_id  THEN $priceflag ";
				}
				$sql .= "ELSE priceflag END) where user_id = $user_id";
				echo $sql;
				if ($conn->query($sql) !== TRUE) {
					print_r(" not updated in db ");
				} else {
					print_r("updated in db ");
	
				}
	
				$jsonrj = "mutation {";
				$i = 0;
				foreach ($bulkupdateindb as $variantObj) {
					$product_id = $variantObj['product_id'];
					
					$price = $variantObj['price'];

					$price = applyPriceMarkup($price, $markuptype, $markupval, $markupround, $user_id);
					$jsonrj .= "
							 updatePriceItem{$i}: productVariantUpdate(input: {
								 id: \"gid://shopify/ProductVariant/{$variant_id}\",
								 price: {$price}
								
							 }) {
								 productVariant {
									 id
									 price
									 compareAtPrice
								 }
								 userErrors {
									 field
									 message
								 }
							 } ";
					$i++;
	
				}
				$jsonrj .= '}';
	
				updateBulkPrice($jsonrj, $shopurl, $token);
	
	      
				$inv = "gid://shopify/InventoryItem/" . $variantsRow['shopifyinventoryid'];
				$qty = (int)$variantsRow['quantity'];
				$shopifylocationid = "gid://shopify/Location/" . $variantsRow['shopifylocationid'];
	
					$inventory[] = array(
						"shopifyinventoryid" => $inv,
						"shopifylocationid" => $shopifylocationid,
						"quantity" => $qty
					);
	
				$jsonStr = json_encode([
					"query" => "mutation inventorySetOnHandQuantities(\$input: InventorySetOnHandQuantitiesInput!) {
									inventorySetOnHandQuantities(input: \$input) {
										userErrors {
											field
											message
										}
										inventoryAdjustmentGroup {
											createdAt
											reason
											referenceDocumentUri
											changes {
												name
												delta
											}
										}
									}
								
								}",
					"variables" => [
						"input" => [
							"reason" => "correction",
							"setQuantities" => $newquantity
						]
					]
	
	
				]);
	
				updatebulkinv($jsonStr, $shopurl, $token);
	
				
				
			}
		} catch (Exception $e) {
	           
		}
	}
	
	
	function updateShopifyVariantPrice($token, $shopurl, $variant_id, $data, $rowid, $user_id){
		global $conn;
		echo 'i am here in price'; 
		print_r($data);
		$url = "https://".$shopurl."/admin/api/2022-01/variants/".$variant_id.".json";
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
		$response_arr = explode("\n",$response);	
		$climit = -1;
		foreach($response_arr as $obj){
			if (strpos($obj, 'X-Shopify-Shop-Api-Call-Limit') !== false) {
				$tempArr = explode(":", $obj);
				$climit = substr(trim(end($tempArr)), 0, -3);
			}
		}	
		if(intval($climit) > 35){
			sleep(5);
		} else {
			sleep(1);
		}
		if( (strstr(($response_arr[0]), "200")) || (strstr(($response_arr[1]), "200")) || (strstr(($response_arr[2]), "200")) ) {
			return true;
		} else if(strstr(($response_arr[0]), "404")){
			$conn->query("update product_variants set quantityflag = 0, priceflag = 0, deleted = 1 where user_id = ".$user_id." and id = ".$rowid);
		} else if(strstr(($response_arr[0]), "429")){
			sleep(1);
		}
		return false;
	}
	function updatebulkinv($jsonStr, $shopurl, $token) {
        $iurl = "https://{$shopurl}/admin/api/2024-01/graphql.json";
    
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $iurl,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Shopify-Access-Token: ' . $token
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $jsonStr
        ]);
        
        $response = curl_exec($curl);
        curl_close($curl);
    
        echo "API Response: " . $response . "\n";
    }
    


    function getinvlevel($locid, $shopurl, $token){
        global $invlevel;

        $iurl = "https://{$shopurl}/admin/api/2023-07/inventory_levels.json?location_ids=$locid";
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $iurl);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token:' . $token));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        
        $response = curl_exec($curl);
        curl_close($curl);
        $response_arr = json_decode($response,true);
        print_r ( $response_arr ) ;

        foreach($response_arr['inventory_levels'] as $inv){
            $invlevel[] = array("shopifyinventoryid" => $inv['inventory_item_id'],"api" =>$inv['admin_graphql_api_id'] );
        }
        
        return true;
    }



	function updateBulkPrice($jsonrj, $shopurl, $token) {
		$url = "https://{$shopurl}/admin/api/2024-01/graphql.json";
	
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'X-Shopify-Access-Token: ' . $token
			],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => json_encode(['query' => $jsonrj]),
		]);
		
		$response = curl_exec($curl);
		
		print_r($response);
		
		curl_close($curl);
	}
          
	function get_html_scraper_api_content($url) {
		$ch = curl_init();
		$apiURL = "http://api.scraperapi.com/?key=7a8ceb5a4f523bc3c82a69c9a759ddca&url=".urlencode($url);
		if(strpos($url, 'amazon.co.uk') !== false){
            $apiURL = "http://api.scraperapi.com/?key=7a8ceb5a4f523bc3c82a69c9a759ddca&country_code=uk&url=".urlencode($url);		    
		}
		curl_setopt($ch, CURLOPT_URL, $apiURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  "Accept: application/json"
		));

		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
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
    	if($domain == "amazon.com.au" || $domain == "www.amazon.com.au"){
          return true; 
        }
    	return false;
	}
    
    function getCountry($producturl){
		$domain = parse_url($producturl, PHP_URL_HOST);
		if($domain == "amazon.com" || $domain == "www.amazon.com"){
			return "US";
		} else if($domain == "amazon.ca" || $domain == "www.amazon.ca"){
			return "CA";
		} else if($domain == "amazon.in" || $domain == "www.amazon.in"){
			return "IN";
		} else if($domain == "amazon.co.uk" || $domain == "www.amazon.co.uk"){
			return "GB";
		} else if($domain == "amazon.com.br" || $domain == "www.amazon.com.br"){
			return "BR";
		} else if($domain == "amazon.com.mx" || $domain == "www.amazon.com.mx"){
			return "MX";
		} else if($domain == "amazon.de" || $domain == "www.amazon.de"){
			return "DE";
		} else if($domain == "amazon.es" || $domain == "www.amazon.es"){
			return "ES";
		} else if($domain == "amazon.fr" || $domain == "www.amazon.fr"){
			return "FR";
		} else if($domain == "amazon.co.jp" || $domain == "www.amazon.co.jp"){
			return "JP";
		} else if($domain == "amazon.cn" || $domain == "www.amazon.cn"){
			return "CN";
		} else if($domain == "amazon.com.au" || $domain == "www.amazon.com.au"){
			return "AU";
		} 
		return false;
	}

	function proxycrawlapi($user_id, $producturl){
		$country = getCountry($producturl);
	    $response = [];
		$url = 'https://api.proxycrawl.com/?token=A8zfXIDXwsj2o5A_1upnJg&autoparse=true&url='.urlencode($producturl);
		if($country){
			$url = 'https://api.proxycrawl.com/?token=A8zfXIDXwsj2o5A_1upnJg&autoparse=true&country='.$country.'&url='.urlencode($producturl);
		}
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$data = curl_exec($curl);
		curl_close($curl);
		
		$res1 = json_decode($data, true);	 	
		// print_r($res1);
			
		if($res1['original_status'] == 200 && isset($res1['body'])){

			$res = $res1['body'];
			
if(isset($res['name'])){$response['title'] = $res['name'];}	
			if(isset($res['description'])){$response['description'] = $res['description'];}	
			if(isset($res['brand'])){$response['brand'] = $res['brand'];}	
			if(isset($res['breadCrumbs'][0])){$response['category'] = $res['breadCrumbs'][0]['name'];}	
			 $response['url'] = $producturl;$response['currency'] = '';
			if(isset($res['price'])){if (strpos($res['price'], '-') !== false) {$price_arr = explode(" ",$res['price']);$response['price'] = trim($price_arr[0]);}else{$response['price'] = $res['price'];}}
			if(isset($res['inStock']) && $res['inStock'] == true){$response['in_stock___out_of_stock'] = 'In stock.';}	
			if(isset($res['images'])){$response['high_resolution_image_urls'] = $res['mainImage'].'|'.implode("|",$res['images']);}	
			if(isset($res['features'])){$response['bullet_points'] = $res['features'];}
			$quantity = 0;
			if(isset($response['in_stock___out_of_stock']) && $response['in_stock___out_of_stock'] == 'In stock.') {
			    $quantity = 1;
			}
			$response['quantity'] = $quantity;
			if(isset($res['productInformation'][0])){
				for($i=0;$i<count($res['productInformation']);$i++){
					$k = preg_replace("/[^a-zA-Z0-9\s]/", "", $res['productInformation'][$i]['name']);
					if(trim($k) == 'ASIN'){
					    $asin = $res['productInformation'][$i]['value'];
					    $asin = preg_replace("/[^a-zA-Z0-9\s]/", "", $asin);
					    $response['asin'] = $asin;
					}
				}
			}
			if(isset($response['high_resolution_image_urls'])) {
				$high_resolution_image_urls = $response['high_resolution_image_urls'];
				$images = explode("|", $high_resolution_image_urls);
				$images = array_map("trim", $images);
				$response['images'] = $images;
			}
			$output = array();
			$output["status"] = "success";
			$output['message'] = $response;return $output;
		}else{$response = array("status" => "error", "message" => "crawling error.");return $response;}
	}
	
  	function getSettings($user_id, $conn) {
		$settingsResult = $conn->query("select * from setting where user_id = ".$user_id);
    	if($settingsResult->num_rows > 0){
    		$settingsRow = $settingsResult->fetch_assoc();
			return $settingsRow;    	
    	}
		return array();
	}	 	

	function updateShopifyInventory($token, $shopurl, $inventory_item_id, $location_id, $quantity){
		global $conn;
		$data = array("location_id" => $location_id, "inventory_item_id" => $inventory_item_id, "available" => $quantity);
		echo 'i am here'.$quantity;
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
		echo $response;
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
			return true;
		}		
		return false;
	}
	
	function getInventoryId($user_id, $token, $shopurl, $shopifyvariantid){
		global $conn;
		$apiurl = "https://".$shopurl."/admin/api/2022-01/variants/".$shopifyvariantid.".json";		
		$session = curl_init();
		curl_setopt($session, CURLOPT_URL, $apiurl);
		curl_setopt($session, CURLOPT_HTTPGET, 1);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);
		$response = curl_exec($session);
		if($response){
			$resObj = json_decode($response, true);
			if(isset($resObj['variant']) && isset($resObj['variant']['inventory_item_id'])){
				return trim($resObj['variant']['inventory_item_id']);
			}
		}
		sleep(1);
		return "";
	}

	function getLocationId($token, $shopurl, $inventory_item_id){
		//addlog("getLocation called","INFO");
    	$apiurl = "https://".$shopurl."/admin/api/2022-01/inventory_levels.json?inventory_item_ids=".$inventory_item_id;
    	//addlog("getLocation called".$apiurl,"INFO");
    	$session = curl_init();
    	curl_setopt($session, CURLOPT_URL, $apiurl);
    	curl_setopt($session, CURLOPT_HTTPGET, 1);
    	curl_setopt($session, CURLOPT_HEADER, false);
    	curl_setopt($session, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token));
    	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);
    	$response = curl_exec($session);
    	//addlog($response,"INFO");
    	curl_close ($session);
    	if($response){
    		$resObj = json_decode($response, true);
    		if(isset($resObj['inventory_levels']) && isset($resObj['inventory_levels'][0]['location_id'])){
    			//addlog(json_encode($resObj),"INFO");
    			return trim($resObj['inventory_levels'][0]['location_id']);
    		}	
    	}
    	//addlog("not proper Response for getLocationId","ERROR");
    	return false;
	}

    function applyPriceMarkup($price, $markuptype, $markupval, $markupround, $user_id){
    	$newprice = $price;
		
    	if($markuptype == "FIXED"){
    		$newprice = $price + $markupval;
    	} else {
    		$newprice = $price + $price*$markupval/100;
    	}
    	if($markupround){
    		$newprice = round($newprice);
    	}
    	return $newprice;
	}    
    
    function getAmount($money) {
	    $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
		$onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);
	    $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;
	    $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
		$removedThousendSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '',  $stringWithCommaOrDot);
	    return (float) str_replace(',', '.', $removedThousendSeparator);
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
    function getMainLocation($user_id, $shopurl, $token){
		global $conn;
		$result = $conn->query("select * from locations where legacy = 0 and user_id = ".$user_id." order by shopifylocationid * 1");
		if($result->num_rows > 0){
			$row = $result->fetch_assoc();
			$location_id = $row['shopifylocationid'];
			return $location_id;
		} else{
			// Try to fetch all possible locations
			$location_id = fetchLocations($user_id, $shopurl, $token);
			if($location_id){
				return $location_id;
			}
		}
		return false;
	}
	
	function fetchLocations($user_id, $shopurl, $token) {
		global $conn;		
		$existingLocations = array();
		$result = $conn->query("select * from locations where user_id = ".$user_id);
		if($result->num_rows > 0){
			while($row = $result->fetch_assoc()){
				$existingLocations[] = $row['shopifylocationid'];
			}
		}

		$apiurl = "https://".$shopurl."/admin/api/2022-01/locations.json";
		$session = curl_init();
		curl_setopt($session, CURLOPT_URL, $apiurl);
		curl_setopt($session, CURLOPT_HTTPGET, 1);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);
	//	curl_setopt($session, CURLOPT_TIMEOUT, 10);
		$response = curl_exec($session);	
		curl_close($session);
		$session = "";
		if($response){
			$respObj = json_decode($response, true);
			if(isset($respObj['locations'])){
				$locationArr = $respObj['locations'];
				foreach($locationArr as $locationObj){
					$locName = $locationObj['name'];
					$shopifylocationid = $locationObj['id'];
					if(in_array($shopifylocationid, $existingLocations)){
						continue;
					}
					$legacy = 0;
					if($locationObj['legacy']){
						$legacy = 1;
					}
					$conn->query("insert into locations(name, legacy, status, shopifylocationid, user_id, created_at, updated_at) values('".mysqli_real_escape_string($conn, $locName)."', ".$legacy.", 'active', ".mysqli_real_escape_string($conn, $shopifylocationid).", ".$user_id.", now(), now())");
					$existingLocations[] = $shopifylocationid;
				}
			}
		}
		if(count($existingLocations) > 0){
			sort($existingLocations, SORT_NUMERIC); 
			return $existingLocations[0];
		} else {
			return false;
		}
	}

	// function addlog($message, $type){
    // 	global $logfile;
    // 	$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
    // 	fwrite($logfile, $txt);
	// }
	
?>