<?php
set_time_limit(0);
$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
if($conn){
	addlog('Database connected',"INFO");
} else {
	addlog('Database Connection Error',"ERROR");
    die("Database Connection Error");
}
mysqli_set_charset($conn, "utf8");

$user_id = 19494;
$result = $conn->query("select * from users where id = ".$user_id);
if($result->num_rows < 1){
	die("invalid user id");
}
$row = $result->fetch_assoc();
$shopurl = $row['shopurl'];
$token = $row['token'];

$result = $conn->query("select * from setting where user_id = ".$user_id);
if($result->num_rows < 1){
	die("invalid user id");
}
$row = $result->fetch_assoc();
$markupenabled = 0;
$markuptype = 'FIXED';
$markupval = 0;
$markupround = 0;
if(isset($row['markupenabled']) && $row['markupenabled'] == 1){
  	$markupenabled = true;
}
if(isset($row['markuptype']) && strlen($row['markuptype']) > 0){
	$markuptype = $row['markuptype'];
}
if(isset($row['markupval'])){
	$markupval = $row['markupval'];
}
if(isset($row['markupround']) && $row['markupround'] == 1){
	$markupround = true;
}

$result = $conn->query("select * from product_variants where user_id = 19494 and shopifyvariantid != '' and quantityflag =1");

if($result->num_rows > 0){
	while($row = $result->fetch_assoc()) {
		$shopifylocationid = $row['shopifylocationid'];
		$shopifyinventoryid = $row['shopifyinventoryid'];
		
		print_r($data);
		updateShopifyInventory($token, $shopurl, $shopifyinventoryid, $shopifylocationid, 1);
	//	updateShopifyVariant($token, $shopurl, $shopifyvariantid, $data);	
		sleep(1);
	}

}



	function applyPriceMarkup($price, $markuptype, $markupval, $markupround, $user_id){
    	$newprice = $price;		
    	if($markuptype == "FIXED"){
    		$newprice = $price + $markupval;
    	} else {
    		$newprice = $price + $price*$markupval/100;
    	}
		if($user_id == 19494){
		    $newprice = $newprice * 3420;
		}
    	if($markupround){
    		$newprice = round($newprice);
    	}
    	return $newprice;
	}



function updateShopifyProduct($token, $shopurl, $product_id, $data){	

	$url = "https://".$shopurl."/admin/api/2021-07/products/".$product_id.".json";	

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

	$response_arr=explode("\n",$response);		

	print_r($response_arr);

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

	if( (strstr(($response_arr[0]), "200")) || (strstr(($response_arr[1]), "200")) || (strstr(($response_arr[2]), "200")) ){

		addlog("Product updated with ID - ".$product_id, "SUCCESS");

		return 1;

	} else {

		echo $product_id."<br />";

		addlog("Error updating product with ID - ".$product_id.", Err Details: ".serialize($response_arr), "ERROR");			

		return null;

	}

}

function updateShopifyInventory($token, $shopurl, $inventory_item_id, $location_id, $quantity){
		global $conn;
		$data = array("location_id" => $location_id, "inventory_item_id" => $inventory_item_id, "available" => $quantity);
		echo 'i am here'.$quantity;
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

function updateShopifyVariant($token, $shopurl, $variant_id, $data){

	global $conn;

	$url = "https://".$shopurl."/admin/api/2021-07/variants/".$variant_id.".json";

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
echo $response;
	curl_close($curl);

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

	}

//	print_r($response_arr);

	if( (strstr(($response_arr[0]), "200")) || (strstr(($response_arr[1]), "200")) || (strstr(($response_arr[2]), "200")) ){

		addlog("Product updated with ID - ".$variant_id, "SUCCESS");

		return 1;		

	} else {

		addlog("Error updating product with ID - ".$variant_id.", Err Details: ".serialize($response_arr), "ERROR");

		return false;

	}

	return false;

}



function addlog($message, $type){

	//	global $myfile;

	//	$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";

	//	fwrite($myfile, $txt);

}

//fclose($myfile);

?>
