<?php

set_time_limit(0);
if (!$loader = @include __DIR__.'/includes/apai-io-master/vendor/autoload.php') {
    die('You must set up the project dependencies, run the following commands:'.PHP_EOL.
        'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install'.PHP_EOL);
}

use ApaiIO\ApaiIO;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Lookup;

require_once("includes/config.php");

$logfile = fopen("logs/amz_syncInventory.txt", "a+") or die("Unable to open log file!");
addlog("Execution Started", "INFO");

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
	addlog("Database connection failed: " . $conn->connect_error, "ERROR");
	die("");
}
mysqli_set_charset($conn, "utf8");
    
$cronQuery = $conn->query("select isrunning from crons where crontype = 'updateshopifyinventory'");
$cronrow = $cronQuery->fetch_assoc();
if($cronrow['isrunning'] == 1){
    @mail("khariwal.rohit@gmail.com", "AAC: updateshopifyinventory: Cron already running", "updateshopifyinventory: Cron already running");
    die("");
}
    
$conn->query("update crons set lastrun = now(), isrunning = 1 where crontype = 'updateshopifyinventory'");

$result = $conn->query("select u.id, u.shopurl, u.token, u.sync, u.created_at, s.inventory_sync, s.price_sync, u.paid_at, u.review, u.membershiptype from users u, setting s, amz_keys ak where ak.user_id = u.id and s.user_id = u.id and (s.inventory_sync = 1 or s.price_sync = 1) and u.status = 'active' and u.installationstatus = 1 and (plan > 2 or u.id = 694)");

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {  
		$id = $row['id'];
        $shopurl = $row['shopurl'];
        $token = $row['token'];
        $membershiptype = $row['membershiptype'];
        $created_at = $row['created_at'];        
        $created_at_time = strtotime($created_at);
        $daysdiff = 8;
        $now = time();
        $datediff = $now - $created_at_time;
	    $daysdiff = floor($datediff / (60 * 60 * 24));     
        if($membershiptype == 'free' && $daysdiff > 7){
			continue;
        }        
        $inventory_sync = $row['inventory_sync'];
        $price_sync = $row['price_sync'];
		$amzKeysResult = $conn->query("select * from amz_keys where user_id = ".$id);
		if($amzKeysResult->num_rows < 1){
			// TODO: keys not available
			continue;
		}
		$amzKeysRow = $amzKeysResult->fetch_assoc();
		$country = $amzKeysRow['country'];
		$aws_access_id = $amzKeysRow['aws_access_id'];
		$aws_secret_key = $amzKeysRow['aws_secret_key'];
		$associate_id = $amzKeysRow['associate_id'];
        mysqli_autocommit($conn, FALSE);
		addlog("fetchInventoryData: Process started for user_id - ".$id, "INFO");
        fetchInventoryData($id, $country, $aws_access_id, $aws_secret_key, $associate_id);
        mysqli_commit($conn);
		addlog("updateInventoryAndPrice: Process started for user_id - ".$id, "INFO");
		updateInventoryAndPrice($id, $token, $shopurl, $inventory_sync, $price_sync);
		mysqli_commit($conn);
    }
}
mysqli_autocommit($conn, TRUE);
$conn->query("update crons set lastrun = now(), isrunning = 0 where crontype = 'updateshopifyinventory'");

