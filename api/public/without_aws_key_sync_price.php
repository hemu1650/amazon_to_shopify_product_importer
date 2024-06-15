<?php

set_time_limit(0);
use ApaiIO\ApaiIO;
use ApaationiIO\Configur\GenericConfiguration;
use ApaiIO\Operations\Lookup;

//require_once("includes/config.php");

$logfile = fopen("logs/witout_aws_key_sync_price.txt", "a+") or die("Unable to open log file!");
addlog("Execution Started", "INFO");

$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
//$conn = new mysqli('localhost', 'root', '', 'test123');	
ini_set('memory_limit', '-1');
// Check connection
if ($conn->connect_error) {
	addlog("Database connection failed: " . $conn->connect_error, "ERROR");
	die("");
}


////////     GLOBAL VARIABLES    /////////
$per_user_sync_limit = 2000;/// To set limit of sync for users
$responseData = array();
$max_failures = 1;
$n_parallel_exit_nodes = 0;
$n_total_req = 0;
$switch_ip_every_n_req = 2;
$at_req = 0;
$failedRequests = array();  

$conn->autocommit(true);
//echo 'To enable your free eval account and get CUSTOMER, YOURZONE and '
//.'YOURPASS, please contact sales@luminati.io';
$username = 'lum-customer-hl_d56dacba-zone-static';
$password = 'uzdebqenv5mf';
$port = 22225;
$user_agent = [];
////////     GLOBAL VARIABLES    /////////

mysqli_set_charset($conn, "utf8");

$cronObj = $conn->query("SELECT * FROM `crons` WHERE `crontype` = 'without_aws_keys_sync_price'");
if(!$cronObj){
	addlog("Error While accessing loc details","DATABASE ERROR");
} else{
	if($cronObj->num_rows == 0){
		if(!$conn->query("INSERT INTO `crons`(`crontype`, `isrunning`, `counter`, `lastrun`) VALUES('without_aws_keys_sync_price',0,0,NOW())")){
			addlog("Error While creating crons entory","DATABASE QUERY ERROR");
		}
	}else{
		$cronRow = $cronObj->fetch_assoc();
		if($cronRow['isrunning']==1){
			addlog("The Cron is Already Running","LOCK BUSY");
			die();
		}else{
			//if(!$conn->query("UPDATE `crons` SET `isrunning` = '1' WHERE `id`='".$cronRow['id']."' AND `crontype`='without_aws_keys_sync_price'")){ //commented tushar and added a new line
			if(!$conn->query("UPDATE `crons` SET `isrunning` = '0' WHERE `id`='".$cronRow['id']."' AND `crontype`='without_aws_keys_sync_price'")){
				addlog("Error While Updating crons LOCK","LOCK UPDATION ERROR");
			}else{
				addlog("lock updated","LOCK UPDATION");
			}
		}
	}
}

$t1 = time();
addlog("///////////////////////////////////////////////","StartTime");
addlog(time(),"StartTime");
addlog("///////////////////////////////////////////////","StartTime");

$result = $conn->query("select u.*, s.inventory_sync, s.price_sync, s.markupenabled, s.markuptype, s.markupval, s.markupround, s.defquantity, s.outofstock_action, s.shopifylocationid, s.inventory_policy from users u, setting s where u.id = s.user_id and u.plan > 2 and u.installationstatus = 1 and (s.inventory_sync = 1 or s.price_sync=1) and u.id = 9272");
///   will be changed to :::  select * from users,setting where users.id = setting.user_id and users.plan > 1 and installationstatus = 1 and 	`inventory_sync` = 1
//addlog("starting first Execution of crowling","CRAWLING");
addlog($result->num_rows,"CRAWLING ROWS");

