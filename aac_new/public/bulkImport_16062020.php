<?php
ini_set('memory_limit','2048M');
$logfile = fopen("logs/bulkImport.txt", "a+") or die("Unable to open log file!");
addlog("bulkImport Initiated","INFO");
set_time_limit(0);
	//include "config.php";
	$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');	
	$failed = array();
	
	   if($conn){
        addlog('Database connected',"INFO");
    }else{
        addlog('Database Connection Error',"ERROR");
        die("Database Connection Error");
    }
    
    
   $cronQuery = $conn->query("select isrunning from crons where crontype = 'bulkimport'");
    $cronrow = $cronQuery->fetch_assoc();
    if($cronrow['isrunning'] == 1){
        @mail("pankajnarang81@gmail.com", "bulk import: Cron already running", "bulkimport: Cron already running");
        die("Connection failure!");
    }

    $conn->query("update crons set lastrun = now(), isrunning = 1 where crontype = 'bulkimport'");
		
	////////     GLOBAL VARIABLES    /////////
    	/*$per_user_sync_limit = 20;/// To set limit of sync for users
        $responseData = array();
        $max_failures = 1;
        $n_parallel_exit_nodes = 0;
        $n_total_req = 0;
        $switch_ip_every_n_req = 2;
        $at_req = 0;
    	$failedRequests = array();  
        $allowed = 0;*/
    
        $conn->autocommit(true);
    	//echo 'To enable your free eval account and get CUSTOMER, YOURZONE and '
    	    //.'YOURPASS, please contact sales@luminati.io';
    	$username = 'lum-customer-hl_c27ea444-zone-static';
    	$password = '0w29dqxs53i7';
    	$port = 22225;
    	$user_agent = [];
    ////////     GLOBAL VARIABLES    /////////
	
	if(function_exists('date_default_timezone_set'))
    {
        date_default_timezone_set("Asia/Kolkata");
    }   
	$restop = $conn->query("SELECT * FROM `bulk_imports` where `status`= 0  and user_id = 9190 limit 0, 1");
	addlog("bulk import in progress for count".$restop->num_rows." rows","INFO");
	while ($data = $restop->fetch_assoc()) {
	    addlog("SELECT * FROM `users` WHERE `id`='".$data['user_id']."'","USER QUERY");
	    if($userObject = $conn->query("SELECT * FROM `users` WHERE `id`='".$data['user_id']."'")){
	        while($userRow = $userObject->fetch_assoc()){
	            //addlog(json_encode($userRow),"USER DETAILS");
	            $skulimit = $userRow['skulimit'];
                addlog("skulimit for user ".$data['user_id']." is ".$skulimit,"ERROR");
	            $skuconsumed = $userRow['skuconsumed'];
	            if($skuconsumed < $skulimit ){
                    $asins = explode("\n",$data['asin']);
                    addlog("asins being performed now".json_encode($data['asin']),"INFO");
                    $pendingSKUCnt = $skulimit - $skuconsumed;
                    addlog("pending asins count are now".$pendingSKUCnt,"INFO");
    $skuconsumed = $skuconsumed + addMultipleProduct($asins,$data['amazon_base_url'],$pendingSKUCnt,$userRow,$data['id']);
                    addlog("skuconsumed update query"."update users SET skuconsumed= '".$skuconsumed."' WHERE id = ".$data['id']."","INFO");
                    $conn->query("update users SET skuconsumed= '".$skuconsumed."' WHERE id = ".$data['id']."");
                   
             	} 

  }
} 
}// while loop

 
     $conn->query("update crons set lastrun = now(), isrunning = 0 where crontype = 'bulkimport'");   
    function addMultipleProduct($asins,$base_url,$pendingSKUCnt,$userDetails,$request_id){
        addlog("add Multiple Product Execution started","STARTED");
       /* global  $conn,$max_failures,$n_parallel_exit_nodes,$n_total_req,$switch_ip_every_n_req,$at_req,$per_user_sync_limit,$failedRequests;*/
        global  $conn;
        $base_url =  "https://".$base_url;
        $failedAsin = array();
        $successImports = 0;
        $master = curl_multi_init();
        $user_id = $userDetails['id'];
	    $max_failures = 1;
	    $failed_count = 0;
	    $n_parallel_exit_nodes = 5;
	    $n_total_req = 5;
	    $switch_ip_every_n_req = 2;
	    $at_req = 0;
        $counter = 0;
        $curl_arr = array();
        $master = curl_multi_init();
        $urls = array();
            foreach($asins as $asin)
            {
                if($asin != "")
                {
                    addlog("SELECT * FROM `product_variants` WHERE `asin`='".trim($asin)."' and user_id = ".$user_id,"INFO");
                    $existance = $conn->query("SELECT * FROM `product_variants` WHERE `asin`='".trim($asin)."' and user_id = ".$user_id);
                    if($existance->num_rows == 0)
                    {// does not exist already
                        if($pendingSKUCnt > 0 && $asin != "")
                        {//
                                $temp= fetchProductDataWithRetry($base_url."/dp/".$asin);
                               // addlog("data via multiple attempts are ".$temp,"ERROR");
                                if($temp !== "ERROR") {
                                    addlog("data found for asin ".$asin,"ERROR");
                                   $product_data = getjsonrdata($temp,$base_url."/dp/".$asin);
                                   addlog("data crawled and parsed for asin ".$asin,"ERROR");
                                   if($product_data) {
                                     $importedProduct = addProduct($product_data,$user_id); 
                                       if($importedProduct == "success") {
                                        addlog("data imported for asin ".$asin,"ERROR");
                                        $successImports = $successImports + 1;
                                        if($successImports >=$pendingSKUCnt){
                                          @mail($userRow['email'], "Product Import Failed", "Import Limit Exceeded Increase Your Package"); 
                                            break;
                                        }
                                                           }
                                       else {
                                          
                                          $failed_count =  $failed_count + 1;
                                           $failedAsin = $asin;
                                       }                    
                                        
                                                      }
                                        else{
                                             $failed_count =  $failed_count + 1;
                                             $failedAsin = $asin;
                                        }              
                                          }
                                        else{
                                             $failed_count =  $failed_count + 1;
                                             $failedAsin = $asin;
                                        }    
                              
                        }//pending if
                      }// existing num_rows if
                      }//ifasin loop
                }//for loop
                    addlog("successful asin implemeted are ".$successImports,"INFO");
                    addlog("failed asin implemeted are".sizeof($failedAsin),"INFO");

                            $tmp = "";
                            $i=0;
                            if(sizeof($failedAsin) > 0){
                               for($i;$i < sizeof($failedAsin);$i++){
                                $tmp += $failedAsin[$i]."\n";
                                    }    
                            }
                            
                       $query = "UPDATE `bulk_imports` SET `status`=1, `failed` = '".sizeof($failedAsin)."', `failed_asin` ='".$failedAsin."',"."`updated_at`= NOW() WHERE `id`=".$request_id."";
                       $conn->query($query);    
                        addlog("updating bulk import is ".$query,"INFO");
                       return $successImports; 

                     
    }
    
    
    function addProduct($res1,$user_id){
        addlog("ADDINg DATA TO DATABASE","AddProduct");
            global $conn;
                $resObj = json_decode($res1, true);
	    if(isset($resObj['Title'])){	
    	$results = $resObj;
    	$title = "";
    	$description = "";
    	$brand = "";
    	$product_type = "";
    	$asin = "";
    	$url = "";
    	$price = "";
    	$list_price = "";
    	$images = "";
    	$feature1 = "";
    	$feature2 = "";
    	$feature3 = "";
    	$feature4 = "";
    	$feature5 = "";
    	$quantity = 0;
    	if(isset($results['Title'])) {
    	$title = $results['Title'];
    	}
    	if(isset($results['description'])) {
    	$description = $results['description'];
    	}
    	if(isset($results['brand'])) {
    	$brand = $results['brand'];
    	}
    	if(isset($results['category'])) {
    	$product_type = $results['category'];
    	}
    	if(isset($results['asin'])) {
    	$asin = $results['asin'];
    	}
    	if(isset($results['url'])) {
    	$url = $results['url'];
    	}
    	if(isset($results['price'])) {
    	$price = $results['price'];
    	$price = getAmount($price);
    	}
    	if(isset($results['list_price'])) {
    	$list_price = $results['list_price'];
    	$list_price = getAmount($list_price);
    	} else {
    	$list_price = $price;
    	}
    	if(isset($results['in_stock___out_of_stock']) && $results['in_stock___out_of_stock'] == 'In stock.') {
    	$quantity = 1;
    	}
    	if(isset($results['high_resolution_image_urls'])) {
    	$high_resolution_image_urls = $results['high_resolution_image_urls'];
    	$images = explode("|", $high_resolution_image_urls);
    	$images = array_map("trim", $images);
    	
    	}

    	if(isset($results['bullet_points'])) {	
    	$bullet_points = $results['bullet_points'];	
    	//$tempArr = explode("|", $bullet_points);
    	//$tempArr = array_map("trim", $tempArr);
    	$tempArr = $bullet_points;
    	$feature1 = isset($tempArr[0])?$tempArr[0]:"";
    	$feature2 = isset($tempArr[1])?$tempArr[1]:"";
    	$feature3 = isset($tempArr[2])?$tempArr[2]:"";
    	$feature4 = isset($tempArr[3])?$tempArr[3]:"";
    	$feature5 = isset($tempArr[4])?$tempArr[4]:"";	
    	}
    	
    	addlog("saving product variant","INFO");
    	$query = "INSERT INTO `products`( `title`, `description`, `feature1`, `feature2`, `feature3`, `feature4`, `feature5`, `item_note`, `brand`, `product_type`,`status`, `user_id`) VALUES ('".str_replace('\'','\\\'',$title)."','".str_replace('\'','\\\'',$description)."','".str_replace('\'','\\\'',$feature1)."','".str_replace('\'','\\\'',$feature2)."','".str_replace('\'','\\\'',$feature3)."','".str_replace('\'','\\\'',$feature4)."','".str_replace('\'','\\\'',$feature5)."','".str_replace('\'','\\\'',$res1)."','".str_replace('\'','\\\'',$brand)."','".str_replace('\'','\\\'',$product_type)."','Import in progress','$user_id')";
    	//print_r($query);
    	$product = $conn->query($query);
    	if($product){
    	//print_r($conn->insert_id);
    	$product_id = $conn->insert_id;

    	$query = "INSERT INTO `product_variants`(`product_id`,`sku`, `asin`, `price`, `saleprice`,`detail_page_url`,`user_id`) VALUES ('$product_id','$asin','$asin','$list_price','$price','$url','$user_id')";
    	addlog ($query,"INFO");
    	$product_variant = $conn->query($query);
    	if($product_variant){
    	addlog ($conn->insert_id,"INFO");
    	$variant_id = $conn->insert_id;

    	addlog("<br/>saving product images","INFO");
    	foreach ($images as $imageUrl) {
    	addlog($imageUrl,"INFO");
    	if($imageUrl == "")
    	                   {
    	                    continue;
    	                    }
    	                $query = "INSERT INTO `product_images`(`variant_id`, `asin`, `imgurl`, `user_id`) VALUES ('$variant_id','$asin','$imageUrl','$user_id')";
    	                addlog ($query,"INFO");
    	                $productImageObject = $conn->query($query);
    	                if($productImageObject){
    	                	$productImageId = $conn->insert_id;
    	                	addlog ("Image Id: ".$conn->insert_id,"INFO");
    	                	addlog("<br/>Importing to shopify..... ","INFO");
    	                	//$query = "INSERT INTO `importToShopify`(`product_id`, `user_id`, `status`) VALUES ('$product_id','$user_id','0')";
    	//$importtoshopify = $conn->query($query);
    	$query = "SELECT * FROM `products` WHERE `product_id`=$product_id AND `user_id`=$user_id AND `shopifyproductid` = ''";
    	addlog($query,"IMPORT TO SHOPIFY PRODUCTOBJECT ACCESS");
    	$productObject = $conn->query($query);
    	if($productRow = $productObject->fetch_assoc()){
    	    addlog("Calling Import To Shopify","IMPORTING");
    	    if(insertToShopify($user_id,$productRow,$conn) != ""){
    	        return "success";
    	    }else{
    	        return false;
    	    }
    	}else{
    	    addlog("Error While Accessing product from database","IMPORT TO SHOPIFY PRODUCTOBJECT ACCESS");
    	}
    	
    	                }else{
    	                	addlog ("Error In inserting Product Images","INFO");
    	                	print_r($conn->error);
    	                	$query = "DELETE * FROM `products` WHERE `product_id`='".$product_id."';"."DELETE * FROM `product_variants` WHERE `product_id`='".$product_id."';";
    	                	if($conn->query($query)){
    	return null;   
    	    }else{
    	addlog("Error In Deleting product and Variants Values 510","ERROR");
    	return null;
    	}
    	                }
    	}
    	}else{
    	addlog("Error in inserting product variation","INFO");
    	addlog($conn->error,"ERROR");
    	$query = "DELETE * FROM `products` WHERE `product_id`='".$product_id."';";
    	if($conn->query($query)){
    	    return null;   
    	}else{
    	addlog("Error In Deleting product Values 521","ERROR");
    	return null;
    	}
    	}
    	}else{
    	addlog ("Error in updating Product","INFO");
    	print_r($conn->error);
    	return null;
    	}
    	addlog($user_id.' '.$product_id,"INFO");
    	addlog("return 200","INFO");
    	
    	addlog("Imported","INFO");
    	}//if of results validation
    	else {
    	@mail("pankajnarang81@gmail.com", "ProductController: Scrapper Call Failed", "ProductController: Proxy crawel code failed");
    	print_r(['error' => ["msg"=>["There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist."]]], 406);
    	}
    }
    
   
	
	
	
 
   function getjsonrdata($data,$url) {
         addlog("in json code","INFO");
        $doc = new \DOMDocument("1.0", "utf-8");
        $doc->recover = true;
        $errors = libxml_get_errors();
        $saved = libxml_use_internal_errors(true);
      // Log::info($data);
        if(strlen($data)<200){
            return null;
        }
        $doc->loadHTML($data);
        //$handle = fopen('Product.html', 'wr');
        //fwrite($handle, $data);
        $xp = new \DOMXPath($doc);
        $dataArr = array();
        //$dataArr['url'] = $producturl;
        $titleBlock = $doc->getElementById('title');
        //$dataArr['Title'] = $xp->evaluate('string(.//*[@class="a-size-extra-large"])', $titleBlock);
        $dataArr['Title'] = trim( $xp->evaluate('string(.//*[@class="a-size-large"])', $titleBlock) );
        //case where capatcha is there  
       //   if(strlen($dataArr['Title'])==0){Log::info("No Data Available in getjsonrdata");return null;}
        if(strlen($dataArr['Title'])==0){
            $dataArr['Title'] = trim($xp->evaluate('string(//*[@id="productTitle"])', $titleBlock));
            if(strlen($dataArr['Title'])==0){
                 addlog("title not found for ".$url,"INFO");
                return null;
            }
        }
         
      /*  Log::info($data);
       $res = preg_match_all('/<div id="productDescription" class="a-section a-spacing-small">\s|.*<div class="disclaim">.*<\/div>(.*)<\/div>/sU',  $data, $matches);
         if($res){
                $dataArr['description'] = '<p><b>Product Details:</b></p>'.trim($matches[1][0]);//*[@id="productDescription"]
                addlog("description is done in fdsdsdirst regular expression ".$dataArr['description'],"INFO");
                exit;
            }
       exit; */    
       $All = [];
       $tables = $doc->getElementById('productDetails_techSpec_section_1');
       if(!$tables){
       $tables = $xp->query("//*[@id='productDetails_techSpec_section_1']")->item(0);
      addlog("ttables found via xpath".$tables,"INFO");
       
       }
       
       $tables2 = $doc->getElementById('productDetails_techSpec_section_2');
       $props = '';
     
     if($tables || $tables2){
       addlog("desc foundddddddddd","INFO");
        $tr = $tables->getElementsByTagName('tr'); 
        addlog("props in productDetails_techSpec_section_1","INFO");
        foreach ($tr as $element1) {        
            for ($i = 0; $i < count($element1); $i++) {
                //Not able to fetch the user's link :(
                $name  = $element1->getElementsByTagName('th')->item(0)->textContent;    // To fetch user link
                $value  = $element1->getElementsByTagName('td')->item(0)->textContent;  // To fetch name
                                 // To fetch country
        
                if($name.contains('Brand') || $name.contains('Dimension') || $name.contains('Color') || $name.contains('Size') || $name.contains('Number') || $name.contains('Weight') ) {
                     $All[$name] = $value;
                }
            }
        }
        
        
      
     
     if($tables2){
        $tr = $tables2->getElementsByTagName('tr'); 
        addlog("props in productDetails_techSpec_section_2","INFO");
        foreach ($tr as $element1) {        
            for ($i = 0; $i < count($element1); $i++) {
                //Not able to fetch the user's link :(
                $name  = $element1->getElementsByTagName('th')->item(0)->textContent;    // To fetch user link
                $value  = $element1->getElementsByTagName('td')->item(0)->textContent;  // To fetch name
                                 // To fetch country
        
                if($name.contains('Brand') || $name.contains('Dimension') || $name.contains('Color') || $name.contains('Size') || $name.contains('Number') || $name.contains('Weight') ) {
                     $All[$name] = $value;
                }
            }
        }
        
        }
        
        

     if(sizeof($All) > 0){
        $props = '<p><b>Product Specifications</b></p><p><ul>';
     }
        foreach($All as $key => $value) {
        $props = $props.'<li><b>'.$key.'</b>:'. $value.'</li>';
        }
        $props= $props.'</ul></p>';
    
        //$dataArr['description'] = $props.$dataArr['description'];
       //Log::info("desc after props".$dataArr['description']);

     }
     
     else {
     
     
        //here we will see how to fetch color brand publisher and other details
     
      $brandnameheaddiv = $doc->getElementById('bylineInfo_feature_div');
       addlog("props in bylineInfo_feature_div","INFO");
      if($brandnameheaddiv)
        {
         $props = '<p><b>Product Specifications</b></p><p><ul>';
         $link = $brandnameheaddiv->getElementsByTagName('div');//->getElementsByClassName('a-link-normal')->item(0)->textContent;         
         foreach ($link as $element1) {
          for ($i = 0; $i < count($element1); $i++) {     
            $brandInfo =  $element1->getElementsByTagName('a')->item(0)->textContent; 
            addlog("brand in else".$brandInfo,"INFO");
            if($brandInfo !== ''){
               $props = $props.'<li><b>Brand Name</b>:'.$brandInfo.'</li>';
                 }    
            //Log::info("brand in else".$props);
          }
         }
        }
        
        
        
      $sizediv = $doc->getElementById('variation_size_name');
      $tmpsizeprops = "";
        if($sizediv)
        {
         $link = $sizediv->getElementsByTagName('div'); 
         if($link){
             foreach ($link as $element1) {
               addlog(json_encode($element1),"INFO");
                if(!$element1->getElementsByTagName('span')){
                 continue;
                }
                if(!$element1->getElementsByTagName('span')->item(0)){
                 continue;
                }
                
                
                
                
                $sizeInfo =  $element1->getElementsByTagName('span')->item(0)->textContent; 
                if(isset($sizeInfo)){
                    addlog("size in else".trim($sizeInfo),"INFO");
                     $props = $props.'<li><b>Size</b>:'.trim($sizeInfo).'</li>';    
                    if($sizeInfo){
                        break;
                    }
                }
                //Log::info("color in else".$props);
            
             } 
         }
        
        }
        
        
        
        
        
          
        $colordiv = $doc->getElementById('variation_color_name');
        if($colordiv)
        {//*[@id="variation_color_name"]/div
         $tmpprops = "";
         $link = $colordiv->getElementsByTagName('div')/*->item(0)*/;//->getElementsByClassName('a-link-normal')->item(0)->textContent; 
         if($link){
             foreach ($link as $element1) {
                addlog(json_encode($element1),"INFO");
                $colorInfo =  $element1->getElementsByTagName('span')->item(0)->textContent; 
                if(isset($colorInfo)){
                    //Log::info("color in else".$colorInfo);
                     $props = $props.'<li><b>Color</b>:'.trim($colorInfo).'</li>';    
                    if($colorInfo){
                        break;
                    }
                }
               addlog("color in else".$tmpprops,"INFO");
             } 
         }
         
         
         /*if( $tmpprops == '' || $tmpprops == null){
             Log::info(str_replace("\n","",$doc->saveHTML($link->item(0))));
             $res = preg_match_all('/<div class="a-row a-spacing-micro">\s*<strong>(.*)<\/strong>\s*(.*)<\/div>/U',str_replace("\n","",$doc->saveHTML($link->item(0))),$matches);
             if($res){
                 Log::info($matches);
                $tmpprops = '<li><b>Color</b>:'.trim($matches[2][0]).'</li>'; 
             }
         }*/
        }
        
        /*  $detailsBlock = $doc->getElementById('centerCol');
          $res = preg_match_all('/<div id="variation_size_name" .*>(.*)<\/div>/sU',$doc->saveHTML($detailsBlock),$matches);
            if($res){
                $tmpprops = '<li><b>Size</b>:'.trim($matches[1][0]).'</li>'; 
                $props .= $tmpprops;
            }
        */
        
      $props= $props.'</ul></p>';
      
      //$dataArr['description'] = $props.$dataArr['description'];
      addlog(" props ".$props,"INFO");
      
}

          $bulletPoints = '';
          $featuredetailsBlock = $doc->getElementById('featurebullets_feature_div');
         // if()
          $res = preg_match_all('/<div id="feature-bullets".*>(.*)<\/div>/sU',$doc->saveHTML($featuredetailsBlock),$matches);
            if($res){
                addlog("bulletPoints in first re".json_encode($matches),"INFO" );
                $bulletPoints = $matches[1][0];
               if (strpos($bulletPoints, 'hsx-rpp-bullet-fits-message') !== false) {
                    $res = preg_match_all('/<div id="feature-bullets".*>.*<\/div>.*<\/script>(.*)<\/div>/sU',$doc->saveHTML($featuredetailsBlock),$matches);
                    $bulletPoints = $matches[1][0];     
                }
                $bulletPoints = str_replace("<a","<!--<a",$bulletPoints);
                $bulletPoints = str_replace("</a>","</a>-->",$bulletPoints);
                $bulletPoints = str_replace("(","",$bulletPoints);
                $bulletPoints = str_replace(")","",$bulletPoints);
                
                $bulletPoints = "<br/><p></p><p><strong>Features</strong></p>".$bulletPoints;
               addlog($bulletPoints,"INFO");
            }
       
      
        
            if(!isset($bulletPoints) || $bulletPoints == '' || $bulletPoints == null){
                $descriptionAndDetailsBlock = $doc->getElementById('descriptionAndDetails');
                $res = preg_match_all('/<div id="detailBullets_feature_div">(.*)<\/div>/sU',$doc->saveHTML($descriptionAndDetailsBlock),$matches);
                if($res){
                     addlog("bulletPoints in second re","INFO");
                    $detailBullets = $matches[1][0];
                    $detailBullets = str_replace('<li><span class="a-list-item">
                       
                    </span></li>',"",$detailBullets);
                    $detailBullets = str_replace('Amazon',"",$detailBullets);
                    $bulletPoints = str_replace("(<a","<!--",$bulletPoints);
                    $bulletPoints = str_replace("</a>)","-->",$bulletPoints);
                    $bulletPoints = str_replace("(","",$bulletPoints);
                    $bulletPoints = str_replace(")","",$bulletPoints);
                    $bulletPoints .= "<br/><p></p><p><strong>Features</strong></p>".$bulletPoints;
                    addlog($detailBullets,"INFO");
                }
            }
        
       
       if(!isset($bulletPoints) || $bulletPoints == '' || $bulletPoints == null){
          $featurebullets = $doc->getElementById('feature-bullets');
          addlog("bulletPoints in third attempet".$bulletPoints,"INFO");
          
          if($featurebullets){
               $count = $xp->evaluate('count(.//ul//li[not(@id)])',$featurebullets);///span[@class="col2"]/text()
                             }
            else{
                $count = 0;
            }                 
          //$bulletData = array();
          $bulletPoints = '<ul>';
           for($x=0; $x <= $count - 1 ;$x++){
             $bdata =  $xp->evaluate('(.//ul//li[not(@id)])',$featurebullets )->item($x)->nodeValue ;
             $str = str_replace(array("\r\n", "\r", "\n", "\t"), '', $bdata);
             $bulletPoints =  $bulletPoints.'<li>'.$str.'<\li>';
             //array_push($bulletData,trim($str));
            }
             $bulletPoints =  $bulletPoints.'<\ul>';        
             if($featurebullets){
             $bulletPoints .= "<br/><p></p><p><strong>Features</strong></p>".$bulletPoints;
             }
             
             
            addlog($bulletPoints,"INFO");
       }
       
       if(!isset($bulletPoints) || $bulletPoints == '' || $bulletPoints == null){
           $bulletPoints = '';
           addlog("bulletPoints is empty after all efforts","INFO");
       }
    
   addlog("feature bullet points are now".$bulletPoints,"INFO");
        
      
     // the below code is for description 
       $dblock = '';
       $productDescriptionBlock = $doc->getElementById('descriptionAndDetails');
       $dataArr['description'] = '';
      if($productDescriptionBlock){
        // Log::info("description block ".$doc->saveHtml($productDescriptionBlock));
         $dblock = $doc->saveHtml($productDescriptionBlock);
       }
       else {
          $centerBlock = $doc->getElementById('dpx-product-description_feature_div');
      if($centerBlock){
          $centerDatBlock = $doc->saveHtml($centerBlock);
          addlog("center block ".$centerDatBlock,"INFO" );
          $dblock =  $centerDatBlock;
       }
          
      //    exit;
       }
         
       
       if($dblock !== ''){//
         $res = preg_match_all('/<div id="productDescription" class="a-section a-spacing-small">\s|.*<div class="disclaim">.*<\/div>(.*)<\/div>/sU',  $dblock, $matches);
            //<div id="productDescription" class="a-section a-spacing-small">\n*.*<div class="disclaim">.*<\/div>(.*)<\/div>
           addlog("trying regular expressions on ","INFO");
            if($res && $matches[1][0] !== '' ){
                $dataArr['description'] = '<p><b>Product Details:</b></p>'.trim($matches[1][0]);//*[@id="productDescription"]
               addlog("description is done in first regular expression ".$dataArr['description'],"INFO");
            }
            else{
                $res = preg_match_all('/<div id="productDescription" class="a-section a-spacing-small">\s*(.*)<\/div>/sU', $dblock, $matches);
                if($res && $matches[1][0] !== ''){
                    $dataArr['description'] = trim($matches[1][0]);//*[@id="productDescription"]
                   addlog("description is done in second regular expression ".$dataArr['description'],"INFO");
                }else{
                    $res = preg_match_all('/<div id="productDescription" class="a-section a-spacing-small">\s*(.*)<\/div>\s*<style\s*/sU',  $dblock, $matches);
               if($res && $matches[1][0] !== ''){
                   $dataArr['description'] = '<p><b>Product Details:</b></p>'.trim($matches[1][0]);//*[@id="productDescription"]
                   addlog("description is done in third regular expression ".$dataArr['description'],"INFO");
                   }
                  else{
                    $dataArr['description'] = '';
                  } 
                }
            }
         }
         //}// test attempt done
       
         
        if($dataArr['description'] == '' || $dataArr['description']==null){
            //*[@id="productDescription"]/p
            $tempdesc = trim($xp->evaluate('string(//*[@id="productDescription"]/p)', $productDescriptionBlock));
            $dataArr['description'] = $tempdesc;
            addlog("description is done in third xpath attempt we wish to avoid".$dataArr['description'],"INFO");
        }
       

         if($dataArr['description'] == '' || $dataArr['description']==null){
            $dataArr['description'] =  trim($doc->saveHTML($productDescriptionBlock));
            $dataArr['description'] = str_replace("\n", "", $data);
            $res = preg_match_all('/<div id="productDescription" class="a-section a-spacing-small">\n*.*<div class="disclaim">.*<\/div>(.*)<\/div>/sU', $data, $matches);
            if($res){
                $dataArr['description'] = trim($matches[1][0]);//*[@id="productDescription"]
               addlog("description is done in 4th attempt".$dataArr['description'],"INFO");
            }else{
                $res = preg_match_all('/<div id="productDescription" class="a-section a-spacing-small">\s*(.*)<\/div>/sU', $data, $matches);
                if($res){
                    $dataArr['description'] = trim($matches[1][0]);//*[@id="productDescription"]
                    addlog("description is done in 5th attempt".$dataArr['description'],"INFO");
                }else{
                    $dataArr["description"] = "";
                }
            }
        }
       

        if($dataArr['description'] == '' || $dataArr['description'] == null){
            if(trim($xp->evaluate('string(//*[@id="aplus"])', $doc)) != '' ||trim($xp->evaluate('string(//*[@id="aplus"])', $doc)) != null){
                $dataArr['description'] = $doc->saveHTML($doc->getElementById('aplus'));
                $res = preg_match_all('/<p>(.*)<\/p>/sU', $dataArr['description'],$matches);
                if($res){
                    $tmp = "";
                    //Log::info($matches[1]);
                    foreach($matches[1] as $p){
                        if(strpos($p, '<img') !== false){
                            
                        }else{
                            $tmp .= "<p>".$p."</p>"; 
                        }
                    }
                   addlog($tmp,"INFO");
                   addlog("description is done in 4th aplus regular expression attempt","INFO");
                    $dataArr['description'] = $tmp;
                }
               
            }else{
                if(trim($xp->evaluate('string(//*[@id="descriptionAndDetails"])',  $doc)) != '' ||trim($xp->evaluate('string(//*[@id="aplus"])',  $doc)) != null){
                    //*[@id="descriptionAndDetails"]
                    $dataArr['description'] = '';//$doc->saveHTML($doc->getElementById('descriptionAndDetails'));
                    //Log::info("description is done in th attempt that may be fixed".$dataArr['description']);
                }else{
                   
                    if(trim($xp->evaluate('string(//*[@id="aplus"])', $doc)) != '' ||trim($xp->evaluate('string(//*[@id="aplus"])',  $doc)) != null){
                $dataArr['description'] = $doc->saveHTML( $doc->getElementById('aplus'));
                $res = preg_match_all('/<p>(.*)<\/p>/sU', $dataArr['description'],$matches);
                if($res){
                    $tmp = "";
                    //Log::info($matches[1]);
                    foreach($matches[1] as $p){
                        if(strpos($p, '<img') !== false){
                            
                        }else{
                            $tmp .= "<p>".$p."</p>"; 
                        }
                    }
                   // Log::info($tmp);
                    $dataArr['description'] = $tmp;
                }
               // Log::info("description is done in 6th attempt");
            }else{
                if(trim($xp->evaluate('string(//*[@id="descriptionAndDetails"])',  $doc)) != '' ||trim($xp->evaluate('string(//*[@id="aplus"])',  $doc)) != null){
                    //*[@id="descriptionAndDetails"]
                    $dataArr['description'] = '';//$doc->saveHTML($doc->getElementById('descriptionAndDetails'));
                  //  Log::info("description is done in 8th attempt".$dataArr['description']);
                }else{
                    $dataArr['description'] = '';
                }
            }  




                    //$dataArr['description'] = '';
                }
            }
        }
        
        

         if($dataArr['description'] == '' || $dataArr['description']==null){
            $tempdesc =  $xp->evaluate('string(//*[@id="productDescription"])', $doc);
            $dataArr['description'] =  $tempdesc ;
         }
         


     
       if($dataArr['description'] == '' || $dataArr['description']==null){
        $temp = $doc->getElementById('bookDescription_feature_div');
        if($temp){
            echo  str_replace(">","",str_replace("<","",$doc->saveHTML($temp)));
            $res = preg_match_all("/<noscript>\s*(.*)\s*.*\s*<\/noscript>/", $doc->saveHTML($temp), $matches);
            if($res){
                echo "<br/><br/>";
                print_r($matches);
                echo "<br/><br/>";
            }else{
                echo "<br/><br/> <h1>Error In Pattern matching</h1><br/><br/>";
            }
        }else{
            echo "<br/><br/> <h1>Iframe Description Not Found</h1><br/><br/>";
        }
       }
   
        
         
            //*[@id="productDescription"]/p
           
        
        
        if($dataArr['description']){
           $dataArr['description'] = '<b>Product Description : </b>'. $dataArr['description'];
        }
       //remove_emoji("description found");

       // above code for description is done.
        $dataArr['description'] = $props.$bulletPoints.$dataArr['description'];
        $dataArr['description'] = str_replace('none','',$dataArr['description']); 
        addlog("complete description is ".$dataArr['description'],"INFO");
        // first attempt to match product from ASIN from link
        $url = explode("?",$url);
        $res = preg_match_all('/\/([A-Z0-9]*)\//U',$url[0]."/",$matches);
        if($res){
        	if(!isset($matches[1][1])){
        		$matches  = explode("/", $url[0]);
        		$asin = $matches[sizeof($matches)-1];
        	}else{
        		$asin = $matches[1][1];
        	}	
        }
        $asin == trim($asin);
        if( strpos($asin, ' ') == false &&  ctype_alnum($asin)){
          $dataArr['asin'] = $asin;    
        }
        else {//fetch ASIN fromssion for secnd best result
             $asinBlock = $doc->getElementById('productDetails_detailBullets_sections1'); //->childNodes;
             if($asinBlock != null){
             $dataArr['asin'] =  $xp->evaluate('(string(.//tr[3]/td))', $asinBlock);     
             }
             else {// the worst attemept is to grab from hidden field in html
                $asinBlock  =     $doc->getElementById('ASIN');
                if($asinBlock != null){
                  $dataArr['asin'] =  $asinBlock->getAttribute('value');
                                    }   
             }
             
        }
       //Log::info("asin is done".$dataArr['asin']);
        //$dataArr['asin'] = $asin;
      
        $values =  $dataArr['asin'];
        
       /* $featurebullets = $doc->getElementById('feature-bullets');
       if($featurebullets){
          $count = $xp->evaluate('count(.//ul//li[not(@id)])',$featurebullets);///span[@class="col2"]/text()
          $bulletData = array();
         
           for($x=0; $x <= $count - 1 ;$x++){
             $bdata =  $xp->evaluate('(.//ul//li[not(@id)])',$featurebullets )->item($x)->nodeValue ;
             $str = str_replace(array("\r\n", "\r", "\n", "\t"), '', $bdata);
             array_push($bulletData,trim($str));
                                     }
       }else{
           $bulletData = '';
       }
      //  Log::info("feature bullet points are".json_encode($bulletData));
        $dataArr['bullet_points'] = $bulletData;*/
        
        $dataArr['bullet_points'] = '';
        //Log::info("bullet points is done".json_encode($dataArr['bullet_points']) );
        $salepricediv = $xp->evaluate('string(//span[contains(@id,"ourprice") or contains(@id,"saleprice") or contains(@id,"priceblock_ourprice") or contains(@id,"buyNew_noncbb") or contains(@id,"priceblock_dealprice")]/text())',$doc);
        if(strpos($salepricediv, '-') !== false){
          $pricediv = explode("-", $salepricediv);
          //Log::info($pricediv);
          $salepricediv = trim($pricediv[0]);
        }
        //Log::info($salepricediv);
        if($salepricediv == ""){
         $salepricediv =$xp->evaluate('string(//div[@id="cerberus-data-metrics"]//@data-asin-price)',$doc);
          if(strpos($salepricediv, '-') !== false){
              $pricediv = explode("-", $salepricediv);
            //  Log::info($pricediv);
             // Log::info($salepricediv);
              $salepricediv = trim($pricediv[0]);
          }
        }
        $dataArr['price'] = $salepricediv;
        if($dataArr['price'] == ''){
            $salepricediv =$xp->evaluate('string(//*[@id="buyNewSection"]/a/h5/div/div[2]/div/span[2])',$doc);
            if(strpos($salepricediv, '-') !== false){
              $pricediv = explode("-", $salepricediv);
           //    Log::info($pricediv);
             // Log::info($salepricediv);
              $salepricediv = trim($pricediv[0]);
            }
        }
        $dataArr['price'] = $salepricediv;
        if($dataArr['price'] == ""){
            $salepricediv =$xp->evaluate('string(//*[@id="olp-upd-new"]/span/a/text())',$doc);
            $salepricediv = explode("$",$salepricediv);
           // Log::info($salepricediv);
          //  Log::info("sales div");
            if( isset($salepricediv[1]) ) {
              $dataArr['price'] = "$ ".$salepricediv[1];    
            }
        }
    // fetch price from comparison table
     if($dataArr['price'] == ""){
        $All = [];
       $tables = $doc->getElementById('HLCXComparisonTable');
      
      if($tables)
       {
       $tr     = $tables->getElementsByTagName('tr'); 

      foreach ($tr as $element1) {        
         for ($i = 0; $i < count($element1); $i++) {
         $id = $element1->getAttribute('id');
        //  Log::info("id is found in table ".$id);
         if($id == 'comparison_price_row') {
             $price = $element1->getElementsByTagName('td')->item(0)->textContent; 
           //  Log::info("price is found in table ".$price);
             $dataArr['price'] = "$ ".substr($price, strpos($price, "$") + 1);
           //  Log::info("price is found in table ".$dataArr['price']);
             break;
         }
           
            }
   }

    /*$props = '<p><b>Product Specifications</b></p><p><ul>';
    foreach($All as $key => $value) {
    $props = $props.'<li><b>'.$key.'</b>:'. $value.'</li>';
    }
    $props= $props.'</ul></p>';*/
    
  // $dataArr['description'] = $props.$dataArr['description'];
  // Log::info("desc after props".$dataArr['description']);

}
            
        }
        
        $originalpricediv = $xp->evaluate('string(//td[contains(text(),"List Price") or contains(text(),"M.R.P") or contains(text(),"Price")]/following-sibling::td/text())', $doc);
        
        if(trim($originalpricediv) == ''){
            $dataArr['list_price'] = $dataArr['price'];
        }else{
         //   Log::info("original price div");
          //  Log::info($originalpricediv);
            $dataArr['list_price'] = $originalpricediv;
        }
        
        
        //vendorPoweredCoupon_feature_div
       
        $categoryDiv = $xp->evaluate('string(//a[@class="a-link-normal a-color-tertiary"]//text())',$doc);
      
        $dataArr['category'] = $categoryDiv;
       
       //$availabilityDiv = $xp->evaluate('string(//div[@id="availability"])',$doc);
       
        $brandDiv = $xp->evaluate('string(//a[@id="bylineInfo"]//text())',$doc);
        $dataArr['brand'] = $brandDiv;
       
        $imageArr = array();
        $imageArr2 = array();
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
              $imageArr2= $match[1];
          }
          if(sizeof($imageArr)<sizeof($imageArr2)){
                   $imageArr = $imageArr2;
          }

          if(sizeof($imageArr) == 0){
            $res1= preg_match_all('/"mainUrl":"(.*)"/U',$file,$match);
            if($res1){
                $imageArr= $match[1];
            }
          }
       }

        }
        
        foreach ($imageArr as $image) {
            $imagepipe = $imagepipe."|".$image;
        }
        
       
        $dataArr['high_resolution_image_urls'] =  $imagepipe;  //$imageArr[0]."|".$imageArr[1]."|".$imageArr[2]."|".$imageArr[3]."|".$imageArr[4]."|".$imageArr[5]."|".$imageArr[6]."|".$imageArr[7]."|".$imageArr[8]."|".$imageArr[9];
        
        
        if( !isset( $dataArr['Title'] ) || !isset( $dataArr['description'] ) || !isset( $dataArr['asin'] ) || !isset( $dataArr['bullet_points'] ) || !isset( $dataArr['price'] ) || !isset( $dataArr['list_price'] )  || !isset( $dataArr['category'] ) || !isset( $dataArr['brand'] ) || !isset( $dataArr['high_resolution_image_urls'] )       ){
            return json_encode(array());
        }else{
            return json_encode($dataArr);
        }
    }
 
