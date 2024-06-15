<?php
	
	function updateShopifyInventory($token, $shopurl, $inventory_item_id, $location_id, $quantity){
		global $conn;
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
		//@mail("khariwal.rohit@gmail.com", "EPI - Inventory error in import", $shopurl.'-'.json_encode($response_arr));
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
			}else{
				$conn->query("insert into failed_productimports(url, reason, type, user_id) values('Bulk Import', 'Unable to get inventory id', 'Shopify', '".mysqli_real_escape_string($conn, $user_id)."')");
			}
		}
		sleep(1);
		return "";
	}

	function getLocationId($token, $shopurl, $inventory_item_id){
		$apiurl = "https://".$shopurl."/admin/api/2022-01/inventory_levels.json?inventory_item_ids=".$inventory_item_id;
    	$session = curl_init();
    	curl_setopt($session, CURLOPT_URL, $apiurl);
    	curl_setopt($session, CURLOPT_HTTPGET, 1);
    	curl_setopt($session, CURLOPT_HEADER, false);
    	curl_setopt($session, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token));
    	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);
    	$response = curl_exec($session);
    	curl_close ($session);
    	if($response){
    		$resObj = json_decode($response, true);
    		if(isset($resObj['inventory_levels']) && isset($resObj['inventory_levels'][0]['location_id'])){
    			return trim($resObj['inventory_levels'][0]['location_id']);
    		}else{
				$conn->query("insert into failed_productimports(url, reason, type, user_id) values('Bulk Import', 'Unable to get location id', 'Shopify', '".mysqli_real_escape_string($conn, $user_id)."')");
			}
    	}
    	return false;
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
		curl_close ($curl);        
		$response_arr = explode("\n",$response);
    
		if( (strstr(($response_arr[0]), "201")) || (strstr(($response_arr[1]), "201")) || (strstr(($response_arr[2]), "201")) ){
			$product_json = end($response_arr);
			$product_arr = json_decode($product_json, true);
			$product_arr = $product_arr["product"];
			return $product_arr;
		} else {
			//print_r($data);
			//print_r($response_arr);
		}
		return null;
	}
	
	function updateShopifyProduct($token, $shopurl, $product_id, $data){	
		$url = "https://".$shopurl."/admin/api/2022-01/products/".$product_id.".json";
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
		if(strstr(($response_arr[0]), "200")){	
			return 1;
		} else {
			return null;
		}
	}
	
	function updateShopifyVariant($token, $shopurl, $variant_id, $data){	
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
		if(strstr(($response_arr[0]), "200")){	
			return 1;
		} else {
			return null;
		}
	}

	function deleteShopifyProduct($user_id, $token, $shopurl, $product_id){
		$url = "https://".$shopurl."/admin/api/2022-01/products/".$product_id.".json";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token, 'Content-Type: application/json; charset=utf-8'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec ($curl);
		curl_close($curl);
		$response_arr = explode("\n",$response);
		if( (strstr(($response_arr[0]), "200")) || (strstr(($response_arr[1]), "200")) || (strstr(($response_arr[2]), "200")) ){
			return true;		
		} else {
			return false;	
		}
	}
	
	function updateShopifyInventory1($token, $shopurl, $inventory_item_id, $location_id, $quantity){
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
		} else if((strstr(($response_arr[0]), "HTTP/1.1 403 Forbidden")) || (strstr(($response_arr[0]), "HTTP/1.1 422 Unprocessable Entity"))) {
			$new_location_id = getLocationId($token, $shopurl, $inventory_item_id);
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
				if( (strstr(($response_arr[0]), "200")) || (strstr(($response_arr[1]), "200")) || (strstr(($response_arr[2]), "200")) ) {
					//$conn->query("update product_variants set shopifylocationid = '".mysqli_real_escape_string($conn, $new_location_id)."' where user_id = ".$user_id." and id = ".$rowid);
					//$conn->query("update settings set shopifylocationid = '".mysqli_real_escape_string($conn, $new_location_id)."' where user_id = ".$user_id);
					return true;
				} else {					
					return false;
				}
			}
		}		
		return true;
	}
	
	function fetchMetafields($shopurl, $token, $shopifyproductid, $shopifyvariantid){
		$url="https://".$shopurl."/admin/api/2022-01/products/".$shopifyproductid."/variants/".$shopifyvariantid."/metafields.json";
		$session = curl_init();
		curl_setopt($session, CURLOPT_URL, $url);
		curl_setopt($session, CURLOPT_HTTPGET, 1);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);
		$response = curl_exec($session);
		curl_close($session);
		if($response){
			$metafieldsArr = json_decode($response, true);	
			if(isset($metafieldsArr['metafields'])){
				return $metafieldsArr['metafields'];
			}
		}
		return false;
	}
	
	function fetchShopMetafields($shopurl, $token){
		$url="https://".$shopurl."/admin/api/2022-01/metafields.json";
		$session = curl_init();
		curl_setopt($session, CURLOPT_URL, $url);
		curl_setopt($session, CURLOPT_HTTPGET, 1);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);
		$response = curl_exec($session);
		curl_close($session);
		if($response){
			$metafieldsArr = json_decode($response, true);	
			if(isset($metafieldsArr['metafields'])){
				return $metafieldsArr['metafields'];
			}
		}
		return false;
	}
	
	function createMetafield($token, $shopurl, $shopifyvariantid, $data){	
		$url = "https://".$shopurl."/admin/api/2022-01/variants/".$shopifyvariantid."/metafields.json";	
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
		$response_arr = explode("\n",$response);
		
		if( (strstr(($response_arr[0]), "201")) || (strstr(($response_arr[1]), "201")) || (strstr(($response_arr[2]), "201")) ){
			return true;
		} else {
			return false;
		}
	}
	
	function createShopMetafield($token, $shopurl, $data){	
		$url = "https://".$shopurl."/admin/api/2022-01/metafields.json";	
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
		$response_arr = explode("\n",$response);
		
		if( (strstr(($response_arr[0]), "201")) || (strstr(($response_arr[1]), "201")) || (strstr(($response_arr[2]), "201")) ){
			return true;
		} else {
			return false;
		}
	}
	
	function updateMetafield($token, $shopurl, $metafield_id, $data){	
		$url = "https://".$shopurl."/admin/api/2022-01/metafields/".$metafield_id.".json";	
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
		
		if( (strstr(($response_arr[0]), "201")) || (strstr(($response_arr[1]), "201")) || (strstr(($response_arr[2]), "201")) ){
			return true;
		} else {
			return false;
		}
	}
	