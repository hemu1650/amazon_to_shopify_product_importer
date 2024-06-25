<?php
$logfile = fopen("logs/updateShopifyImport.txt", "a+") or die("Unable to open log file!");

set_time_limit(0);

$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
	if(!$conn){
		die("connection error");
	}
	
addlog("update to shopify initiated","INFO");
$cronQuery = $conn->query("select isrunning from crons where crontype = 'applypricingrule'");
	$cronrow = $cronQuery->fetch_assoc();
	if($cronrow['isrunning'] == 1){
		@mail("pankajnaran81@gmail.com", "applypricingrule: Cron already running", "applypricingrule: Cron already running");
		die("");
	}

$conn->query("update crons set lastrun = now(), isrunning = 1 where crontype = 'applypricingrule'");


$resulttop = $conn->query("SELECT*FROM `setting` WHERE `change_status`=0");
addlog("update to shopify users".$resulttop->num_rows,"INFO");

while($data = $resulttop->fetch_assoc()){
    $user_id = $data['user_id'];
    $resrows = $conn->query("SELECT * FROM `products` WHERE user_id='".$user_id."'"."and `status`='Imported' and shopifyproductid !=''");
     addlog("processing apply rules cron for ".$user_id,"INFO");
    while($productRow = $resrows->fetch_assoc()){
               //print_r($productRow);
        echo "<br/>";
        insertToShopify($user_id,$productRow,$conn);
        echo "end <br/>";
    }
    if(!$conn->query("UPDATE `setting` SET `change_status`=1 where `id` ='".$data['id']."'")){
        echo "Error in changing setting status";
        addlog("update to shopify cron setting table updation failed","ERROR");
    }
    else{
        addlog("update to shopify cron completed successfully chaned to 1 again","INFO");
    }
    
    
}
$conn->query("update crons set isrunning = 0 where crontype = 'applypricingrule'");


function insertToShopify($user_id,$productObject,$conn){
		addlog("inserting to shopify: " . $user_id, "INFO");
		$currUser = getUser($user_id,$conn);
		addlog("parsing users: " . $currUser->id, "INFO");
		//echo(json_encode($currUser));
		$settingObject = getSettings($user_id,$conn);
		addlog("parsing seting: " . $currUser->id, "INFO");
		$published = false;		
		$tags = array();
		$vendor = "";
		$product_type = "";
		$inventory_policy = null;
		$defquantity = 1;
		$markupenabled = 0;
		$markuptype = 'FIXED';
		$markupval = 0;
		$markupvalfixed = 0;
		$markupround = 0;
		$location_id = "";
		if($settingObject){
			$tags = $settingObject['tags'];
			if(strlen($tags) > 0){
				$tags = explode(",", $tags);
			} else {
				$tags = array();
			}
			if($settingObject['inventory_policy']!="NO"){
				$inventory_policy = $settingObject['inventory_policy'];
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
			if(isset($settingObject['markupvalfixed'])){
				$markupvalfixed = $settingObject['markupvalfixed'];
			}
			if(isset($settingObject['markupround']) && $settingObject['markupround'] == 1){
				$markupround = true;
			}
		}
		
		$shopurl = $currUser['shopurl'];
		$token = $currUser['token'];
		$product_id = $productObject['product_id'];
		$title = $productObject['title'];
		
		
		        $variantResult = $conn->query("select * from product_variants where product_id = ".$product_id." and user_id = ".$user_id);
				$noOfVariants = $variantResult->num_rows;
				
		$variantsArr = $variantResult; 
		$vCount = $variantResult->num_rows;
		if($vCount == 1){
			$variantObject = $variantResult->fetch_assoc();
			$productid = $variantObject['product_id'];
			$price = $variantObject['price'];
			$saleprice = $variantObject['saleprice'];
			$variant_id = $variantObject['id'];
			$shopifyvariantid = $variantObject['shopifyvariantid'];
			if($markupenabled){
			    if($user_id == 1823 || $user_id == 5718){
			        $price = applyPriceMarkup($price, $markuptype, $markupval, $markupround);
				    $saleprice = applyPriceMarkup($saleprice, $markuptype, $markupval, $markupround);
				    $price = $price + $markupvalfixed;
				    $saleprice = $saleprice + $markupvalfixed;
			    } else {
				    $price = applyPriceMarkup($price, $markuptype, $markupval, $markupround);
				    $saleprice = applyPriceMarkup($saleprice, $markuptype, $markupval, $markupround);
			    }
			}			
			$detail_page_url = $variantObject['detail_page_url'];
			addlog("select * from product_images where user_id = ".$user_id." and variant_id = ".$variantObject['id'],"INFO");
			
			$data = array(
					"variant"=>array(
						"id"=>$shopifyvariantid,
						"price"=>number_format($saleprice, 2, '.', ''),
					/*	"compare_at_price"=>number_format($price, 2, '.', ''),*/
						"inventory_policy"=>"deny",
                        "fulfillment_service"=> "manual",
                        "inventory_management"=> $inventory_policy
					)
				);
			if($user_id == 5718){
		        $data["variant"]["compare_at_price"] = "";
		    }
				addlog("$shopifyvariantid","SHOPIFY VARIANT");
			$response = updateShopifyProduct($token, $shopurl, $data,$shopifyvariantid);
			addlog("$shopifyvariantid","SHOPIFY VARIANT");
			if($response){	
				echo $shopifyproductid;
			} else {
                addlog("<br/><br/><h1>Error while excepting Response from shopify at line 245</h1>","INFO");
			}
		} else {
			addlog("No Variants Found","ERROR"); 
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
       $settingsResult = $conn->query("select * from setting where user_id = ".$user_id);
		if($settingsResult->num_rows > 0){
			while($settingsRow = $settingsResult->fetch_assoc()) {
				return $settingsRow;
							
			}
		}
	}
function applyPriceMarkup($price, $markuptype, $markupval, $markupround){
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
function updateShopifyProduct($token, $shopurl, $data,$variant_id){
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
    
        addlog($response,"INFO");
        
		$response_arr = explode("\n",$response);

		foreach($response_arr as $obj){
		    if (strpos($obj, 'X-Shopify-Shop-Api-Call-Limit') !== false) {
		        $tempArr = explode(":", $obj);
		        $climit = substr(trim(end($tempArr)), 0, -3); 
		        
		    } 
		    
		} if(intval($climit) > 35)
		   { sleep(5);
		    }
		
		if( (strstr(($response_arr[0]), "200 OK")) || (strstr(($response_arr[1]), "200 OK")) || (strstr(($response_arr[2]), "200 OK")) ){
			$product_json = end($response_arr);
			$product_arr = json_decode($product_json, true);
			$product_arr = $product_arr["product"];
			return $product_arr;
		} else {
			//print_r($data);
			//print_r($response_arr);
			//addlog("Error adding product with SKU - ".$product["product"]["variants"]["sku"].", Err Details: ".serialize($response_arr), "ERROR");
		}
		return null;
	}
	function addlog($message, $type){
		global $logfile;
		$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
		fwrite($logfile, $txt);
	}
	
?>