if($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {  
		print_r($row);
		$user_id = $row['id'];		
		$aws_keyQuery = $conn->query("select * from amz_keys where user_id = ".$user_id);				
		//exit();
    	if($aws_keyQuery->num_rows > 0){
			$aws_keyRow = $aws_keyQuery->fetch_assoc();
			echo 'aws_keyRow'.$aws_keyRow['aws_access_id'];
			if($aws_keyRow['aws_access_id'] != ''){
				continue;
			}
		}
        $created_at = $row['created_at'];        
        $created_at_time = strtotime($created_at);
        $daysdiff = 8;
        $now = time();
        $datediff = $now - $created_at_time;
	    $daysdiff = floor($datediff / (60 * 60 * 24));
	    $membershiptype = $row['membershiptype']; 
		$shopurl = $row['shopurl']; 
		$token = $row['token'];
		$id = $row['id'];
	    if($row['plan'] == 3){
	    	$per_user_sync_limit = 2000;
	    }else if($row['plan'] == 4){
	    	$per_user_sync_limit = 10000;
	    }
        if($membershiptype == 'free' && $daysdiff > 7){
			continue;
        }else{
          //  $per_user_sync_limit = 100;
        }  
		$location_id = $row['shopifylocationid'];
		if($location_id == ""){
			$location_id = getMainLocation($id, $shopurl, $token);
			if(!$location_id){
				echo 'no location id found';
				@mail("khariwal.rohit@gmail.com", "AAC:without_aws_keys Location ID missing", $id);
				continue;				
			}
			$row['shopifylocationid'] = $location_id;			
		}		
	    $inventory_sync = $row['inventory_sync'];
	    $price_sync = $row['price_sync'];
		$noOfVariantsQuery = $conn->query("select count(*) as cnt from product_variants where user_id = ".$user_id." and deleted = 0");
		$noOfVariantsRow = $noOfVariantsQuery->fetch_assoc();		
		//exit();//tushar added
		$noOfVariants = $noOfVariantsRow['cnt'];
		addlog("Alloted limmit for user ".$user_id."is ".$per_user_sync_limit,"ALLOTED LIMIT");
		if($noOfVariants > $per_user_sync_limit){
			addlog("Sync skipped because of limite exceeded - ".$user_id." is ".$noOfVariants,"ALLOTED LIMIT");
			continue;
		}
	   // mysqli_autocommit($conn, FALSE);
		crawlParallelNow($row);
		mysqli_commit($conn);	
    }
}

$t2 = time();
addlog("///////////////////////////////////////////////","TOTAL TIME IN ALL USERS CRAWLING");
addlog($t2-$t1,"TOTAL TIME IN ALL USERS CRAWLING");
addlog("///////////////////////////////////////////////","TOTAL TIME IN ALL USERS CRAWLING");


if(!$conn->query("UPDATE `crons` SET `isrunning` = '0' WHERE `id`='".$cronRow['id']."' AND `crontype`='without_aws_keys_sync_price'")){
	addlog("Error While Updating crons LOCK","LOCK UPDATION ERROR");
}else{
	addlog("lock updated","LOCK UPDATION");
}
mysqli_autocommit($conn, TRUE);

function crawlParallelNow($userDetails){	
	global  $conn, $max_failures, $n_parallel_exit_nodes, $n_total_req, $switch_ip_every_n_req, $at_req, $per_user_sync_limit, $failedRequests;
    $user_id = $userDetails['id'];	
    $start = 0;
    $interval = 1;
    $limit = $interval;
    $counter = true;
    while($counter == true){
	   	$urls = array();
		$urlObj = $conn->query("SELECT * FROM `product_variants` WHERE id = 299911 and `user_id`= $user_id and deleted = 0 limit $start, $interval");
		if($urlObj->num_rows > 0){
			addlog("CRAWLING ".$urlObj->num_rows." PRODUCTS","FOR USER : ".$user_id);
			echo "<br/> CRAWLING ".$urlObj->num_rows." PRODUCTS FOR USER : ".$user_id."<br/>";
	        while($urlData = $urlObj->fetch_assoc()){
	            $urls[] = $urlData;
	        }
	        //print_r($urls);

		    $mcurl = new Mcurl();
		    $max_failures = 1;
		    $n_parallel_exit_nodes = sizeof($urls);
		    $n_total_req = sizeof($urls);
		    $switch_ip_every_n_req = 2;
		    $at_req = 0;
		    $time = time();
		    for ($i = 0; $i < $n_parallel_exit_nodes; $i++){
				if($uaObj = $conn->query("SELECT * FROM `user_agents` WHERE `id`=".rand(1,250))){
		    		if($ua = $uaObj->fetch_assoc()){
		    			$user_agents = $ua['ua_string'];
		    		} else {
		    			addlog("user_agents not found","USER AGENT NOT FOUND ERROR");
		    			echo "<br/>user_agents not found</br/>";
		    		}
		    	}
		        echo "<br/>call for Product ".$i.'---'.$urls[$i]['detail_page_url']."<br/>";
		        $client = new Client($mcurl);
		        $client->run($urls[$i]['detail_page_url']);
		    }
		    $mcurl->run($urls, $userDetails);
		    $i = 0;
		    if(!$conn->commit()){
		        echo "<br/>Transection Commit Failed <br/>";
		    }
		    if($limit == $per_user_sync_limit){
		    	$counter = false;
		    }
		    $start = $limit;
		    $limit += $interval;
		    $time2 = time();
		    echo " <br/><h1>Total Time taken for Crowl and Scrapping : ";
		    echo $time2 - $time;
		    echo "</h1><br/>";
		    /// $failedRequests[$urls[$i]['id']] = $urls[$i]['detail_page_url'];		    
		    $conn->commit();
		    $conn->autocommit(true);

	    } else {
	    	$counter = false;
	    }
	}
}


