<?php

set_time_limit(0);


use ApaiIO\ApaiIO;
use ApaationiIO\Configur\GenericConfiguration;
use ApaiIO\Operations\Lookup;

//require_once("includes/config.php");

$logfile = fopen("logs/witout_aws_key_sync_price.php.txt", "a+") or die("Unable to open log file!");
addlog("Execution Started", "INFO");

$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
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
}else{
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
			if(!$conn->query("UPDATE `crons` SET `isrunning` = '1' WHERE `id`='".$cronRow['id']."' AND `crontype`='without_aws_keys_sync_price'")){
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


$result = $conn->query("select * from users,setting where users.id = setting.user_id and users.plan > 2");
///   will be changed to :::  select * from users,setting where users.id = setting.user_id and users.plan > 1 and installationstatus = 1 and 	`inventory_sync` = 1
//addlog("starting first Execution of crowling","CRAWLING");
addlog($result->num_rows,"CRAWLING ROWS");
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {  
    	addlog("SELECT * FROM `amz_keys` WHERE `user_id`='".$row['user_id']."' AND `aws_secret_key` = '' ","AWS QUERY");
    	$aws_keyObj = $conn->query("select * from aws_keys where user_id='".$row['user_id']."' AND `aws_secret_key` = ''");
    	print_r($aws_keyObj);
    	if($aws_keyObj->num_rows == 0 ){
    		$user_id = $row['user_id'];
    		$userDetails = $row;
	        $created_at = $row['created_at'];        
	        $created_at_time = strtotime($created_at);
	        $daysdiff = 8;
	        $now = time();
	        $datediff = $now - $created_at_time;
		    $daysdiff = floor($datediff / (60 * 60 * 24));
		    $membershiptype = $row['membershiptype']; 

		    if($row['plan'] == 3){
		    	$per_user_sync_limit = 2000;
		    }else if($row['plan'] == 4){
		    	$per_user_sync_limit = 10000;
		    }
	        if($membershiptype == 'free' && $daysdiff > 7){
				continue;
	        }else{
	            $per_user_sync_limit = 100;
	        }  

	        addlog("Alloted limmit for user ".$user_id."is ".$per_user_sync_limit,"ALLOTED LIMIT"); 
	        echo "<br/>Alloted limmit for user ".$user_id."is ".$per_user_sync_limit."<br/>";   
	        $inventory_sync = $row['inventory_sync'];
	        $price_sync = $row['price_sync'];
			
	        mysqli_autocommit($conn, FALSE);
	        checkProductOnAmazon($userDetails);
			mysqli_commit($conn);	
    	}
    }

    $t2 = time();

    addlog("///////////////////////////////////////////////","TOTAL TIME IN ALL USERS CRAWLING");
	addlog($t2-$t1,"TOTAL TIME IN ALL USERS CRAWLING");
	addlog("///////////////////////////////////////////////","TOTAL TIME IN ALL USERS CRAWLING");
}

if(!$conn->query("UPDATE `crons` SET `isrunning` = '0' WHERE `id`='".$cronRow['id']."' AND `crontype`='without_aws_keys_sync_price'")){
	addlog("Error While Updating crons LOCK","LOCK UPDATION ERROR");
}else{
	addlog("lock updated","LOCK UPDATION");
}
mysqli_autocommit($conn, TRUE);

function crawlParallelNow($userDetails){
    global  $conn,$max_failures,$n_parallel_exit_nodes,$n_total_req,$switch_ip_every_n_req,$at_req,$per_user_sync_limit,$failedRequests;
    $user_id = $userDetails['user_id'];
    if($count = $conn->query("SELECT count(id) FROM `product_variants` WHERE `user_id`=$user_id and deleted = 0 limit 0,".$per_user_sync_limit)){
    	$value = $count->fetch_assoc();
    	addlog($value['count(id)'],"TOTAL PRODUCT");
    	addlog($user_id,"FOR USER");
    }
    $start = 0;
    $interval = 10;
    $limit = $interval;
    $counter = true;
    while($counter==true){
    	$urls = array();
	    $urlObj = $conn->query("SELECT * FROM `product_variants` WHERE `user_id`=$user_id and deleted = 0 limit $start,$interval");
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
		    for ($i=0; $i<$n_parallel_exit_nodes; $i++){
		    	if($uaObj = $conn->query("SELECT * FROM `user_agents` WHERE `id`=".rand(1,250))){
		    		if($ua = $uaObj->fetch_assoc()){
		    			$user_agents = $ua['ua_string'];
		    		}else{
		    			addlog("user_agents not found","USER AGENT NOT FOUND ERROR");
		    			echo "<br/>user_agents not found</br/>";
		    		}
		    	}
		        echo "<br/>call for Product ".$i."<br/>";
		        $client = new Client($mcurl);
		        $client->run($urls[$i]['detail_page_url']);
		    }
		    $mcurl->run($urls,$userDetails);
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

	    }else{
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
    public function run($urls,$userDetails){
        global $conn,$responseData,$markupenabled,$failedRequests;

        $token = $userDetails['token'];
		$membershiptype = $userDetails['membershiptype'];
	    $shopurl = $userDetails['shopurl'];
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
                	$product_data = getjsonrdata($content,$urls[$i]['detail_page_url']);
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
	                }else{
	                    $newData = json_decode($product_data,true);
	                    if($newData['availability'] == "Currently unavailable."){
	                    	addlog($urls[$i]['detail_page_url'],"URL");
	                    	addlog($newData['availability'],"AVAILABILITY");
	                        applyInventorySetting($conn,$userDetails['user_id'],$token, $shopurl, $inventory_sync, $price_sync,$urls[$i],$userDetails);
	                    }else{
	                    	addlog($urls[$i]['detail_page_url'],"URL");
	                    	addlog($newData['availability'],"AVAILABILITY");
	                        if($newData['list_price'] != $urls[$i]['price'] || $newData['price'] != $urls[$i]['saleprice']){
	                            if($markupenabled == true){
	                                $urls[$i]['price'] = applyPriceMarkup($newData['list_price'], $markuptype, $markupval, $markupround);
	                                $urls[$i]['saleprice'] = applyPriceMarkup($newData['price'], $markuptype, $markupval, $markupround);
	                            }else{
	                                $urls[$i]['price'] = $newData['list_price'];
	                                $urls[$i]['saleprice'] = $newData['price'];   
	                            }
	                          $conn->query("UPDATE `product_variants` SET `price`='".$newData['list_price']."' ,`saleprice`='".$newData['price']."' WHERE `user_id`= '".$userDetails['user_id']."' AND `id`=".$urls[$i]['id']);  
	                            updateInventoryAndPrice($conn,$userDetails['user_id'], $token, $shopurl, $inventory_sync, $price_sync,$urls[$i],$newData['availability'],$defquantity);
	                        }
	                    }
	                }
                }else{
                	//$failedRequests[$urls[$i]['id']] = $urls[$i]['detail_page_url'];
	                	echo "<br/>Blank Output of The Page <br/>";
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
		$newprice = round($newprice);
	}
	return $newprice;
}
	 
function checkProductOnAmazon($userDetails){
	global $markupenabled,$markuptype,$markupval,$defquantity,$markupround;
	
	$token = $userDetails['token'];
	$membershiptype = $userDetails['membershiptype'];
    $shopurl = $userDetails['shopurl'];
    $shopifylocationid = $userDetails['shopifylocationid'];
    $inventory_sync = $userDetails['inventory_sync'];  
    $price_sync = $userDetails['price_sync'];
            if(isset($userDetails['markupenabled']) && $userDetails['markupenabled'] == 1){
				$markupenabled = true;
			}
			if(isset($userDetails['markuptype']) && strlen($userDetails['markuptype']) > 0){
				$markuptype = $userDetails['markuptype'];
			}
			if(isset($userDetails['markupval'])){
				$markupval = $userDetails['markupval'];
			}
			if(isset($userDetails['markupround']) && $userDetails['markupround'] == 1){
				$markupround = true;
			}
			if(isset($userDetails['defquantity'])){
				$defquantity = $userDetails['defquantity'];
			}
    addlog($inventory_sync." ".$price_sync,"PRICE INVENTORY");
    
    crawlParallelNow($userDetails);
	
}

function applyInventorySetting($conn,$user_id,$token,$shopurl,$inventory_sync,$price_sync,$row,$userDetails){
       $rowid = $row['id'];
	   $shopifyvariantid = $row['shopifyvariantid'];
	   $shopifyproductid = $row['shopifyproductid'];
	   $sku = $row['sku'];
	   $quantity = $row['quantity'];
	   
	   
    		if($inventory_sync == 1){
    		    switch($userDetails['outofstock_action']){
        		    case "unpublish":
        		            $data = array(
        				        "product" => array(
        				            "id" => $row['shopifyproductid'],
        				            "published"=> false,
        				            "variants"=>array(
            							"id" => $shopifyvariantid,		
            						)
        				        )
        					);
        				    $res = updateShopifyProduct($token, $shopurl, $shopifyproductid, $data, $rowid, $user_id);	
        				    if($res){
                			    addlog(json_encode($data),"DATA UPDATED TO INVENTORY");
                				$i++;
                				$conn->query("update product_variants set quantityflag = 0, priceflag = 0 where id = ".$rowid);
                				if($i > 50){
                					echo "incommit";
                					mysqli_commit($conn);
                					$i = 0;
                				}
                			} else {
                				//@mail("khariwal.rohit@gmail.com", "AAC:updateshopifyinventory - Error updating inventory - ".$rowid, serialize($response_arr));
                				addlog(json_encode($data),"DATA UPDATION TO INVENTORY FAILED");
                				$conn->query("update product_variants set quantityflag = 0, priceflag = 0, deleted = 1 where id = ".$rowid);
                			}
        		        break;
        		    case "outofstock":
        		            $data = array(
        				        "product" => array(
        				            "id" => $row['shopifyproductid'],
        				            "variants"=>array(
            							"id" => $shopifyvariantid,
            							"inventory_quantity" => '0',
            						)
        				        )
        					);

        		            $res = updateShopifyProduct($token, $shopurl, $shopifyproductid, $data, $rowid, $user_id);	
                			if($res){
                			    addlog(json_encode($data),"DATA UPDATED TO INVENTORY");
                				$i++;
                				$conn->query("update product_variants set quantityflag = 0, priceflag = 0 where id = ".$rowid);
                				if($i > 50){
                					echo "incommit";
                					mysqli_commit($conn);
                					$i = 0;
                				}
                			} else {
                				//@mail("khariwal.rohit@gmail.com", "AAC:updateshopifyinventory - Error updating inventory - ".$rowid, serialize($response_arr));
                				addlog(json_encode($data),"DATA UPDATION TO INVENTORY FAILED");
                				$conn->query("update product_variants set quantityflag = 0, priceflag = 0, deleted = 1 where id = ".$rowid);
                			}
        		        break;
        		    case "delete";
        		        deleteShopifyProductVariant($token,$shopurl,$shopifyproductid,$shopifyvariantid);
        		        break;
        		    default :
        		        
        		        break;
        		}
    		}            
}

function deleteShopifyProductVariant($token,$shopurl,$product_id,$shopifyvariantid){
        addlog("deleting product variant","DELETE VARIANT");
	    addlog($token,"INFO");
	    $url = "https://".$shopurl."/admin/api/2021-07/products/$product_id.json";
	    addlog($url,"PROUCT DELETE URL");
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token, 'Content-Type: application/json; charset=utf-8'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
		//curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec ($curl);
		curl_close ($curl);
        
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        addlog($header,"INFO");
        //addlog($body,"INFO");
        
        //print_r($response);
		$response_arr = explode("\n",$response);

		if( (strstr(($response_arr[0]), "200 OK")) || (strstr(($response_arr[1]), "200 OK")) || (strstr(($response_arr[2]), "200 OK")) ){
			$product_json = end($response_arr);
			$product_arr = json_decode($product_json, true);
			$product_arr = $product_arr["product"];
			return 'Deleted';
		} else {
			//print_r($data);
			//print_r($response_arr);
			addlog("Error DELETING PRODUCT VARIANT", "ERROR");
		}
		return $response;
	}

function updateInventoryAndPrice($conn,$user_id, $token, $shopurl, $inventory_sync, $price_sync,$row,$availability,$defquantity){	
    addlog("updateInventoryAndPrice","RUNNING");
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
			if($availability == "Currently unavailable."){
			    if($inventory_sync == 1 && $price_sync == 1){
    				$data = array(
    						"variant"=>array(
    							"id" => $shopifyvariantid,		
    							"inventory_quantity" => $quantity,
    							"price" => number_format(0, 2, '.', '')
    							/*"compare_at_price" => number_format(0, 2, '.', '')*/
    						)
    					);	
    			} else {			
    				if($price_sync == 1){
    					$data = array(
    							"variant"=>array(
    								"id" => $shopifyvariantid,
    								"inventory_quantity" => $quantity,
    								"price" => number_format(0, 2, '.', '')
    								/*"compare_at_price" => number_format(0, 2, '.', '')*/
    						)
    					);
    				} else {
    					$data = array(
    							"variant"=>array(
    								"id" => $shopifyvariantid,
    								"inventory_quantity" => $quantity,
    						)
    					);
    				}
    			}   
			}else{
			    if($quantity == 0){
    			    $quantity = $defquantity;
    			}
			    if($inventory_sync == 1 && $price_sync == 1){
    				$data = array(
    						"variant"=>array(
    							"id" => $shopifyvariantid,		
    							"inventory_quantity" => $quantity,
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
    								"inventory_quantity"=>$quantity				
    						)
    					);
    				}
    			}
			}
			$res = updateShopifyVariant($token, $shopurl, $shopifyvariantid, $data, $rowid, $user_id);	
			if($res){
			    addlog(json_encode($data),"DATA UPDATED TO INVENTORY");
				$i++;
				$conn->query("update product_variants set quantityflag = 0, priceflag = 0 where id = ".$rowid);
				if($i > 50){
					echo "incommit";
					mysqli_commit($conn);
					$i = 0;
				}
			} else {
				//@mail("khariwal.rohit@gmail.com", "AAC:updateshopifyinventory - Error updating inventory - ".$rowid, serialize($response_arr));
				addlog(json_encode($data),"DATA UPDATION TO INVENTORY FAILED");
				$conn->query("update product_variants set quantityflag = 0, priceflag = 0, deleted = 1 where id = ".$rowid);
			}
}
function updateShopifyInventory($token, $shopurl, $inventory_item_id, $location_id, $quantity,$user_id,$conn,$rowid){
	    //addlog("entered to inventory funtion<br/>","INFO");
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
		//addlog("response Generated <br/>","INFO");
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
function updateShopifyVariant($token, $shopurl, $variant_id, $data, $rowid, $user_id){
    addlog("update ShopifyVariant","METHOD EXECUTION");
	$url = "https://".$shopurl."/admin/api/2021-07/variants/".$variant_id.".json";
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

function productOutOfStock($product_url){
    addlog("Checking Out Of Stock","PRODUCT OUT OF STOCK");
	//$product_url = "https://www.amazon.com/dp/B07JNDD4QQ/ref=sspa_dk_detail_2?psc=1&pd_rd_i=B07JNDD4S7&pd_rd_w=9kLRo&pf_rd_p=21517efd-b385-405b-a405-9a37af61b5b4&pd_rd_wg=Zvx2E&pf_rd_r=H9YYNB8Q2Z45FMFYX4A7&pd_rd_r=9faffec4-10bf-11e9-85cd-a9b33ba877cb";//Product not Available link
	//$product_url = "https://www.amazon.com/Loopy-Strings-Organic-Catnip-Field/dp/B0773YL8BM/ref=sr_1_3?s=pet-supplies&ie=UTF8&qid=1546838930&sr=1-3&keywords=bag&refinements=p_n_availability%3A2661601011%2Cp_89%3AWeebo+Pets%7CFrom+The+Field";
	$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com/?key=bccfd6a1043eeef4b878ab667efac22b&url=".urlencode($product_url));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  "Accept: application/html"
		));

		$response = curl_exec($ch);
		echo $response;
		curl_close($ch);
        return getjsonrdata($response,$product_url);
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
		$titleBlock = $doc->getElementById('title');
    	//$dataArr['Title'] = $xp->evaluate('string(.//*[@class="a-size-extra-large"])', $titleBlock);
	    $dataArr['Title'] = trim( $xp->evaluate('string(.//*[@class="a-size-large"])', $titleBlock) );
	   	addlog($dataArr['Title'],"TITLE");	
	   	if(strlen($dataArr['Title'])==0){
	   		$dataArr['Title'] = trim($xp->evaluate('string(//*[@id="productTitle"])', $titleBlock));
	   		if(strlen($dataArr['Title'])==0){
	   			addlog("No Data Available <br/>","INFO");
	   			return null;
	   		}
	   	}

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
	   	  addlog($pricediv,"PRICE INFO");
	   	  $salepricediv = trim($pricediv[0]);
	   	}
	    addlog($salepricediv,"SALE PRICE INFO");
	    echo $salepricediv;
	    if($salepricediv == ""){
	   	 $salepricediv =$xp->evaluate('string(//div[@id="cerberus-data-metrics"]//@data-asin-price)',$doc);
	   	  if(strpos($salepricediv, '-') !== false){
		   	  $pricediv = explode("-", $salepricediv);
		   	  addlog($pricediv,"INFO");
		   	  $salepricediv = trim($pricediv[0]);
	   	  }
	    }
	    addlog("after Sale price","SALE PRICE");
	    $dataArr['price'] = str_replace("$","",$salepricediv);
	   
	    $originalpricediv = $xp->evaluate('//td[contains(text(),"List Price") or contains(text(),"M.R.P") or contains(text(),"Price")]/following-sibling::td/text()', $doc);
	   
	    $dataArr['list_price'] = str_replace("$","",$salepricediv);
	    
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
	            $res1= preg_match_all('/"large":"(.*)"/U',$file,$match);
                if($res1){
                   $imageLargeArr= $match[1];
                 }
          
                 if(sizeof($imageLargeArr)>sizeof($imageArr)){
                   $imageArr = $imageLargeArr;
                  }
	        }
	    }
	    
	    foreach ($imageArr as $image) {
	     	$imagepipe = $imagepipe."|".$image;
	    }
	    
	   
	    $dataArr['high_resolution_image_urls'] =  $imagepipe;  //$imageArr[0]."|".$imageArr[1]."|".$imageArr[2]."|".$imageArr[3]."|".$imageArr[4]."|".$imageArr[5]."|".$imageArr[6]."|".$imageArr[7]."|".$imageArr[8]."|".$imageArr[9];
	    
	    
	    //if( !isset( $dataArr['Title'] ) || !isset( $dataArr['description'] ) || !isset( $dataArr['asin'] ) || !isset( $dataArr['bullet_points'] ) || !isset( $dataArr['price'] ) || !isset( $dataArr['list_price'] )  || !isset( $dataArr['category'] ) || !isset( $dataArr['brand'] ) || !isset( $dataArr['high_resolution_image_urls'] )       ){
	     //   return json_encode(array());
	    //}else{
	        return json_encode($dataArr);
	    //}
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
?>