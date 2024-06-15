<?php
	set_time_limit(0);
    ini_set('memory_limit', '-1');
	//require_once("includes/config.php");

	$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
	// Check connection
	if ($conn->connect_error) {
		// TODO: Add some logging and email notification here
		die("Connection failed: " . $conn->connect_error);
	}
	$result = $conn->query("SELECT * FROM `product_variants` WHERE user_id = 21373 and handle = '' and shopifyproductid != '' and handle = ''");
    if ($result->num_rows > 0) {
    	while($row = $result->fetch_assoc()) {	
    		$shopifyproductid = $row['shopifyproductid'];
    		$result1 = $conn->query("SELECT * FROM `shopifyproducts` WHERE user_id = 21373 and productid = '".$shopifyproductid."'");
    		if ($result1->num_rows < 1) {
    		    continue;
    		}
    		$row1 = $result1->fetch_assoc();
    		$handle = $row1['handle'];
    		echo "update product_variants set handle = '".mysqli_real_escape_string($conn, $handle)."' where user_id = 21373 and id = ".$row['id'];
    		$conn->query("update product_variants set handle = '".mysqli_real_escape_string($conn, $handle)."' where user_id = 21373 and id = ".$row['id']);
    	}
    }
	exit;
	$userArr = array(21373);

/*	$result = $conn->query("select u.*, s.inventory_sync, s.price_sync, s.markupenabled, s.markuptype, s.markupval, s.markupround, s.defquantity, s.outofstock_action, s.shopifylocationid, s.inventory_policy from users u, setting s where u.id = s.user_id and u.plan > 2 and u.installationstatus = 1 and (s.inventory_sync = 1 or s.price_sync=1)");
    if ($result->num_rows > 0) {
    	while($row = $result->fetch_assoc()) {	
    		$userArr[] = $row['id'];
    	}
    }*/
    //print_r($userArr);exit;
	$existingSKUs = array();
	foreach($userArr as $user_id){
    	$result = $conn->query("select * from users where id = ".$user_id);
        if($result->num_rows < 1){
            echo "invalid user id";
            continue;
        }
        $row = $result->fetch_assoc();
        $shopurl = $row['shopurl'];
        $token = $row['token'];
    	$conn->query("delete from shopifyproducts where user_id = ".$user_id);
    	$existingSKUs = array();
    	$result = $conn->query("select * from shopifyproducts where user_id = ".$user_id);
    	if ($result->num_rows > 0) {
    		while($row = $result->fetch_assoc()) {	
    			$existingSKUs[] = $row['productid'];
    		}
    	}
    	mysqli_autocommit($conn, FALSE);
    	fetchCatalog($user_id, $shopurl, $token, $existingSKUs, "");
    	mysqli_commit($conn);
    	mysqli_autocommit($conn, TRUE);
	}
    
        
	

	function fetchCatalog($user_id, $shopurl, $token, $existingSKUs, $nextLink){
		global $conn;
		$productUrl = $nextLink;
		if($nextLink == ""){
			$productUrl="https://".$shopurl."/admin/api/2021-07/products.json?limit=100";
		}		
		$session = curl_init();
		curl_setopt($session, CURLOPT_URL, $productUrl);
		curl_setopt($session, CURLOPT_HTTPGET, 1);
		curl_setopt($session, CURLOPT_HEADER, true);
		curl_setopt($session, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);
		$response = curl_exec($session);	
		$response_arr = explode("\n",$response);
		if(strstr(($response_arr[0]), "200")){
			$header_size = curl_getinfo($session, CURLINFO_HEADER_SIZE);
			curl_close($session);
			$header = substr($response, 0, $header_size);
			$body = substr($response, $header_size);
			$products_array = json_decode($body, true);	
			if($products_array){
				foreach($products_array['products'] as $product) {	
					$product_id = $product["id"];
					$title = $product["title"];
					$handle = $product["handle"];
					if (in_array($product_id, $existingSKUs)) {
						continue;
					}
					$product_type = $product["product_type"];
					$gid_shopifyproductid = $product["admin_graphql_api_id"];
					$variants = $product["variants"];
					//$conn->query("insert into test_1250(shopifyproductid, imgarr, created_at) values ('".$product_id."', '".mysqli_real_escape_string($conn, $product['tags'])."', now())");
					foreach($variants as $variant){
						$variant_id = $variant["id"];	
						$gid_shopifyvariantid = $variant["admin_graphql_api_id"];
						$sku = $variant["sku"];
						$price = $variant["price"];
						$compare_at_price = $variant["compare_at_price"];
						$qty = $variant["inventory_quantity"];
						//echo "insert into shopifyproducts(user_id, handle, productid, variantid, gid_shopifyproductid, gid_shopifyvariantid, sku, price, qty, dateofmodification) values ('".$user_id."', '".mysqli_real_escape_string($conn, $handle)."', ".$product_id."','".$variant_id."','".mysqli_real_escape_string($conn, $gid_shopifyproductid)."','".mysqli_real_escape_string($conn, $gid_shopifyvariantid)."','".mysqli_real_escape_string($conn, $sku)."','".$price."','".$qty."', now())";exit;
						//$conn->query("insert into shopifyproducts(user_id, title, productid, variantid, gid_shopifyproductid, gid_shopifyvariantid, sku, price, qty, compare_at_price, dateofmodification) values ('".$user_id."', '".mysqli_real_escape_string($conn, $title)."', '".$product_id."','".$variant_id."','".mysqli_real_escape_string($conn, $gid_shopifyproductid)."','".mysqli_real_escape_string($conn, $gid_shopifyvariantid)."','".mysqli_real_escape_string($conn, $sku)."','".$price."','".$qty."', '".$compare_at_price."', now())");
					    $conn->query("insert into shopifyproducts(user_id, handle, productid, variantid, gid_shopifyproductid, gid_shopifyvariantid, sku, price, qty, dateofmodification) values ('".$user_id."', '".mysqli_real_escape_string($conn, $handle)."', '".$product_id."','".$variant_id."','".mysqli_real_escape_string($conn, $gid_shopifyproductid)."','".mysqli_real_escape_string($conn, $gid_shopifyvariantid)."','".mysqli_real_escape_string($conn, $sku)."','".$price."','".$qty."', now())");
					}	
				}					
				mysqli_commit($conn);
			}
			$pattern = '/rel="previous",\s*<(.*)>;\s*rel="next"/smiU';
			$res1 = preg_match_all($pattern, $header, $result1);
			sleep(1);
			if($res1){			
				$nextLink = isset($result1[1][0])?trim($result1[1][0]):"";			
				fetchCatalog($user_id, $shopurl, $token, $existingSKUs, $nextLink);
			} else {
			    $pattern = '/Link:\s*<(.*)>;\s*rel="next"/smiU';
			    $res1 = preg_match_all($pattern, $header, $result1);
			    if($res1){			
				    $nextLink = isset($result1[1][0])?trim($result1[1][0]):"";			
				    fetchCatalog($user_id, $shopurl, $token, $existingSKUs, $nextLink);
			    } else {
			        echo $productUrl;
		            print_r($response_arr);        
			    }
			}
		} else if(strstr(($response_arr[0]), "HTTP/2 500")){
		    sleep(1);
		    fetchCatalog($user_id, $shopurl, $token, $existingSKUs, $nextLink);
		} else if(strstr(($response_arr[0]), "HTTP/2 429")){
		    sleep(2);
		    fetchCatalog($user_id, $shopurl, $token, $existingSKUs, $nextLink);
		} else if(strstr(($response_arr[0]), "HTTP/2 402")){
		    $conn->query("update users set usermsg = '402' where shopurl = '".mysqli_real_escape_string($conn, $shopurl)."'");
		    return false;
		} else {
		    echo $productUrl;
		    print_r($response_arr);
		}		
	}
?>