class Mcurl
{
    private $mh;
    private $curl_handlers;
    public function __construct(){
        $this->mh = curl_multi_init();
        $this->curl_handlers = array();
    }
    public function __destruct(){
        curl_multi_close($this->mh);
    }
    public function async_get($curl_options, $handler){
        $curl = curl_init();
        curl_setopt_array($curl, $curl_options);
        $this->async_exec($curl, $handler);
    }
    private function async_exec($curl, $handler){
        $this->curl_handlers[(string)$curl] = $handler;
        curl_multi_add_handle($this->mh, $curl);
    }
    public function run($urls, $userDetails){
        global $conn, $responseData, $markupenabled, $failedRequests;
        $token = $userDetails['token'];
		$shopurl = $userDetails['shopurl'];
		$membershiptype = $userDetails['membershiptype'];
	    
	    $shopifylocationid = $userDetails['shopifylocationid'];
	    $inventory_sync = $userDetails['inventory_sync'];  
	    $price_sync = $userDetails['price_sync'];
	    $defquantity = $userDetails['defquantity'];
		
        $time = time();
        $active = 0;
        $mrc = 0;
        $i = 0;
        do {
            $mrc = curl_multi_exec($this->mh, $active);
        } while ($mrc > 0);//== CURLM_CALL_MULTI_PERFORM
        while ($active && $mrc == CURLM_OK) {
            $mrc = curl_multi_select($this->mh);
            do {
                $mrc = curl_multi_exec($this->mh, $active);
            } while ($mrc > 0);//== CURLM_CALL_MULTI_PERFORM
            $time2 = time();
            while ($info = curl_multi_info_read($this->mh)) {
                $curl = $info['handle'];
                $key = (string)$curl;
                $handler = $this->curl_handlers[$key];
                unset($this->curl_handlers[$key]);
                $info = curl_getinfo($curl);
                $http_code = $info['http_code'];
                $content = curl_multi_getcontent($curl);
                //echo "http_code:$http_code curl:$curl content: $content\n";
                //$handler($http_code, $content);
                //echo $content;
                if($content != ""){
					echo $content;
                	$product_data = getjsonrdata($content, $urls[$i]['detail_page_url']);
					
	                if($product_data == null || $product_data == "" ){
	                    echo $urls[$i]['detail_page_url']."<br/>";
	                    if(!$conn->query("INSERT into `sync_failure_request`(variant_id,url,status) VALUES('$i','".$urls[$i]['detail_page_url']."','0')")){
				    		addlog("error in Updating request failure report","DATABASE QUERY ERROR");
				    		addlog("INSERT into `sync_failure_request`(variant_id,url,status) VALUES('$i','".$urls[$i]['detail_page_url']."','0')","QUERY FOR ERROR");
				    	}
	                    //echo $content;
		                    $doc = new \DOMDocument();
		                    $doc->loadHTML($content);
		                    $img = $doc->getElementsByTagName('img');
		                    foreach ($img as $key => $value) {
		                        $res = preg_match_all('/src="(.*)"/U', $doc->saveHTML($value), $matches);
		                        if($res){
		                            if($matches[1][0] == "https://m.media-amazon.com/images/G/01/error/title._TTD_.png"){
		                                echo "<br/>Blocked By Amazon <br/>";
		                            }else{
		                                print_r($matches[1]);
		                            }
		                        }
		                        echo "<br/>";
		                    }
                            $doc = null;
	                } else {
	                    $newData = json_decode($product_data, true);
						print_r($newData);
						$isAvailable = true;
						$newpriceArr = array();
	                    if($newData['availability'] == "Currently unavailable."){
	                    	addlog($urls[$i]['detail_page_url'],"URL");
	                    	addlog($newData['availability'],"AVAILABILITY");
	                        $isAvailable = false;
	                    } else {
	                    	addlog($urls[$i]['detail_page_url'],"URL");
	                    	addlog($newData['availability'], "AVAILABILITY");
	                        //if($newData['list_price'] != $urls[$i]['price'] || $newData['price'] != $urls[$i]['saleprice']){
								$newpriceArr = array("saleprice" => $newData['price'], "price" => $newData['list_price']);								
	                       // }
	                    }
						updateInventoryAndPrice($userDetails, $urls[$i], $newpriceArr, $isAvailable);
	                }
                }else{
						echo 'content from curl_multi_exec function is NULL. so trying getting content using scraperAPI';
						echo '<hr/><hr/><hr/><h1>Crawling on '.$urls[$i]['detail_page_url'].':</h1><br/>';
					$content = get_html_scraper_api_content($urls[$i]['detail_page_url']);
					//$content = json_decode($content);					
											
						//$product_data = $content;												
						$isAvailable = true;
						$newpriceArr = array();
						if($content['quantity'] == 0){
							addlog($urls[$i]['detail_page_url'],"URL");
							addlog($content['quantity'],"AVAILABILITY");
							$isAvailable = false;
						}else{
							addlog($urls[$i]['detail_page_url'],"URL");
							addlog($content['quantity'], "AVAILABILITY");
							//if($content['list_price'] != $urls[$i]['price'] || $content['price'] != $urls[$i]['saleprice']){
								$newpriceArr = array("saleprice" => $content['price'], "price" => $content['list_price']);								
						// }
						}
						echo '<br/>...Crawled and got content by scrapperAPI<br/>';
						updateInventoryAndPrice($userDetails, $urls[$i], $newpriceArr, $isAvailable);
						//$failedRequests[$urls[$i]['id']] = $urls[$i]['detail_page_url'];
																		
	            }
                //$responseData[$urls[$i]] = $product_data; 
                /*if($product_data == "" || $product_data == null){
                   $product_data = "Product Data Not Found For Url: "."https://www.amazon.com/dp/".$urls[$i];
                }
                $product_data = str_replace("'","\'",$product_data);
                if(!$conn->query("INSERT INTO `crawl_module_test`(`variant_id`, `scrapdata`) VALUES ('".$i."','".$product_data."')")){
                   echo " <br/><h1 style='color:red;'>Error While Uploading to DataBase</h1><br/>";
                   print_r($product_data);
               }*/
                $i++;
                //echo "<br/><br/>";
                curl_multi_remove_handle($this->mh, $curl);
                curl_close($curl);
            }
        }
            echo " <br/><h1>Total Time taken for Crowl : ";
            echo $time2 - $time;
            echo "</h1><br/>";
            //$info = curl_multi_info_read($this->mh);
            //echo " Size Of Response: ";
            //print_r($info);
    }
};

