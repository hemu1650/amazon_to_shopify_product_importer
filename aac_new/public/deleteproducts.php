<?php
// Don't delete: 3090, 
set_time_limit(0);
$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');

// Check connection
if ($conn->connect_error) {
	// TODO: Add some logging and email notification here
	die("Connection failed: " . $conn->connect_error);
}
$handleArr = array();
$file = fopen("reviews.csv","r");
$skuArr = array();
$row = fgetcsv($file, 0);
while(!feof($file)) {
	$row = fgetcsv($file, 0);		
	if(strlen(trim($row[0])) < 1){
		continue;
	}
	$handle = trim($row[0]);
	$handleArr[] = $handle;
	//$conn->query("update shopifyproducts set gid_shopifylocationid = 'Done' where user_id = 3208 and handle = '".mysqli_real_escape_string($conn, $handle)."'");
}
$handleArr = array_unique($handleArr);
foreach($handleArr as $handle){
    $conn->query("update shopifyproducts set gid_shopifylocationid = 'Done' where user_id = 3208 and handle = '".mysqli_real_escape_string($conn, $handle)."'");
}
print_r($handleArr);
exit;
$result = $conn->query("SELECT * FROM `product_variants` WHERE user_id = 14308 and shopifyproductid in (select productid from shopifyproducts where user_id = 14308)");
    if($result->num_rows > 0){
    	while($row = $result->fetch_assoc()){
    	    $asin = $row['asin'];
    	    $conn->query("INSERT INTO fetchReviews(user_id, product_asin, status, created_at, updated_at) VALUES (14308, '".$asin."', 0, now(), now())");
    	}
    }
exit;
    $asinArr = array();
    $file = fopen("Products to add to Shopify from Amazon (Infoshore Apps import) - Feuille 1 (1).csv","r");
	$skuArr = array();
	$row = fgetcsv($file, 0);		
	//print_r($row);exit;
	while(!feof($file)) {
		$row = fgetcsv($file, 0);		
		if(strlen(trim($row[0])) < 1){
			continue;
		}
		$asin = trim($row[0]);
		$price = trim($row[1]);
		$asinArr[$asin] = $price;
	}
	foreach($asinArr as $asin => $price){
	    $result = $conn->query("SELECT * FROM `shopifyproducts` WHERE user_id = 3208 and sku = '".$asin."'");
        if($result->num_rows < 1){
            //echo "'".$asin."',";
            echo $asin."<br />";
        }
	}
	/*print_r($asinArr);
	$result = $conn->query("SELECT * FROM `product_variants` WHERE user_id = 3208");
    if($result->num_rows > 0){
    	while($row = $result->fetch_assoc()){
    	    $asin = $row['asin'];
    	    //echo $asin;
    	    if(array_key_exists($asin, $asinArr)){
    	      //  $conn->query("update product_variants set price = '".mysqli_real_escape_string($conn, $asinArr[$asin])."', saleprice = '".mysqli_real_escape_string($conn, $asinArr[$asin])."' where user_id = 3208 and id = ".$row['id']);
    	    } else {
    	        echo $asin."<br />";
    	    }
    	   // exit;
    	}
    }*/
	
	fclose($file);	

exit;
$userArr = array();
/*$result = $conn->query("select distinct user_id from products");
if($result->num_rows > 0){
	while($row = $result->fetch_assoc()){
		$user_id = $row['user_id'];
		$result1 = $conn->query("select count(*) as cnt, ebayitemid from products where user_id = ".$user_id." group by ebayitemid having cnt > 1");
		if($result1->num_rows > 0){
			$userArr[] = $user_id;
		}
	}
}*/
$userArr = array(3208);
//$userArr = array(9276);
foreach($userArr as $user_id){
	$result = $conn->query("select * from users where id = ".$user_id." and installationstatus = 1");
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {	
			$id = $row['id'];
			$shopurl = $row['shopurl'];
			$token = $row['token'];
			echo $id;
		//	$conn->query("delete FROM `products` WHERE `user_id`=".$id." and product_id not in (select product_id from product_variants where user_id = ".$id.")");
			handleProductWithoutVariants($id, $token, $shopurl);		
		}
	}
}

