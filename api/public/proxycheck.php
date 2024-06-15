<?php

	
	$logfile = fopen("logs/proxycheck.txt", "a+") or die("Unable to open log file!");
	addlog("Execution Started", "INFO");

	$handle = fopen("logs/proxyCheckResult.csv", "a+") or die("Unable to open result file!");
	$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
	set_time_limit(0);
	if(!$conn){
		die("database connection Error");
	}
	//$handle = fopen("failedProxy.txt", "w");
	
		$result = mysqli_query($conn,"SELECT *FROM `proxy`");
		addlog($result->num_rows);
		if($result->num_rows > 0){
			echo $result->num_rows;
			while ($row= mysqli_fetch_array($result)) {
				$id = $row["id"];
				$proxy_ip = $row["url"];
				$proxy_port = $row["port"];
				$username = $row["username"];
				$password = $row["password"];
				echo $proxy_ip;echo "<br/>";
				$time = time();
				$pc = proxyCheck($proxy_port,$proxy_ip,$username.":".$password);
				if($pc == "not working"){
					fwrite($handle, $id.",Not Working \n");
					if(!$conn->query("UPDATE `proxy` SET `flag`=-1 WHERE `id`=".$id)){
						addlog("Error in Updating Proxy flag","ERROR");
					}
				}else{
					fwrite($handle, $id.",Working \n");
					if($conn->query("UPDATE `proxy` SET `flag`=0 WHERE `id`=".$id)){
						addlog("Proxy workin so  Updating Proxy flag to 0 again","INFO");
					}
				}
				sleep(2);
			}
		}
		/*$result = mysqli_query($conn,"SELECT *FROM `proxy` WHERE `flag`=-1");
		if($result->num_rows >= 99){
			@mail("khariwal.rohit@gmail.com", "AAC:Proxy Failure Urgent Fix Needed - All Proxy Failed  ", "ProxyCheck: failed all proxies");
			@mail("pankajnarang81@gmail.com", "AAC:Proxy Failure Urgent Fix Needed - All Proxy Failed  ", "ProxyCheck: failed all proxies");
			@mail("amitsingh987987@gmail.com", "AAC:Proxy Failure Urgent Fix Needed - All Proxy Failed " , "AAC:Proxy Failure Urgent Fix Needed - All Proxy Failed  ", "ProxyCheck: failed all proxies");
		}*/
		fclose($handle);
	function proxyCheck($proxy_port,$proxy_ip,$loginpassw){
		global $conn;
		$url = "https://www.amazon.com/Sealer-Plastic-Handheld-Portable-Resealer/dp/B0797L6DBM";
	    //$cookie = tempnam ("/tmp", "CURLCOOKIE");
	    if($uaObj = $conn->query("SELECT * FROM `user_agents` WHERE `id` = ".rand(1,250))){
	    	$user_agents = $uaObj->fetch_assoc();
	    	$user_agent = $user_agents['ua_string'];
	    }else{
	    	addlog("Error in Accessing User Agents","DATABASE ERROR");
	    	$user_agent = "Mozilla/6.0 (Macintosh; I; Intel Mac OS X 11_7_9; de-LI; rv:1.9b4) Gecko/2012010317 Firefox/10.0a4";
	    }
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
            "Host: www.amazon.com",
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
	    $doc = new \DOMDocument();
	    libxml_use_internal_errors(true);
	    $doc->loadHTML($content);
	    $content1 = $doc->saveHTML($doc->getElementById("productTitle"));
	    if($content1 == ""){
	    	$content1 = $doc->saveHTML($doc->getElementById("title"));
	    }
	    $content1 = getjsonrdata($content,$url);
	    if($content1 != null){
	    	//$content1 = str_replace("\n","",trim($content1));
	    	//$res = preg_match_all('/<h2 class="as-title-block-left">(.*)<\/h2>/sU', $content1, $matches);
	    	/*if(strlen($content1) >= 40){
	    		echo $content1;
		    	echo "working .".$proxy_ip;
		    	return "working";
	    	}else{
	    		echo $content1;
		    	echo "Not working .".$proxy_ip;
		    	return "not working";
	    	}*/
	    		$content1 = json_decode($content1,true);
	    		echo $content1['Title'];
		    	echo "<br/>working .".$proxy_ip;
		    	return "working";
	    }else{
	    	echo $content;
	    	echo "<b/>Not working .".$proxy_ip;
	    	return "not working";
	    }
	    //print_r($content);
	}

	function proxyUsingfile($proxy_port,$proxy_ip,$loginpassw){
		$auth = $loginpassw;

			$aContext = array(
			    'https' => array(
			        'proxy' => "tcp://".$proxy_ip.":".$proxy_port,
			        'request_fulluri' => true,
			        'header' => "Authorization: Basic $auth",
			    ),
			);
			$cxContext = stream_context_create($aContext);

			$sFile = file_get_contents("https://www.amazon.com/dp/B07BRFQ4GP", False, $cxContext);
			echo $sFile;
	}

	function getjsonrdata($data,$producturl) {
		$doc = new \DOMDocument();
	    $doc->recover = true;
	    $errors = libxml_get_errors();
	    $saved = libxml_use_internal_errors(true);
	    if(strlen($data)<200){
	        return null;
	    }
	    $doc->loadHTML($data);
	    $handle = fopen('Product.html', 'wr');
	    fwrite($handle, $data);
		$xp = new \DOMXPath($doc);
	    $dataArr = array();
	    $dataArr['url'] = $producturl;
		$titleBlock = $doc->getElementById('title');
    	//$dataArr['Title'] = $xp->evaluate('string(.//*[@class="a-size-extra-large"])', $titleBlock);
	    $dataArr['Title'] = trim( $xp->evaluate('string(.//*[@class="a-size-large"])', $titleBlock) );
	   	//addlog($dataArr['Title'],"TITLE");	
	   	if(strlen($dataArr['Title'])==0){
	   		$dataArr['Title'] = trim($xp->evaluate('string(//*[@id="productTitle"])', $titleBlock));
	   		if(strlen($dataArr['Title'])==0){
	   			$dataArr['Title'] = trim($xp->evaluate('string(//*[@id="title"])', $titleBlock));
	   			if(strlen($dataArr['Title'])==0){
	   				$dataArr['Title'] = trim($xp->evaluate('string(//*[@id="productTitle"])', $titleBlock));
	   				if(strlen($dataArr['Title'])==0)
	   					return null;
	   			}
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
	   	  //Log::info($pricediv);
	   	  $salepricediv = trim($pricediv[0]);
	   	}
	    //Log::info($salepricediv);
	    if($salepricediv == ""){
	   	 $salepricediv =$xp->evaluate('string(//div[@id="cerberus-data-metrics"]//@data-asin-price)',$doc);
	   	  if(strpos($salepricediv, '-') !== false){
		   	  $pricediv = explode("-", $salepricediv);
		   	  //Log::info($pricediv);
		   	  $salepricediv = trim($pricediv[0]);
	   	  }
	    }
	    $dataArr['price'] = $salepricediv;
	   
	    $originalpricediv = $xp->evaluate('//td[contains(text(),"List Price") or contains(text(),"M.R.P") or contains(text(),"Price")]/following-sibling::td/text()', $doc);
	   
	    $dataArr['list_price'] = $salepricediv;
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
	function addlog($message, $type="INFO"){
	if(is_array($message)){
		$message = json_encode($message);
	}
	global $logfile;
	$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
	fwrite($logfile, $txt);
}
addlog("Execution Finished", "INFO");
fclose($logfile);
?>