class Client
{
    public $super_proxy;
    public $session_id;
    public $fail_count;
    public $n_req_for_exit_node;
    public $mcurl;
    private $proxy, $auth;

    public function __construct($mcurl)
    {
        $this->session_id = "";
        $this->fail_count = 0;
        $this->n_req_for_exit_node = 0;
        $this->mcurl = $mcurl;
        $this->switch_session_id();
    }
    private function switch_session_id(){
        $this->session_id = mt_rand();
        #echo "switched session ID to: ".$this->session_id."\n\n";
        $this->n_req_for_exit_node = 0;
        $this->update_super_proxy();
    }
    private function update_super_proxy(){
        global $port, $username, $password;
        $this->fail_count = 0;
        $super_proxy = gethostbyname("session-".$this->session_id.".zproxy.lum-superproxy.io");
        $this->proxy = "http://".$super_proxy.":$port";
        $this->auth = "$username-session-".$this->session_id.":$password";
    }
    private function have_good_super_proxy(){
        global $max_failures;
        return $this->fail_count < $max_failures;
    }
    private function make_request($url){
        global $user_agent;
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_ENCODING => "UTF8",
            CURLOPT_SSL_VERIFYPEER => 1,
            CURLOPT_PROXY => $this->proxy,
            CURLOPT_PROXYUSERPWD => $this->auth,
            CURLOPT_USERAGENT => $user_agent,
        );
        $client = $this;
        $handler = function($http_code, $content) use ($client){
            $client->handle_response($http_code, $content,$url); };
        $this->mcurl->async_get($curl_options, $handler);
    }
    private function handle_response($http_code, $content,$url){
        if ($this->should_switch_exit_node($http_code, $content)){
           $this->switch_session_id();
           $this->fail_count++;
           $this->run_next($url);
        }else{
            // success or other client/website error like 404...
            echo "$content\n";
            $this->n_req_for_exit_node++;
            $this->fail_count = 0;
            $this->run($url);
        }
    }
    private function should_switch_exit_node($http_code, $content){
        return $content=="" ||
            $this->status_code_requires_exit_node_switch($http_code);
    }
    private function status_code_requires_exit_node_switch($code){
        if (!$code) // curl_multi timed out or failed
            return true;
        return $code==403 || $code==429 || $code==502 || $code==503;
    }
    private function run_next($url){
        global $switch_ip_every_n_req;
        if (!$this->have_good_super_proxy()){
            $this->switch_session_id();
            return;
        }
        if ($this->n_req_for_exit_node == $switch_ip_every_n_req)
            $this->switch_session_id();
        $this->make_request($url);
    }
    public function run($url){
        global $n_total_req, $at_req;
        if ($at_req++ < $n_total_req){
            $this->run_next($url);
        }
    }
};