function fetchOffersWithoutVariant($asin, $country, $aws_access_id, $aws_secret_key, $associate_id){
	global $conn;
	$conf = new GenericConfiguration();
	$client = new \GuzzleHttp\Client(['verify' => false]);
	$request = new \ApaiIO\Request\GuzzleRequest($client);		
	try {
		$conf
			->setCountry($country)
			->setAccessKey($aws_access_id)
			->setSecretKey($aws_secret_key)
			->setAssociateTag($associate_id)
			->setRequest($request)
			->setResponseTransformer(new \ApaiIO\ResponseTransformer\XmlToArray());
	
		$apaiIO = new ApaiIO($conf);
		$lookup = new Lookup();
		$lookup->setItemId($asin);
		$lookup->setResponseGroup(array('Offers'));
		$response = $apaiIO->runOperation($lookup);
		if(isset($response['Items']['Request']['Errors'])){
			return array("status" => "error", "errmessage" => json_encode($response['Items']['Request']['Errors']));
		}
		addlog("fetchInventoryData: Response of fetchOffersWithoutVariant - ".json_encode($response), "INFO");
		if(isset($response['Items']['Item'])){
			$items = $response['Items'];
			$item = array();
			if(isset($items['Item'][0]['ASIN'])){
				$item = $items['Item'][0];
			} else if(isset($items['Item']['ASIN'])) {
				$item = $items['Item'];
			}
			// Fetch offer details
			$offerlistingId = "";
			$price = "";
			$saleprice = "";
			$condition = "";
			$quantity = 0;
			if(isset($item['Offers'])){
				$offers = $item['Offers'];
				$totalOffers = isset($offers['TotalOffers'])?$offers['TotalOffers']:0;
				if($totalOffers > 0){
					$quantity = 1;
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
							$quantity = 1;
							$price = $lowestNewPrice['Amount']/100;
							$saleprice = $price;
						}
					}
				}
			} 
			return array("status" => "success", "data" => array("quantity" => $quantity, "price" => $price, "saleprice" => $saleprice, "offerlistingId" => $offerlistingId, "condition" => $condition));
		}
		return array("status" => "error", "errmessage" => "Item not found.");
	} catch (\Exception $e) {
		// TODO: Error in keys
		return array("status" => "error", "errmessage" => $e->getMessage());
	}
}

function fetchOffersWithVariant($parentasin, $country, $aws_access_id, $aws_secret_key, $associate_id){
	global $conn;
	$conf = new GenericConfiguration();
	$client = new \GuzzleHttp\Client(['verify' => false]);
	$request = new \ApaiIO\Request\GuzzleRequest($client);		
	try {
		$conf
			->setCountry($country)
			->setAccessKey($aws_access_id)
			->setSecretKey($aws_secret_key)
			->setAssociateTag($associate_id)
			->setRequest($request)
			->setResponseTransformer(new \ApaiIO\ResponseTransformer\XmlToArray());
	
		$apaiIO = new ApaiIO($conf);
		$lookup = new Lookup();
		$lookup->setItemId($parentasin);
		$lookup->setResponseGroup(array('Variations'));
		$response = $apaiIO->runOperation($lookup);
		if(isset($response['Items']['Request']['Errors'])){
			return array("status" => "error", "errmessage" => json_encode($response['Items']['Request']['Errors']));
		}		
		if(isset($response['Items']['Item'])){
			$variantsArr = array();
			$items = $response['Items'];
			$item = array();
			if(isset($items['Item'][0]['ASIN'])){
				$item = $items['Item'][0];
			} else if(isset($items['Item']['ASIN'])) {
				$item = $items['Item'];
			}
			if(isset($item['Variations'])){				
				$variations = $item['Variations'];
				$totalVariations = 0;
				if(isset($variations['TotalVariations'])){
					$totalVariations = $variations['TotalVariations'];	
				}
				if($totalVariations < 1){
					// TODO: no variant found.			
				}
				$variationItems = $variations['Item'];
				foreach($variationItems as $variationItem){
					$asin = "";					
					if(isset($variationItem['ASIN'])){
						$asin = $variationItem['ASIN'];
					}
					if($asin == 'B01M5CSS94' || $asin == 'B01MDNR8MI'){
						print_r($variationItem);
					}
					// Fetch offer details
					$offerlistingId = "";
					$price = "";
					$saleprice = "";
					$condition = "";
					$quantity = 0;
					if(isset($variationItem['Offers'])){
						$offers = $variationItem['Offers'];
						$offer = $offers['Offer'];
						if(isset($offer['OfferAttributes']['Condition'])){
							$condition = $offer['OfferAttributes']['Condition'];
						}
						if(isset($offer['OfferListing'])){
							$quantity = 1;
							$offerListing = $offer['OfferListing'];
							if(isset($offerListing['OfferListingId'])){
								$offerlistingId = $offerListing['OfferListingId'];
							}
							if(isset($offerListing['Price']['Amount'])){
								$price = number_format($offerListing['Price']['Amount']/100, 2, '.', '');
							}
							if(isset($offerListing['SalePrice']['Amount'])){
								$saleprice = number_format($offerListing['SalePrice']['Amount']/100, 2, '.', '');
							} else {
								$saleprice = $price;
							}
						}
					}
					$variantsArr[$asin] = array("quantity" => $quantity, "price" => $price, "saleprice" => $saleprice, "offerlistingId" => $offerlistingId, "condition" => $condition);
				}				
			}
			return array("status" => "success", "data" => $variantsArr);
		}
		return array("status" => "error", "errmessage" => "Item not found.");
	} catch (\Exception $e) {
		// TODO: Error in keys
		return array("status" => "error", "errmessage" => $e->getMessage());
	}
}