// 'B01BT903AW','B01BT95K9G','B01BT9DGLU','B01F3ZP3EY','B01BDRMGGE','B01BDSL312'
function handleProductWithoutVariants($user_id, $token, $shopurl){
	global $conn;			
	$result = $conn->query("SELECT count(*) as cnt, asin FROM `product_variants` WHERE user_id = ".$user_id." group by asin having cnt > 1");	
	//$result = $conn->query("SELECT count(*) as cnt, title FROM `products` WHERE user_id = ".$user_id." group by title having cnt > 1");	
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$asin = $row['asin'];
			echo $asin;
			$result1 = $conn->query("select * from `product_variants` where user_id = ".$user_id." and asin = '".$asin."' order by product_id desc limit 0, 1");			
			if($result1->num_rows > 0){
				$row1 = $result1->fetch_assoc();
				$shopifyproductid = $row1['shopifyproductid'];
				$product_id = $row1['product_id'];
				$variant_id = $row1['id'];
				if(strlen($shopifyproductid) > 0){
					continue;
				}				
				$conn->query("delete from product_images where variant_id = ".$variant_id." and user_id = ".$user_id);
				$conn->query("delete from product_variants where user_id = ".$user_id." and product_id = ".$product_id);
				$conn->query("delete from products where user_id = ".$user_id." and product_id = ".$product_id);
			}
			//exit;
		}
	}
}

function deleteProduct($user_id, $product_id){
	global $conn;
	$result1 = $conn->query("select id from product_variants where user_id = ".$user_id." and product_id = ".$product_id);
	if($result1->num_rows > 0) {
		while($row1 = $result1->fetch_assoc()) {
			$variant_id = $row1['id'];
			$conn->query("delete from product_images where variant_id = ".$variant_id." and user_id = ".$user_id);
		}
	}
	$conn->query("delete from product_description where user_id = ".$user_id." and product_id = ".$product_id);
	$conn->query("delete from product_variants where user_id = ".$user_id." and product_id = ".$product_id);
	$conn->query("delete from products where user_id = ".$user_id." and product_id = ".$product_id);
}

function deleteShopifyProduct($user_id, $token, $shopurl, $product_id){
	global $conn;
	$url = "https://".$shopurl."/admin/api/2021-07/products/".$product_id.".json";
	echo $url;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token, 'Content-Type: application/json; charset=utf-8'));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_VERBOSE, 0);
	curl_setopt($curl, CURLOPT_HEADER, 1);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
		
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec ($curl);
	curl_close ($curl);	
	$response_arr=explode("\n",$response);
	//print_r($response_arr);
	if(strstr(($response_arr[0]), "200") || strstr(($response_arr[1]), "200") || strstr(($response_arr[2]), "200")){
		$conn->query("update products set shopifyproductid = '', status ='Ready to Import' where user_id = ".$user_id." and shopifyproductid = '".$product_id."'");

		$conn->query("update product_variants set shopifyproductid = '', shopifyvariantid = '', status ='Ready to Import' where user_id = ".$user_id." and shopifyproductid = '".$product_id."'");

		$conn->query("delete from shopifyproducts where user_id = ".$user_id." and productid = '".$product_id."'");		
	} else {
		$conn->query("update products set shopifyproductid = '', status ='Ready to Import' where user_id = ".$user_id." and shopifyproductid = '".$product_id."'");

		$conn->query("update product_variants set shopifyproductid = '', shopifyvariantid = '', status ='Ready to Import' where user_id = ".$user_id." and shopifyproductid = '".$product_id."'");

		$conn->query("delete from shopifyproducts where user_id = ".$user_id." and productid = '".$product_id."'");			
	}
}
?>