function applyPriceMarkup($price, $markuptype, $markupval, $markupround){
	$newprice = $price;
	if($markuptype == "FIXED"){
		$newprice = $price + $markupval;
	} else {
		$newprice = $price + $price*$markupval/100;
	}
	if($markupround){
		$newprice = round($price) - 0.01;
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

function updateInventoryAndPrice($userRow, $variantRow, $priceArr, $isAvailable){
	
	echo '<br/><h1>Updating Price and Inventory</h1><br/>This is userId:';
	echo $userRow['id'];
	echo '<br/>variantrow : ';
	print_r($variantRow);
	echo '<br/>pricerow  : ';
	print_r($priceArr);
	echo '<br/>isavailable : '. $isAvailable.'<br/>';
	global $conn;	
	$user_id = $userRow['id'];
	$shopurl = $userRow['shopurl'];
	$token = $userRow['token'];
	$rowid = $variantRow['id'];
	$shopifyproductid = $variantRow['shopifyproductid'];
	$shopifyvariantid = $variantRow['shopifyvariantid'];
	$shopifyinventoryid = $variantRow['shopifyinventoryid'];
	$shopifylocationid = $variantRow['shopifylocationid'];
	if($shopifylocationid == ''){
		$shopifylocationid = $userRow['shopifylocationid'];
	}	
	
	// Update prices
	if($userRow['price_sync'] == 1 && isset($priceArr['price'])){
		
		echo '<br/><h2>------Updating shopify Price</h2><br/>';
		print_r($priceArr);

		$price = getAmount($priceArr['price']);
		$list_price = $price;
		if(isset($priceArr['list_price'])){
			$list_price = getAmount($priceArr['list_price']);
		}
		
		if($userRow['markupenabled'] == 1){
			$price = applyPriceMarkup($price, $userRow['markuptype'], $userRow['markupval'], $userRow['markupround']);
			$list_price = applyPriceMarkup($list_price, $userRow['markuptype'], $userRow['markupval'], $userRow['markupround']);
		}
		$data = array(
					"variant"=>array(
						"id" => $shopifyvariantid,		
						/*"compare_at_price" => number_format($list_price, 2, '.', ''),*/
						"price" => number_format($price, 2, '.', '')
						)
					);
		$res = updateShopifyVariant($token, $shopurl, $shopifyvariantid, $data, $rowid, $user_id);
		if($res){			
			$res = $conn->query("update product_variants set price = '".mysqli_real_escape_string($conn, $list_price)."', saleprice = '".mysqli_real_escape_string($conn, $price)."', priceflag = 0, quantity = 1 where user_id = '".$user_id."' and id = ".$rowid);				
		}
	}
	
	// Update quantity
	if($userRow['inventory_sync'] == 1){
		echo 'Inventory Sync is 1 for the user, so updating inventory : ';
		$inventory_policy = $userRow['inventory_policy'];
		$defquantity = $userRow['defquantity'];
		$outofstock_action = $userRow['outofstock_action'];
		if(!$isAvailable){
			if($outofstock_action == "delete"){
				echo 'deleting shopify product because of outofstock_action ';
				deleteShopifyProduct($user_id, $token, $shopurl, $shopifyproductid);
				return;
			} else if($outofstock_action == "unpublish"){
				echo '<br/>unpublishing shopify product because of outofstock_action ';
				$data = array(
        			"product" => array(
        				"id" => $shopifyproductid,
        					"published"=> false
        					)
        				);
				$res = updateShopifyProduct($token, $shopurl, $shopifyproductid, $data, $rowid, $user_id);	
				return;
			} else {
				$defquantity = 0;
			}
		}

		if($userRow['inventory_policy'] == 'shopify'){
			if($shopifyinventoryid == ''){
				$shopifyinventoryid = getInventoryId($user_id, $token, $shopurl, $shopifyvariantid);
				$conn->query("update product_variants set shopifyinventoryid = '".mysqli_real_escape_string($conn, $shopifyinventoryid)."' where user_id = ".$user_id." and id = ".$rowid);
			}
			if($shopifyinventoryid != ''){
				$res = updateShopifyInventory($rowid, $user_id, $token, $shopurl, $shopifyinventoryid, $shopifylocationid, $defquantity);
				if($res){
					$conn->query("update product_variants set quantityflag = 0 where id = ".$rowid);
				}
			}	
		}
	}	
}

function updateShopifyProduct($token, $shopurl, $product_id, $data, $rowid, $user_id){
    addlog("update ShopifyVariant","METHOD EXECUTION");
	$url = "https://".$shopurl."/admin/api/2021-07/products/".$product_id.".json";
	addlog($url,"SHOPIFY UPDATE URL");
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




function getjsonrdata($data,$producturl) {
	addlog("parsing data","GET JSON ARR");
	$doc = new \DOMDocument();
	$doc->recover = true;
	//$errors = libxml_get_errors();
    //print_r($errors);
    //$errors = null;
	libxml_use_internal_errors(true);
    
	$doc->loadHTML($data);
	$xp = new \DOMXPath($doc);
	$dataArr = array();
	$dataArr['url'] = $producturl;
	addlog($dataArr['url'],"URL");		   		    

	$asinBlock = $doc->getElementById('productDetails_detailBullets_sections1'); //->childNodes;
	$dataArr['asin'] =  $xp->evaluate('(string(.//tr[3]/td))', $asinBlock);
	$asinBlock  =     $doc->getElementById('ASIN');
	if($asinBlock != null){
		$dataArr['asin'] =  $asinBlock->getAttribute('value');
	}
	  
	$values =  $dataArr['asin'];
	$salepricediv = $xp->evaluate('string(//span[contains(@id,"ourprice") or contains(@id,"saleprice") or contains(@id,"priceblock_ourprice") or contains(@id,"buyNew_noncbb") or contains(@id,"priceblock_dealprice")]/text())',$doc);
	if(strpos($salepricediv, '-') !== false){
		$pricediv = explode("-", $salepricediv);
        $salepricediv = trim($pricediv[0]);
	}
    if($salepricediv == ""){
		$salepricediv =$xp->evaluate('string(//div[@id="cerberus-data-metrics"]//@data-asin-price)',$doc);
        if(strpos($salepricediv, '-') !== false){
			$pricediv = explode("-", $salepricediv);
			$salepricediv = trim($pricediv[0]);
		}
	}
    $dataArr['price'] = $salepricediv;
    if($dataArr['price'] == ''){
		$salepricediv =$xp->evaluate('string(//*[@id="buyNewSection"]/a/h5/div/div[2]/div/span[2])',$doc);
        if(strpos($salepricediv, '-') !== false){
			$pricediv = explode("-", $salepricediv);
            $salepricediv = trim($pricediv[0]);
		}
	}
    $dataArr['price'] = $salepricediv;
    if($dataArr['price'] == ""){
		$salepricediv =$xp->evaluate('string(//*[@id="olp-upd-new"]/span/a/text())',$doc);
        $salepricediv = explode("$",$salepricediv);
        if( isset($salepricediv[1]) ) {
			$dataArr['price'] = "$ ".$salepricediv[1];    
		}
	}
    // fetch price from comparison table
    if($dataArr['price'] == ""){
		$All = [];
		$tables = $doc->getElementById('HLCXComparisonTable');
        if($tables) {
			$tr = $tables->getElementsByTagName('tr'); 
			foreach ($tr as $element1) {        
				for ($i = 0; $i < count($element1); $i++) {
					$id = $element1->getAttribute('id');
					if($id == 'comparison_price_row') {
						$price = $element1->getElementsByTagName('td')->item(0)->textContent; 
			            $dataArr['price'] = "$ ".substr($price, strpos($price, "$") + 1);
                        break;
					}
				}
			}
		}           
	}
    
	$originalpricediv = $xp->evaluate('string(//td[contains(text(),"List Price") or contains(text(),"M.R.P") or contains(text(),"Price")]/following-sibling::td/text())', $doc);
        
    if(trim($originalpricediv) == ''){
       $dataArr['list_price'] = $dataArr['price'];
    }else{
       $dataArr['list_price'] = $originalpricediv;
    }
	    
	$availability = $doc->saveHTML($doc->getElementById('availability'));
	$availability = str_replace("\n","",$availability);
	$availability = str_replace("\t","",$availability);
	if($availability != ''){
		$res = preg_match_all('/<span\sclass=".*">(.*)<\/span>/sU',$availability,$matches);
    	if($res){
    		addlog(json_encode($matches[1]),"Availability Matches");
    	    $availability = trim($matches[1][0]);
    	    addlog($availability,"Availability Matches");
		}
	}
	
	$dataArr['availability'] = $availability;
	addlog($availability,"AVAILABILITY");
    return json_encode($dataArr);
}

/*function addlog($message, $type){
	if(is_array($message)){
		$message = json_encode($message);
	}
	global $logfile;
	$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
	fwrite($logfile, $txt);
}*/

function addlog($message, $type){
	if(is_array($message)){
	$message = json_encode($message);
	}
	global $logfile;
	$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
	flock($logfile, LOCK_EX);
	fwrite($logfile, $txt);
	flock($logfile, LOCK_UN);
}


addlog("Execution Finished", "INFO");
fclose($logfile);

function updateShopifyVariant($token, $shopurl, $variant_id, $data, $rowid, $user_id){
	echo '<br/><h2>------Updating shopify variant</h2><br/>';
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
	//echo 'Response of updateShopifyVariant (Price )Function : '.$response.'<br/>';//added tushar test

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
	if( (strstr(($response_arr[0]), "HTTP/2 200")) || (strstr(($response_arr[1]), "HTTP/2 200")) || (strstr(($response_arr[2]), "HTTP/2 200")) ) {
		return true;
	} else if(strstr(($response_arr[0]), "HTTP/1.1 404 Not Found")){
		$conn->query("update product_variants set quantityflag = 0, priceflag = 0, deleted = 1 where user_id = ".$user_id." and id = ".$rowid);
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
		return true;		
	} else {
		return false;	
	}
}

// Start adding functions to handle multiple location
function getMainLocation($user_id, $shopurl, $token){
	//echo 'i am inside function getMainaLocation()';//tushar added for testing purpose
	global $conn;
	$result = $conn->query("select * from locations where user_id = ".$user_id." order by shopifylocationid * 1");
	if($result->num_rows > 0){
		$row = $result->fetch_assoc();
		$location_id = $row['shopifylocationid'];
		//echo 'this is location id-'.$location_id;//added tushar
		return $location_id;
	} else{
		// Try to fetch all possible locations
		$location_id = fetchLocations($user_id, $shopurl, $token);
		//echo 'this is location id fetched by function fetchLocatiuon()-'.$location_id;//added tushar
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

	$apiurl = "https://".$shopurl."/admin/api/2021-07/locations.json";
	$session = curl_init();
	curl_setopt($session, CURLOPT_URL, $apiurl);
	curl_setopt($session, CURLOPT_HTTPGET, 1);
	curl_setopt($session, CURLOPT_HEADER, false);
	curl_setopt($session, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token));
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);
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

function getInventoryId($user_id, $token, $shopurl, $shopifyvariantid){
	global $conn;
	$apiurl = "https://".$shopurl."/admin/api/2021-07/variants/".$shopifyvariantid.".json";
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
	return "";
}

function updateShopifyInventory($rowid, $user_id, $token, $shopurl, $inventory_item_id, $location_id, $quantity){
	global $conn;	
	$data = array(
        "location_id" => $location_id, 
        "inventory_item_id" => $inventory_item_id, 
        "available" => $quantity
    );
	echo '</br>Data passing to update shopify inventory: <br/>';
	print_r($data);
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

	// echo '<br/><br/>response of updateshopifyInventory';//added tushar to test
	// echo $response;
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
		return true;
	} else if((strstr(($response_arr[0]), "HTTP/1.1 403 Forbidden")) || (strstr(($response_arr[0]), "HTTP/1.1 422 Unprocessable Entity"))) {
		$new_location_id = getLocationId($token, $shopurl, $inventory_item_id);
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
				return false;
			}
		}
	}
	return false;
}
	