//https://www.amazon.com/gp/product/B07C5992QM
	
  
	function proxyCheck($proxy_port,$proxy_ip,$loginpassw,$url,$user_agent){
	
	    //$cookie = tempnam ("/tmp", "CURLCOOKIE");
	    $ch = curl_init();
	    curl_setopt( $ch, CURLOPT_USERAGENT, $user_agent );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
        curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTPS');
        curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            //"Postman-Token: 47dd397c-06d7-461f-9873-b317e948d580",
            "cache-control: no-cache",
            "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
            "Connection: close",
            "X-Forwarded-For: ".$proxy_ip,
            "Content-Length: 0",
            "Cookie: timezone=Asia/Kolkata;",
            "Accept-Language: en-US,en;q=0.9",
            "Accept-Encoding: gzip, deflate, br",
            //"Host: www.amazon.com",
          ));
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_ENCODING, "UTF8" );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
	    $content = curl_exec( $ch );
	    $response = curl_getinfo( $ch );
	    print_r($response);echo "<br/><br/><br/>";
	    curl_close ( $ch );
	    $d = getjsonrdata($content,$url);
	    if($d == null){
	    	 //print_r($content);
	    	return $d;
	    }
	   	return $content;
	    //print_r($content);
	}
	
	
	function getAmount($money) {
	    addlog("converting to amount","INFO");
	    addlog($money,"INFO");
	    $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
	    $onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);
	    $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;
	    $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
	    $removedThousendSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '',  $stringWithCommaOrDot);
	    return (float) str_replace(',', '.', $removedThousendSeparator);
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

	

	

	function getImageURL($imageSetObj){
    	$imageUrl = "";
    	if(isset($imageSetObj['HiResImage']['URL'])){
    	$imageUrl = $imageSetObj['HiResImage']['URL'];
    	} else if(isset($imageSetObj['LargeImage']['URL'])){
    	$imageUrl = $imageSetObj['LargeImage']['URL'];
    	} else if(isset($imageSetObj['MediumImage']['URL'])){
    	$imageUrl = $imageSetObj['MediumImage']['URL'];
    	} else if(isset($imageSetObj['TinyImage']['URL'])){
    	$imageUrl = $imageSetObj['TinyImage']['URL'];
    	} else if(isset($imageSetObj['SmallImage']['URL'])){
    	$imageUrl = $imageSetObj['SmallImage']['URL'];
    	} else if(isset($imageSetObj['ThumbnailImage']['URL'])){
    	$imageUrl = $imageSetObj['ThumbnailImage']['URL'];
    	} else if(isset($imageSetObj['SwatchImage']['URL'])){
    	$imageUrl = $imageSetObj['SwatchImage']['URL'];
    	}
    	return $imageUrl;
	}

	function insertItemWithVariants($item) 
	{	
    	$currUser = Auth::User();
    	$title = "";
    	$description = "";
    	$feature1 = "";
    	$feature2 = "";
    	$feature3 = "";
    	$feature4 = "";
    	$feature5 = "";
    	$brand = "";
    	$product_type = "";
    	$parentasin = "";	
    	$option1name = "";
    	$option2name = "";
    	$option3name = "";
    	$variantsArr = array();
    	$detailPageURL = "";

    	// For products table
    	if(isset($item['ItemAttributes'])){
    	$itemAttributes	= $item['ItemAttributes'];
    	if(isset($itemAttributes['Title'])){
    	$title = trim($itemAttributes['Title']);
    	}	
    	if(isset($itemAttributes['Brand'])){
    	$brand = $itemAttributes['Brand'];
    	}
    	if(isset($itemAttributes['ProductGroup'])){
    	$product_type = $itemAttributes['ProductGroup'];
    	}
    	}
    	if(isset($item['DetailPageURL'])){
    	$detailPageURL = $item['DetailPageURL'];
    	}
    	if(isset($item['Variations'])){
    	$variations = $item['Variations'];
    	$totalVariations = 0;
    	if(isset($variations['TotalVariations'])){
    	$totalVariations = $variations['TotalVariations'];	
    	}
    	if($totalVariations < 1){
    	return array("status" => false, "msg" => "Product - ".$title." - no active variant found.");	
    	} else if($totalVariations > 100){
    	return array("status" => false, "msg" => "Product - ".$title." - having more than 100 variants.");	
    	}
    	if(isset($variations['VariationDimensions']['VariationDimension'])){
    	$variationDimensions = $variations['VariationDimensions']['VariationDimension'];
    	if(is_array($variationDimensions) && count($variationDimensions) > 3){
    	return array("status" => false, "msg" => "Product - ".$title." - having more than three variant options.");
    	}
    	if(is_array($variationDimensions)){
    	if(isset($variations['VariationDimensions']['VariationDimension'][0])){
    	$option1name = $variations['VariationDimensions']['VariationDimension'][0];
    	}
    	if(isset($variations['VariationDimensions']['VariationDimension'][1])){
    	$option2name = $variations['VariationDimensions']['VariationDimension'][1];
    	}
    	if(isset($variations['VariationDimensions']['VariationDimension'][2])){
    	$option3name = $variations['VariationDimensions']['VariationDimension'][2];
    	}
    	} else {
    	$option1name = $variationDimensions;
    	}
    	}

    	$variationItems = $variations['Item'];
    	$isFirstVariant = true;
    	if($currUser->id == 279 && isset($variationItems[0]['ItemAttributes']['Title'])){
    	$title = trim($variationItems[0]['ItemAttributes']['Title']);
    	}
    	foreach($variationItems as $variationItem){
    	$tempVariant = array();
    	$tempVariant['option1val'] = "";
    	$tempVariant['option2val'] = "";
    	$tempVariant['option3val'] = "";
    	if(isset($variationItem['ItemAttributes'])) {
    	$vItemAttributes = $variationItem['ItemAttributes'];
    	if($isFirstVariant){
    	$isFirstVariant = false;
    	if(isset($vItemAttributes['Feature'])){
    	$features = $vItemAttributes['Feature'];
    	if(isset($features[0])) {
    	$feature1 = $features[0];
    	}
    	if(isset($features[1])) {
    	$feature2 = $features[1];
    	}
    	if(isset($features[2])) {
    	$feature3 = $features[2];
    	}
    	if(isset($features[3])) {
    	$feature4 = $features[3];
    	}
    	if(isset($features[4])) {
    	$feature5 = $features[4];
    	}
    	}
    	if(isset($variationItem['EditorialReviews']['EditorialReview']['Content'])) {
    	$description = $variationItem['EditorialReviews']['EditorialReview']['Content'];
    	}
    	if(isset($variationItem['ParentASIN'])){
    	$parentasin = $variationItem['ParentASIN'];
    	}
    	}
    	$tempVariant['upc'] = "";
    	if(isset($vItemAttributes['UPC'])){
    	$tempVariant['upc'] = $vItemAttributes['UPC'];
    	}
    	$tempVariant['weight'] = 0;
    	if(isset($vItemAttributes['PackageDimensions']['Weight'])){
    	$tempVariant['weight'] = $vItemAttributes['PackageDimensions']['Weight'];
    	$tempVariant['weight'] = $tempVariant['weight']/100;
    	}
    	}
    	
    	$tempVariant['asin'] = "";
    	if(isset($variationItem['ASIN'])){
    	$tempVariant['asin'] = $variationItem['ASIN'];
    	}
    	
    	// Fetch offer details
    	$offerlistingId = "";
    	$price = "";
    	$saleprice = "";
    	$condition = "";
    	
    	if(isset($variationItem['Offers'])){
    	$offers = $variationItem['Offers'];
    	$totalOffers = isset($offers['TotalOffers'])?$offers['TotalOffers']:0;
    	$offer = $offers['Offer'];
    	if($totalOffers > 0) {
    	$offer = $offers['Offer'][0];
    	}
    	if(isset($offer['OfferAttributes']['Condition'])){
    	$tempVariant['condition'] = $offer['OfferAttributes']['Condition'];
    	}
    	if(isset($offer['OfferListing'])){
    	$offerListing = $offer['OfferListing'];
    	if(isset($offerListing['OfferListingId'])){
    	$tempVariant['offerlistingId'] = $offerListing['OfferListingId'];
    	}
    	if(isset($offerListing['Price']['Amount'])){
    	$tempVariant['price'] = number_format($offerListing['Price']['Amount']/100, 2, ".", "");
    	}
    	if(isset($offerListing['SalePrice']['Amount'])){
    	$tempVariant['saleprice'] = number_format($offerListing['SalePrice']['Amount']/100, 2, ".", "");
    	} else {
    	$tempVariant['saleprice'] = $tempVariant['price'];
    	}
    	}
    	}

    	if(isset($variationItem['VariationAttributes']['VariationAttribute'])){
    	if(isset($variationItem['VariationAttributes']['VariationAttribute'][0]['Name'])){
    	foreach($variationItem['VariationAttributes']['VariationAttribute'] as $variationAttributeObj){
    	if(isset($variationAttributeObj['Name']) && $variationAttributeObj['Name'] == $option1name){
    	$tempVariant['option1val'] = $variationAttributeObj['Value'];
    	}
    	if(isset($variationAttributeObj['Name']) && $variationAttributeObj['Name'] == $option2name){
    	$tempVariant['option2val'] = $variationAttributeObj['Value'];
    	}
    	if(isset($variationAttributeObj['Name']) && $variationAttributeObj['Name'] == $option3name){
    	$tempVariant['option3val'] = $variationAttributeObj['Value'];
    	}
    	}
    	} else {
    	if(isset($variationItem['VariationAttributes']['VariationAttribute']['Name']) && $variationItem['VariationAttributes']['VariationAttribute']['Name'] == $option1name){
    	$tempVariant['option1val'] = $variationItem['VariationAttributes']['VariationAttribute']['Value'];
    	}
    	}
    	}
    	// fetch Images
    	$tempVariant['images'] = array();	
    	if(isset($variationItem['ImageSets']['ImageSet'])){
    	$imageSets = $variationItem['ImageSets']['ImageSet'];
    	if(is_array($imageSets) && isset($imageSets[0]['SmallImage'])){
    	foreach($imageSets as $imageSet){
    	$imageUrl = getImageURL($imageSet);
    	$imgCat = 'variant';
    	if(isset($imageSet['@attributes']['Category'])){
    	$imgCat = trim($imageSet['@attributes']['Category']);
    	}
    	if(strlen($imageUrl) > 0){
    	if($imgCat == 'primary'){
    	array_unshift($tempVariant['images'], $imageUrl);
    	} else {
    	$tempVariant['images'][] = $imageUrl;
    	}
    	}
    	}
    	} else {
    	$imageUrl = getImageURL($imageSets);
    	$imgCat = 'variant';
    	if(isset($imageSets['@attributes']['Category'])){
    	$imgCat = trim($imageSets['@attributes']['Category']);
    	}
    	if(strlen($imageUrl) > 0){
    	if($imgCat == 'primary'){
    	array_unshift($tempVariant['images'], $imageUrl);
    	} else {
    	$tempVariant['images'][] = $imageUrl;
    	}
    	}	
    	}
    	}	
    	$tempVariant['images'] = array_unique($tempVariant['images']);	
    	$variantsArr[] = $tempVariant;
    	}
    	}

    	// TODO: Add validation using laravel validator: Rohit
    	$productObject = new Product(array("title" => $title, "description" => $description, "feature1" => $feature1, "feature2" => $feature2, "feature3" => $feature3, "feature4" => $feature4, "feature5" => $feature5, "option1name" => $option1name, "option2name" => $option2name, "option3name" => $option3name, "brand" => $brand, "product_type" => $product_type, "parentasin" => $parentasin, "raw_data" => json_encode($item)));
    	$currUser->products()->save($productObject);	
    	foreach($variantsArr as $v){
    	$variantObject = new ProductVariant(array("sku" => $v['asin'], "asin" => $v['asin'], "option1val" => $v['option1val'], "option2val" => $v['option2val'], "option3val" => $v['option3val'], "price" => $v['price'], "saleprice" => $v['saleprice'], "condition" => $v['condition'], "amazonofferlistingid" => $v['offerlistingId'], "weight" => $v['weight'], "weight_unit" => "lb", "detail_page_url" => $detailPageURL, "user_id" => $currUser->id));
    	$productObject->variants()->save($variantObject);
    	
    	foreach ($v['images'] as $imageUrl) {
    	$productImageObject = new ProductImage(array("asin" => $v['asin'], "imgurl" => $imageUrl));
    	$variantObject->images()->save($productImageObject);
    	}
    	}
    	return insertToShopify($user_id,$productObject,$conn);
    	return array("status" => true, "msg" => "success");
	}

	function createmany($parentasin)
    {
    	$temp = explode(",", $parentasin);
    	$count = count($temp);
    	if($count == 0){
    	return response()->json(['error' => ["msg"=>["Please choose atleast one product."]]], 406);
    	} else if($count > 10){
    	return response()->json(['error' => ["msg"=>["Please choose less than 10 products."]]], 406);
    	}

        	$currUser = Auth::User();
    	$amzKey = $currUser->amzKey()->first();
    	if(!$amzKey){
    	return response()->json(['error' => ["msg"=>['Amazon AWS keys are required for this operation.']]], 406);
    	}
    	foreach($temp as $t){
    	$pcount = $currUser->products()->where('parentasin', $t)->count();	
    	if($pcount != 0) {
    	return response()->json(['error' => ["msg"=>['Product already exist.']]], 406);
    	break;
    	}	
    	}
    	
    	try {
    	$conf = new GenericConfiguration();
    	$client = new \GuzzleHttp\Client();
    	$request1 = new \ApaiIO\Request\GuzzleRequest($client);
    	$conf
    	->setCountry($amzKey->country)
    	->setAccessKey($amzKey->aws_access_id)
    	->setSecretKey($amzKey->aws_secret_key)
    	->setAssociateTag($amzKey->associate_id)
    	->setRequest($request1)
    	->setResponseTransformer(new \ApaiIO\ResponseTransformer\XmlToArray());

    	$app = new ApaiIO($conf);
    	$lookup = new Lookup();
    	$lookup->setItemId($temp);
    	$lookup->setResponseGroup(array('Large', 'Small', 'Variations'));
    	
    	$response = $app->runOperation($lookup);
    	addlog($response,"INFO");
    	/*if(isset($response['Items']['Item']['Variations']['Item'][0]['ASIN']))
    	{
    	$asin = $response['Items']['Item']['Variations']['Item'][0]['ASIN'];
    	$product_variant = ProductVariant::where('asin', $asin)->get();
    	$length = count($product_variant);
    	if($length != 0)
    	{
    	return response()->json(['error' => ["msg"=>['Product Already Exist.']]], 406);
    	}
    	}*/	
    	if(isset($response['Items']['Request']['Errors']['Error']))
    	{
    	return response()->json(['error' => ["msg" => ['There was some error processing the request. Please contact customer support.']]], 406);
    	}
    	if(!isset($response['Items']['Item'])){
    	return response()->json(['error' => ["msg" => ['There was some error processing the request. Please contact customer support.']]], 406);
    	}

    	$items = $response['Items'];
    	if(isset($items['Item'][0]['ASIN'])){
    	$errmsg = '';
    	foreach($items['Item'] as $item){
    	$res = insertItemWithVariants($item);
    	if(!$res['status']){
    	$errmsg = $errmsg.$res['msg'].'<br />';
    	}
    	}
    	if($errmsg != ''){
    	return response()->json(['error' => ["msg"=>[$errmsg]]], 406);
    	} else {
    	return response()->json(['success'], 200);
    	}
    	} else if(isset($items['Item']['ASIN'])) {
    	$res = insertItemWithVariants($items['Item']);
    	if($res['status']){
    	return response()->json(['success'], 200);
    	} else {
    	return response()->json(['error' => ["msg"=>[$res['msg']]]], 406);
    	}
    	} else {
    	return response()->json(['error' => ["msg"=>['There was some error processing the request. Please contact customer support.']]], 406);
    	}
    	} catch (\Exception $e) {
            	return response()->json(['error' => ["msg"=>['There were some error processing this request. Please try again.']]], 406);
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
 	function insertToShopify($user_id,$productObject,$conn){
    	addlog("insert to shopify: " . $user_id, "INFO");
    	$currUser = getUser($user_id,$conn);
    	//echo(json_encode($currUser));
    	$settingObject = getSettings($user_id,$conn);
    	addlog("setting accessed","INFO");
    	$published = false;	
    	$tags = array();
    	$vendor = "";
    	$product_type = "";
    	$inventory_policy = null;
    	$defquantity = 1;
    	$markupenabled = 0;
    	$markuptype = 'FIXED';
    	$markupval = 0;
    	$markupround = 0;
    	$location_id = "";
    	addlog(json_encode($settingObject),"SETTING");
    	if($settingObject){
    	$tags = $settingObject['tags'];
    	if(strlen($tags) > 0){
    	$tags = explode(",", $tags);
    	} else {
    	$tags = array();
    	}
    	if( $settingObject['published'] == 1){
    	$published = true;
    	}
    	if(strlen($settingObject['vendor']) > 0){
    	$vendor = $settingObject['vendor'];
    	}
    	if(strlen($settingObject['product_type']) > 0){
    	$product_type = $settingObject['product_type'];
    	}
    	if($settingObject['inventory_policy']!="NO"){
    	$inventory_policy = $settingObject['inventory_policy'];
    	}
    	if(isset($settingObject['defquantity'])){
    	$defquantity = $settingObject['defquantity'];
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
    	if(isset($settingObject['markupround']) && $settingObject['markupround'] == 1){
    	$markupround = true;
    	}
    	if(isset($settingObject['shopifylocationid'])){
    	$location_id = $settingObject['shopifylocationid'];
    	}
    	}
    	
    	$shopurl = $currUser['shopurl'];
    	$token = $currUser['token'];
    	$product_id = $productObject['product_id'];
    	//print_r($productObject);
    	$title = $productObject['title'];
    	$description = $productObject['description'];
    	$brand = $productObject['brand'];
    	if($vendor != ''){
    	$brand = $vendor;
    	}	
    	$productType = $productObject['product_type'];
    	if($product_type != ''){
    	$productType = $product_type;
    	}
    	$feature1 = $productObject['feature1'];
    	//addlog("feature123".$feature1,"INFO");
    	$feature2 = $productObject['feature2'];
    	$feature3 = $productObject['feature3'];
    	$feature4 = $productObject['feature4'];
    	$feature5 = $productObject['feature5'];
    	$featureStr = "";
    	if(strlen($feature1) > 0){
    	$featureStr .= '<li>'.$feature1.'</li>';
    	}
    	if(strlen($feature2) > 0){
    	$featureStr .= '<li>'.$feature2.'</li>';
    	}
    	if(strlen($feature3) > 0){
    	$featureStr .= '<li>'.$feature3.'</li>';
    	}
    	if(strlen($feature4) > 0){
    	$featureStr .= '<li>'.$feature4.'</li>';
    	}
    	if(strlen($feature5) > 0){
    	$featureStr .= '<li>'.$feature5.'</li>';
    	}
    	if(strlen($featureStr) > 0){
    	$featureStr = '<br /><ul>'.$featureStr.'</ul>';
    	}
    	$description = $description.$featureStr;
    	    $variantQuery = "select * from product_variants where product_id = ".$product_id." and user_id = ".$user_id." and block = 0 and duplicate = 0 and shopifyvariantid = ''";
    	    addlog($variantQuery,"QUERY");
    	        $variantResult = $conn->query("select * from product_variants where product_id = ".$product_id." and user_id = ".$user_id." and block = 0 and duplicate = 0 and shopifyvariantid = ''");
    	$noOfVariants = $variantResult->num_rows;
    	//print_r($variantResult->fetch_assoc()); 
    	//echo $noOfVariants;
    	
    	
    	
    	$variantsArr = $variantResult; //$productObject->variants();
    	$vCount = $variantResult->num_rows;
    	if($vCount == 1){
    	    addlog("count is  1<br/>","INFO");
    	$variantObject = $variantResult->fetch_assoc();
    	$sku = $variantObject['sku'];
    	$weight = $variantObject['weight'];
    	$weight_unit = $variantObject['weight_unit'];
    	$productid = $variantObject['product_id'];
    	$price = $variantObject['price'];
    	$saleprice = $variantObject['saleprice'];
    	$variant_id = $variantObject['id'];
    	//echo  $variant_id."   pankaj <br/>";
    	if($markupenabled == true){
    	$price = applyPriceMarkup($price, $markuptype, $markupval, $markupround);
    	$saleprice = applyPriceMarkup($saleprice, $markuptype, $markupval, $markupround);
    	}	
    	$detail_page_url = $variantObject['detail_page_url'];
    	addlog("select * from product_images where user_id = ".$user_id." and variant_id = ".$variantObject['id'],"INFO");
    	$imageResult = $conn->query("select imgurl from product_images where variant_id = ".$variantObject['id']);
    	//print_r($imageResult->num_rows);
    	$imagesArr = array();
    	if($imageResult->num_rows > 0){
    	$imagesArr = $imageResult->fetch_assoc();
    	addlog($imageResult->num_rows,"INFO");
                }else{
                    addlog("no data Found for Images","ERROR");
                }	
    	//$imagesArr = $variantObject->images()->get();
    	$images = array();
    	$position = 1;
    	foreach($imagesArr as $imageObject){
    	    //addlog(json_encode($imageObject),"Images To Be Uploaded Object");
        	$imgUrl = $imageObject;
        	if(!($strpos = stripos($imgUrl, "no-image"))){
        	$images[] = array("src" => trim($imgUrl), "position" => $position++);
        	}
    	}
    	//print_r($imagesArr); 
    	$productMetafields = array(array("key" => "isavailable", "value" => 1, "type" => "number_integer", "namespace" => "isaac"));
		$variantMetafields = array(array("key" => "buynowurl", "value" => $detail_page_url, "type" => "single_line_text_field", "namespace" => "isaac"));
    	addlog(json_encode($images),"Images To Be Uploaded");
    	$data = array(
    	"product"=>array(
    	"title"=> $title,
    	"body_html"=> $description,
    	"vendor"=> $brand,
    	"product_type"=> $productType,
    	"published"=> $published,
    	"tags"=> $tags,
    	"published_scope" => "global",
    	"images"=>$images,
    	"metafields" => $productMetafields,
    	"variants"=>array(
    	array(
    	"sku"=>$sku,
    	"position"=>1,
    	"price"=>number_format($saleprice, 2, '.', ''),
    	/*"compare_at_price"=>number_format($price, 2, '.', ''),*/
    	"inventory_policy"=>"deny",
    	"fulfillment_service"=> "manual",
    	"inventory_management"=> $inventory_policy,
    	"taxable"=>true,	
    	"weight" => $weight,
    	"weight_unit" => $weight_unit,
    	"barcode" => '',
    	"requires_shipping"=> true,
    	"metafields" => $variantMetafields
    	)
    	)
    	)
    	);
    	/*if($currUser->id == 279 || $currUser->id == 374){
    	$data["product"]["template_suffix"] = "amazon";
    	}*/
    	$response = addShopifyProduct($token, $shopurl, $data);
    	addLog(json_encode($response),"INFO");
    	//print_r($response);
    	if($response){	
    	    addlog("<br/><h2>Response Generated</h2><br/>","INFO");
    	    //print_r($response);
    	    //addlog("<br/>updating .....<br/>","INFO");
    	$shopifyproductid = $response["id"];
    	$shopifyvariantid = $response["variants"][0]["id"];
    	$shopifyinventoryid = $response["variants"][0]["inventory_item_id"];
    	addlog($shopifyproductid,"SHOPIFYPRODUCTID");
    	addlog($shopifyvariantid,"SHOPIFYVARINATID");
    	addlog($shopifyinventoryid,"SHOPIFYINVENTORYID");
    	//addlog("<br/>Response data parsed properly upto line 239<br/>","INFO");
    	//$variantObject->shopifyvariantid = $shopifyvariantid;
    	//$variantObject->shopifyproductid = $shopifyproductid;
    	//$variantObject->shopifyinventoryid = $shopifyinventoryid;
    	//addlog("<br/>Response data saving properly upto line 244<br/>","INFO");
    	//echo $response['handle'];
    	
    	if($location_id == ""){
    	    $location_id = getLocationId($token, $shopurl, $shopifyinventoryid);
    	    if(!$conn->query("UPDATE `setting` SET `shopifylocationid` = '".$location_id."' WHERE `user_id` = '".$user_id."'")){
    	       addlog("Error In Udating shopifylocationid in setting ","ERROR"); 
    	    }
    	}
    	
    	if($conn->query("UPDATE `product_variants` SET `handle`='".$response['handle']."',`shopifylocationid`='".$location_id."', `shopifyproductid`='".$shopifyproductid."',`shopifyvariantid`='".$shopifyvariantid."',`shopifyinventoryid`='".$shopifyinventoryid."' WHERE  `product_id`=".$product_id." AND `user_id`=".$user_id)){
    	    addlog("Product Variant Table Updated Properly<br/>","INFO");
    	}else{
    	    addlog("Product Variant Table Update Error","ERROR");
    	}	
    	    //echo $variantObject->save();   
    	
    	//addlog("<br/>Response data saved properly upto line 245<br/>","INFO");
    	$rowid = "";
    	if($row = $conn->query("UPDATE `products` SET `shopifyproductid`= '".$shopifyproductid."',`status`='Imported' WHERE  `product_id`=".$product_id." AND `user_id`=".$user_id)){
    	    addlog("Product table Updated Properly<br/>".$product_id,"INFO");
    	    $rowid= $product_id;
    	}else{
            addlog("Error While Updating shopify product id in products table","ERROR");
        }
    	
    	//if($conn->query("UPDATE `importToShopify` SET `status`=1 WHERE  `product_id`=".$product_id." AND `user_id`=".$user_id)){
    	//    addlog("Status Updated Properly<br/>","INFO");
    	//}
    	//$productObject->shopifyproductid = $shopifyproductid;
    	//$productObject->save();
    	
    	addlog("location_id : ".$location_id,"INFO");
    	addlog("Response data parsed properly upto line 247<br/>","INFO");
    	addlog("quantity".$defquantity,"INFO");
    	if($inventory_policy == "shopify" && $location_id != ""){
    	    addlog("<h3>updating shopify inventory</h3><br/>","INFO");
    	    addlog("quantity".$defquantity,"INFO");
    	   updateShopifyInventory($token, $shopurl, $shopifyinventoryid, $location_id, $defquantity,$user_id,$conn,$rowid);
    	}else{
    	    //addlog("<br/><h3>Error in invetory or location at line 250</h3><br/>","INFO");
    	    //addlog("inventory policy : ".$inventory_policy."<br/>","INFO");
    	    //addlog("location id : ".$location_id."<br/>","INFO");
    	}
    	return $shopifyproductid;
    	} else {
                    //addlog("<br/><br/><h1>Error while excepting Response from shopify at line 245</h1>","INFO");
    	}
    	} else {
    	addlog($vCount,"ERROR"); 
    	}
    	$skuconsumed = $currUser['skuconsumed'];
    	$currUser['skuconsumed'] = $skuconsumed + 1;
    	return "";
	//$currUser->save();
	}
	function updateShopifyInventory($token, $shopurl, $inventory_item_id, $location_id, $quantity,$user_id,$conn,$rowid){
	    //addlog("entered to inventory funtion<br/>","INFO");
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
    	if( (strstr(($response_arr[0]), "200")) || (strstr(($response_arr[1]), "200")) || (strstr(($response_arr[2]), "200")) ) {
    	return true;
    	} else if((strstr(($response_arr[0]), "HTTP/1.1 403 Forbidden")) || (strstr(($response_arr[0]), "HTTP/1.1 422 Unprocessable Entity"))) {
    	    //addlog("entered in forbidden handler line 419","INFO");
    	$new_location_id = getLocationId($token, $shopurl, $inventory_item_id);
    	if(!$conn->query("UPDATE `setting` SET `shopifylocationid` = '".$new_location_id."' WHERE `user_id` = '".$user_id."'")){
    	    addlog("Error In Udating shopifylocationid in setting ","ERROR"); 
    	}
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
    	$apiurl = "https://".$shopurl."/admin/api/2022-01/inventory_levels.json?inventory_item_ids=".$inventory_item_id;
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
        addlog("data imported for url ".$url."is ".json_encode($resposnce),"INFO");
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
    	//addlog("Error adding product with SKU - ".$product["product"]["variants"]["sku"].", Err Details: ".serialize($response_arr), "ERROR");
    	}
    	return null;
	}
	

    function fetchProductDataWithRetry($url){
        $temp= get_html_scraper_api_content($url);
        if($temp == "ERROR" || strpos($temp,"Error") || $temp == [])
        {
                                sleep(2);
                                $temp = get_html_scraper_api_content($url);
                                 if($temp == "ERROR" || strpos($temp,"Error")){
                                   sleep(4);
                                   $temp = fetchProductData($url);
                                    if($temp == "ERROR" || strpos($temp,"Error")){
                                     // sleep(7);
                                    //  $failedAsin = $asin; /*getDataFromDoc(fetchProductData($url))*/;
                                      return $temp;
                                                                                                             }
                                    else{
                                       $data[$asin] = $temp;
                                        }
                                                                                                         }
                                   else{
                                       $data[$asin] = $temp;
                                       }
        }
       else{
                                    $data[$asin] = $temp;
            }
         return  $temp;

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
    
    function get_html_scraper_api_content($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com/?key=bccfd6a1043eeef4b878ab667efac22b&url=".urlencode($url));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  "Accept: application/json"
		));

		$response = curl_exec($ch);
		curl_close($ch);
		echo $response;
		exit;
		return $response;
	}
	
    function fetchProductData($url){
        sleep(1);
        global $conn;
        $randomCode = GenCode(6);
        $url =  str_replace("\r", '', $url);
        $proxy_port = "22225";//60099";lum-customer-hl_c27ea444-zone-static
        $proxy_ip = "zproxy.lum-superproxy.io";//172.84.122.33";lum-customer-hl_c27ea444-zone-static
        $loginpassw = "lum-customer-hl_c27ea444-zone-static-session-".$randomCode.":0w29dqxs53i7";

        $user_agentObj = $conn->query("SELECT * FROM `user_agents` WHERE id=".rand(0,220));
        if($user_agentObj->num_rows > 0){
            $user_agents = $user_agentObj->fetch_assoc();
            $user_agent = $user_agents['ua_string'];
        }else{
            $user_agent = "Mozilla/6.0 (Macintosh; I; Intel Mac OS X 11_7_9; de-LI; rv:1.9b4) Gecko/2012010317 Firefox/10.0a4";
        }

    	$ch = curl_init();
    	
    	
    	
    	 curl_setopt( $ch, CURLOPT_USERAGENT,$user_agent );
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
            curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTPS');
            curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                //"Postman-Token: 47dd397c-06d7-461f-9873-b317e948d580",
                "cache-control: no-cache",
                "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
                "Connection: close",
                "X-Forwarded-For: ".$proxy_ip,
                "Content-Length: 0",
                "Cookie: timezone=Asia/Kolkata;",
                "Accept-Language: en-US,en;q=0.9",
                "Accept-Encoding: gzip, deflate, br",
                //"Host: www.amazon.com",
              ));
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt( $ch, CURLOPT_ENCODING, "UTF8" );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
            curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
            curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    	    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            //addlog("http code is ".json_encode(curl_getinfo($ch)),"INFO");
    	

      
        $response = curl_exec($ch);
	    $err = curl_error($ch);
	    curl_close($ch);
       // addlog("data crawled via luminiato ".json_encode($response),"INFO");
        if ($err) {
         addlog("data crawled via luminiato erroe ".$err,"INFO");    
	      return "ERROR";
	    } else {
	      return $response;
	    }
    }

	function addlog($message, $type){
    	global $logfile;
    	$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
    	fwrite($logfile, $txt);
	}
	
	?>