function fetchInventoryData($user_id, $country, $aws_access_id, $aws_secret_key, $associate_id){
	global $conn;
	$productsResult = $conn->query("select * from products where block = 0 and user_id = ".$user_id." and product_id in (109415,109417,109428)");//and product_id in (SELECT DISTINCT product_id FROM `product_variants` WHERE user_id = 694 and price = 0 and deleted = 0)
	//$productsResult = $conn->query("select * from products where block = 0 and user_id = ".$user_id);
	if($productsResult->num_rows > 0){		
		while($productsRow = $productsResult->fetch_assoc()){
			$product_id = $productsRow['product_id'];
			$variantsResult = $conn->query("select * from product_variants where block = 0 and deleted = 0 and product_id = ".$product_id." and user_id = ".$user_id);
			$vcount = $variantsResult->num_rows;			
			if($vcount == 1){ // Single variant
				$variantsRow = $variantsResult->fetch_assoc();
				$asin = $variantsRow['asin'];				
				$oldqty = $variantsRow['quantity'];
				$oldprice = $variantsRow['price'];
				$oldsaleprice = $variantsRow['saleprice'];
				$currData = fetchOffersWithoutVariant($asin, $country, $aws_access_id, $aws_secret_key, $associate_id);
				addlog("fetchInventoryData: Response of ".$product_id." from fetchOffersWithoutVariant - ".json_encode($currData), "INFO");
				if(isset($currData['status']) && $currData['status'] == "success"){
					$newqty = $currData["data"]["quantity"];
					$newprice = $currData["data"]["price"];
					$newsaleprice = $currData["data"]["saleprice"];
					$quantityflag = 0;
					$priceflag = 0;
					if($oldqty != $newqty){
						$quantityflag = 1;
					} 
					if($oldprice != $newprice || $oldsaleprice != $newsaleprice){
						$priceflag = 1;
					}
					if($newqty == 0){
						$conn->query("update product_variants set quantity = 0, quantityflag = 1 where id = ".$variantsRow['id']." and user_id = ".$user_id);
					} else {
						if($quantityflag == 1 || $priceflag == 1){
							$conn->query("update product_variants set quantity = ".$newqty.", price = '".mysqli_real_escape_string($conn, $newprice)."', saleprice = '".mysqli_real_escape_string($conn, $newsaleprice)."', quantityflag = ".$quantityflag.", priceflag = ".$priceflag." where id = ".$variantsRow['id']." and user_id = ".$user_id);
						}
					}
				}
			} else if($vcount > 1){ // multiple variants
				$parentasin = $productsRow['parentasin'];
				if(strlen($parentasin) < 1){
					addlog("fetchInventoryData: parentasin missing - ".$product_id, "ERROR");
					continue;
				}
				$currData = fetchOffersWithVariant($parentasin, $country, $aws_access_id, $aws_secret_key, $associate_id);
				addlog("fetchInventoryData: Response of ".$product_id." from fetchOffersWithVariant - ".json_encode($currData), "INFO");
				if(isset($currData['status']) && $currData['status'] == "success"){
					while($variantsRow = $variantsResult->fetch_assoc()){
						$quantityflag = 0;
						$priceflag = 0;
						$asin = $variantsRow['asin'];
						$oldprice = $variantsRow['price'];
						$oldsaleprice = $variantsRow['saleprice'];
						$oldqty = $variantsRow['quantity'];
						if(array_key_exists($asin, $currData['data'])){
							$newqty = $currData["data"][$asin]["quantity"];
							$newprice = $currData["data"][$asin]["price"];
							$newsaleprice = $currData["data"][$asin]["saleprice"];
							if($oldqty != $newqty){
								$quantityflag = 1;
							} 
							if($oldprice != $newprice || $oldsaleprice != $newsaleprice){
								$priceflag = 1;
							}
							if($quantityflag == 1 || $priceflag == 1){
								$conn->query("update product_variants set quantity = ".$newqty.", price = '".mysqli_real_escape_string($conn, $newprice)."', saleprice = '".mysqli_real_escape_string($conn, $newsaleprice)."', quantityflag = ".$quantityflag.", priceflag = ".$priceflag." where id = ".$variantsRow['id']." and user_id = ".$user_id);
							}
						} else {
							if($oldqty > 0){
								$conn->query("update product_variants set quantity = 0, quantityflag = 1 where id = ".$variantsRow['id']." and user_id = ".$user_id);								
							}
						}
					}
				}
			} else {
				addlog("fetchInventoryData: No active variant found - ".$product_id, "ERROR");
				continue;
			}
		}
	}
}