function getLocationId($token, $shopurl, $inventory_item_id){		
	$apiurl = "https://".$shopurl."/admin/api/2021-07/inventory_levels.json?inventory_item_ids=".$inventory_item_id;
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
		}			
	}		
	return false;
}
function get_html_scraper_api_content($url) {
	echo '<br/><br/>inside get html scraper api content<br/><br/>';
		
	$ch = curl_init();//http://api.scraperapi.com/?key=bccfd6a1043eeef4b878ab667efac22b&url=
	//curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com/?key=bccfd6a1043eeef4b878ab667efac22b&url=".urlencode($url));
	curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com/?key=e6585b7c2f1d8cc1842f3a77b4187ad0&url=".urlencode($url));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	  "Accept: application/json"
	));

	$response = curl_exec($ch);
	curl_close($ch);
	//Log::info("closed");
	//echo $response;
	return getjsonrdata_2($response,$url);
}

function getjsonrdata_2($data,$url) {
	//Log::info("in json code");
	echo '<br/><br/>inside getjsonrdata_2<br/><br/>';
   $doc = new \DOMDocument("1.0", "utf-8");
   $doc->recover = true;
   $errors = libxml_get_errors();
   $saved = libxml_use_internal_errors(true);      
   if(strlen($data)<200){
	   return null;
   }
   $doc->loadHTML($data);
   $xp = new \DOMXPath($doc);
   $dataArr = array();        
   //echo $data;
   $price = 0;
   $list_price = 0;
   preg_match('/<span\s*id="priceblock_ourprice"\s*class="a-size-medium\s*a-color-price\s*priceBlockBuyingPriceString">(.*?)<\/span>/ms',$data,$price_str);
   if(!$price_str){
	   preg_match('/<span\s*id="priceblock_dealprice"\s*class="a-size-medium\s*a-color-price\s*priceBlockDealPriceString">(.*?)<\/span>/ms',$data,$price_str);
	   if(!$price_str){
		   preg_match('/<span\s*id="price_inside_buybox"\s*class="a-size-medium\s*a-color-price">(.*?)<\/span>/ms',$data,$price_str);
		   if(!$price_str){
			   preg_match('/<span\s*id="priceblock_saleprice"\s*class="a-size-medium\s*a-color-price\s*priceBlockSalePriceString">(.*?)<\/span>/ms',$data,$price_str);
			   if($price_str){
				   preg_match('/([0-9]+\.[0-9]+)/', $price_str[1], $p_str);
				   $price = trim($p_str[1]);
			   }else{
				   preg_match('/<span\s*class=\'a-color-price\'>(.*?)<\/span>/ms',$data,$price_str);
				   if($price_str){
					   preg_match('/([0-9]+\.[0-9]+)/', $price_str[1], $p_str);
					   if($p_str){$price = trim($p_str[1]);}else{$price = trim(str_replace(chr(0xE2).chr(0x82).chr(0xAC), "", $price_str[1]));$price = trim(str_replace(',','',$price));}
				   }
			   }
		   }else{
			   preg_match('/([0-9]+\.[0-9]+)/', $price_str[1], $p_str);
			   if($p_str){$price = trim($p_str[1]);}else{$price = trim(str_replace(chr(0xE2).chr(0x82).chr(0xAC), "", $price_str[1]));$price = trim(str_replace(',','',$price));}
		   }
	   }else{
		   preg_match('/([0-9]+\.[0-9]+)/', $price_str[1], $p_str);
		   if($p_str){$price = trim($p_str[1]);}else{$price = trim(str_replace(chr(0xE2).chr(0x82).chr(0xAC), "", $price_str[1]));$price = trim(str_replace(',','',$price));}
	   }
   }else{
	   preg_match('/([0-9]+\.[0-9]+)/', $price_str[1], $p_str);
	   if($p_str){$price = trim($p_str[1]);}else{$price = trim(str_replace(chr(0xE2).chr(0x82).chr(0xAC), "", $price_str[1]));$price = trim(str_replace(',','',$price));}
   }        
   preg_match('/<span\s*class="priceBlockStrikePriceString\s*a-text-strike">(.*?)<\/span>/ms',$data,$list_str);
   if($list_str){
	   preg_match('/([0-9]+\.[0-9]+)/', $list_str[1], $l_str);
	   $list_price = trim($l_str[1]);          
   }
   $dataArr['price'] = $price;
   $dataArr['list_price'] = $list_price;
   if($list_price == 0){
	   $dataArr['list_price'] = $price;
   }


   // Checking availability and quantity
   preg_match('/<div\s*id="availability"\s*class="a-section\s*a-spacing-none">\s*<span\s*class="a-size-medium\s*a-color-success">(.*?)<\/span>\s*<\/div>/ms',$data,$in_stock_str);
   if($in_stock_str){
	   $quantity = 1;
	   $dataArr['quantity'] = $quantity;
   }else{
	   preg_match('/<div\s*id="availability"\s*class="a-section\s*a-spacing-base">\s*<span\s*class="a-size-medium\s*a-color-price">(.*?)<\/span>/ms',$data,$in_stock_str);
	   if($in_stock_str && trim($in_stock_str[1]) == 'Currently unavailable.'){
		   $quantity = 0;
		   $dataArr['quantity'] = $quantity;
	   }else{
		   preg_match('/<div\s*id="availability"\s*class="a-section\s*a-spacing-base">\s*<span\s*class="a-size-medium\s*a-color-state">(.*?)<\/span>/ms',$data,$in_stock_str);
		   if($in_stock_str && trim($in_stock_str[1]) == 'Temporarily out of stock.'){
			   $quantity = 0;
			   $dataArr['quantity'] = $quantity;
		   }else{
			   $quantity = 1;
			   $dataArr['quantity'] = $quantity;
		   }
	   }
   }
   
   
   echo '<h4>This is dataArr[] : </h4>';
   print_r($dataArr);

   echo '<h4>End of DataArr[]</h4><br/>';
   return $dataArr;

}
?>