<?php
//die();
set_time_limit(0);

$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');

// Check connection
if ($conn->connect_error) {
	// TODO: Add some logging and email notification here
	die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("select * from users where id in (3208)");
if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		$id = $row['id'];
		$shopurl = $row['shopurl'];
		$token = $row['token'];		
		handleProductWithoutVariants($id, $token, $shopurl);		
	}
}
// 'B01BT903AW','B01BT95K9G','B01BT9DGLU','B01F3ZP3EY','B01BDRMGGE','B01BDSL312'
function handleProductWithoutVariants($user_id, $token, $shopurl){
	global $conn;			
	$result1 = $conn->query("SELECT * FROM `products` WHERE user_id = 3208 and shopifyproductid = ''");
	if ($result1->num_rows > 0) {
		while($row1 = $result1->fetch_assoc()) {
		    $shopifyproductid = $row1['shopifyproductid'];
		    $product_id = $row1['product_id'];
			if($shopifyproductid != ''){
				$response = deleteShopifyProduct($user_id, $token, $shopurl, $shopifyproductid);
			}
			deleteProduct($user_id, $product_id);
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
	print_r($response_arr);	
	sleep(1);
	if(strstr(($response_arr[0]), "404") || strstr(($response_arr[0]), "200") || strstr(($response_arr[1]), "200") || strstr(($response_arr[2]), "200")){
	//	$conn->query("update products set shopifyproductid = '', status ='Import in progress' where user_id = ".$user_id." and shopifyproductid = '".$product_id."'");

	//	$conn->query("update product_variants set shopifyproductid = '', shopifyvariantid = '', status ='Ready to Import' where user_id = ".$user_id." and shopifyproductid = '".$product_id."'");

		$conn->query("delete from shopifyproducts where user_id = ".$user_id." and productid = '".$product_id."'");		
	} else {
	    die("");
	//	$conn->query("update products set shopifyproductid = '', status ='Ready to Import' where user_id = ".$user_id." and shopifyproductid = '".$product_id."'");

	//	$conn->query("update product_variants set shopifyproductid = '', shopifyvariantid = '', status ='Ready to Import' where user_id = ".$user_id." and shopifyproductid = '".$product_id."'");
		//$conn->query("delete from shopifyproducts where user_id = 4301 and productid = '".$product_id."'");	
	//	$conn->query("delete from shopifyproducts where user_id = ".$user_id." and productid = '".$product_id."'");			
	}
}
?>