function updateInventoryAndPrice($user_id, $token, $shopurl, $inventory_sync, $price_sync){
	global $conn;	
	$result = $conn->query("select * from product_variants where shopifyvariantid != '' and (quantityflag = 1 or priceflag = 1) and block = 0 and deleted = 0 and user_id = ".$user_id);
	if ($result->num_rows > 0) {
		$i = 0;
		while($row = $result->fetch_assoc()) {			
			$rowid = $row['id'];
			$shopifyvariantid = $row['shopifyvariantid'];
			$sku = $row['sku'];
			$quantity = $row['quantity'];
			$price = $row['price'];
			$saleprice = $row['saleprice'];
			if($saleprice == 0){
				$saleprice = $price;
			}
			$data = array();					
			if($inventory_sync == 1 && $price_sync == 1){
				$data = array(
						"variant"=>array(
							"id" => $shopifyvariantid,		
							/*"inventory_quantity" => $quantity,*/
							"price" => number_format($saleprice, 2, '.', '')
							/*"compare_at_price" => number_format($price, 2, '.', '')*/
						)
					);	
			} else {			
				if($price_sync == 1){
					$data = array(
							"variant"=>array(
								"id" => $shopifyvariantid,		
								"price" => number_format($saleprice, 2, '.', '')
								/*"compare_at_price" => number_format($price, 2, '.', '')*/
						)
					);
				} else {
					$data = array(
							"variant"=>array(
								"id" => $shopifyvariantid,		
							/*	"inventory_quantity"=>$quantity*/				
						)
					);
				}
			}			
			$res = updateShopifyVariant($token, $shopurl, $shopifyvariantid, $data, $rowid, $user_id);	
			if($res){
				$i++;
				$conn->query("update product_variants set quantityflag = 0, priceflag = 0 where id = ".$rowid);
				if($i > 50){
					echo "incommit";
					mysqli_commit($conn);
					$i = 0;
				}
			} else {
				//@mail("khariwal.rohit@gmail.com", "AAC:updateshopifyinventory - Error updating inventory - ".$rowid, serialize($response_arr));
				$conn->query("update product_variants set quantityflag = 0, priceflag = 0, deleted = 1 where id = ".$rowid);
			}
		}
		mysqli_commit($conn);
	}
}

function updateShopifyVariant($token, $shopurl, $variant_id, $data, $rowid, $user_id){
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
	}
	if( (strstr(($response_arr[0]), "200 OK")) || (strstr(($response_arr[1]), "200 OK")) || (strstr(($response_arr[2]), "200 OK")) ) {
		return true;
	}
	return false;
}

function deleteShopifyProduct($user_id, $token, $shopurl, $product_id){
	global $conn;
	$url = "https://".$shopurl."/admin/api/2021-07/products/".$product_id.".json";
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
	if( (strstr(($response_arr[0]), "200 OK")) || (strstr(($response_arr[1]), "200 OK")) || (strstr(($response_arr[2]), "200 OK")) ){
		$conn->query("update products set shopifyproductid = '', status ='Ready to Import' where user_id = ".$user_id." and shopifyproductid = '".$product_id."'");
		$conn->query("update product_variants set shopifyproductid = '', shopifyvariantid = '', status ='Ready to Import' where user_id = ".$user_id." and shopifyproductid = '".$product_id."'");
		$conn->query("delete from shopifyproducts where user_id = ".$user_id." and productid = '".$product_id."'");	
		return true;		
	} else {
		return false;	
	}
}

function addlog($message, $type){
	global $logfile;
	$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
	fwrite($logfile, $txt);
}
addlog("Execution Finished", "INFO");
fclose($logfile);
?>