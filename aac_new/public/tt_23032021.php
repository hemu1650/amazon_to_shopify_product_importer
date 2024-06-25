<?php
   // die("");
    ini_set('memory_limit', '-1');
	set_time_limit(0);

	$logfile = fopen("logs/fetchReviewsNew.txt", "a+") or die("Unable to open log file!");
	addlog("Execution Started", "INFO");

	$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
    if(!$conn){        
		addlog('Database Connection Error', "ERROR");
        die("Database Connection Error");
    }
	mysqli_set_charset($conn, "utf8");

	$cronQuery = $conn->query("select isrunning from crons where crontype = 'fetchReviewnew'");
    $cronrow = $cronQuery->fetch_assoc();
    if($cronrow['isrunning'] == 1) {
        @mail("khariwal.rohit@gmail.com", "fetchReviewnew: Cron already running", "fetchReviewnew: Cron already running");
        die("Connection failure!");
    }

	$conn->query("update crons set lastrun = now(), isrunning = 1 where crontype = 'fetchReviewnew'");
 
	$res = $conn->query("SELECT * FROM fetchReviews WHERE status = 0 and user_id in (3208,9440,9494,9228,10099,11475,11648,14621) and user_id = 1 limit 0, 1");
	if($res->num_rows > 0){
		while($row = $res->fetch_assoc()){
			$totalpage = $row['totalpage'];
			$reviewsCnt = $row['reviews_cnt'];
			$processedPage = $row['processed_page'];
            $currentpage = $row['currentpage'];
            $asin = $row['product_asin'];
            $user_id = $row['user_id'];
            addlog($asin, "INFO");
            fetchReviewsAndStoreInDB(trim($asin), $user_id, $totalpage, $currentpage, $reviewsCnt, $processedPage);
		}            
	}
	$conn->query("update crons set lastrun = now(), isrunning = 0 where crontype = 'fetchReviewnew'");
	
	function fetchReviewsAndStoreInDB($asin, $user_id, $totalpage, $currentpage, $oldReviewsCnt, $processedPage) {
		global $conn;
		$variantResult = $conn->query("select * from product_variants where asin = '".mysqli_real_escape_string($conn, $asin)."' and user_id = ".$user_id);
		if($variantResult->num_rows < 1){
			return false;
		}
		$variantRow = $variantResult->fetch_assoc();
		$page_url = $variantRow['detail_page_url'];
        $page_url = str_replace("https://", "", $page_url);
        $page_url = explode("?", $page_url);
        $page_url = explode("/", $page_url[0]);
        $base_url = $page_url[0];
        addlog($base_url, "BASE URL");
		
		$linkPrefix = 'https://'.$base_url.'/product-reviews/'.$asin;
		$firstReviewURL = $linkPrefix.'/ref=cm_cr_dp_d_show_all_btm?ie=UTF8&sortBy=recent&reviewerType=all_reviews&pageSize=20';
		echo $firstReviewURL;
		$sourceData = fetchReviewData($firstReviewURL);
		// Re-try if issue in fetching page
		$pattern = '/<div\s*class="a-row\s*a-spacing-mini\s*customerReviewsTitle">(.*)<\/div>/ismU';
		$resp = preg_match_all($pattern, $sourceData, $result);
		if(!$resp){
			sleep(5);
			$sourceData = fetchReviewData($firstReviewURL);
			$pattern = '/<div class="a-row a-spacing-mini customerReviewsTitle">(.*)<\/div>/ismU';
			$resp = preg_match_all($pattern, $sourceData, $result);
			if(!$resp){
			    $conn->query("update fetchReviews set status = 1, reviews_cnt = 0 where user_id = ".$user_id." and product_asin = '".mysqli_real_escape_string($conn, $asin)."'");
				return false;
			}
		}

		// Count total reviews
		$reviewsCount = -1;
		$totalCountPattern = '/<span\s*data-hook="cr-filter-info-review-count"\s*class="a-size-base">(.*)<\/span>/ismU';
		$resp = preg_match_all($totalCountPattern, $sourceData, $result);
		print_r($result);exit;
		if($resp){
			$resp1 = preg_match_all('/\s([0-9]*)\s.*/sU', str_replace(",", "", $result[1][0]), $result1);
			if($resp1){
				$reviewsCount = $result1[1][0];
			} else {
			    $resp1 = preg_match_all('/\s([0-9]+)$/sU', str_replace(",", "", $result[1][0]), $result1);
			    if($resp1){
				    $reviewsCount = $result1[1][0];
			    }
			}	
		}
		if($reviewsCount == -1){		
			// No reviews available
			$conn->query("update fetchReviews set status = 1, reviews_cnt = 0 where user_id = ".$user_id." and product_asin = '".mysqli_real_escape_string($conn, $asin)."'");
			return false;
		}
		/*if($oldReviewsCnt >= $reviewsCount){		
			// No new reviews available
			$conn->query("update fetchReviews set status = 1 where user_id = ".$user_id." and asin = '".mysqli_real_escape_string($conn, $asin)."'");
			return true;
		}*/

		$reviewPagesCount = ceil((int)$reviewsCount / 20);
		if($reviewPagesCount < 2){
			$reviewsArr = processReviewLink($sourceData);		
			insertIntoDB($user_id, $asin, $reviewsArr);			
			$conn->query("update fetchReviews set status = 1, reviews_cnt = ".$reviewsCount.", processed_page = 1 where user_id = ".$user_id." and product_asin = '".mysqli_real_escape_string($conn, $asin)."'");
			return true;
		}
		if($oldReviewsCnt < 1){
			$reviewsArr = processReviewLink($sourceData);		
			insertIntoDB($user_id, $asin, $reviewsArr);
			$i = 2;
			for(; ($i <= $reviewPagesCount || $i < 25); $i++){
				$link = $linkPrefix.'/ref=cm_cr_arp_d_paging_btm_next_2?pageNumber='.$i.'&sortBy=recent&pageSize=20';
				$sourceData = fetchReviewData($link);
				// Re-try if issue in fetching page
				$pattern = '/<div class="a-row a-spacing-mini customerReviewsTitle">(.*)<\/div>/ismU';
				$resp = preg_match_all($pattern, $sourceData, $result);
				if(!$resp){
					sleep(5);
					$sourceData = fetchReviewData($firstReviewURL);
					$pattern = '/<div class="a-row a-spacing-mini customerReviewsTitle">(.*)<\/div>/ismU';
					$resp = preg_match_all($pattern, $sourceData, $result);
					if(!$resp){
						$conn->query("update fetchReviews set reviews_cnt = ".$reviewsCount.", processed_page = ".($i-1)." where user_id = ".$user_id." and product_asin = '".mysqli_real_escape_string($conn, $asin)."'");	
						return false;
					}
				}
				$reviewsArr = processReviewLink($sourceData);
				insertIntoDB($user_id, $asin, $reviewsArr);
			}
			if($i >= $reviewPagesCount || $i > 25){
				$conn->query("update fetchReviews set status = 1, reviews_cnt = ".$reviewsCount.", processed_page = ".($i-1)." where user_id = ".$user_id." and product_asin = '".mysqli_real_escape_string($conn, $asin)."'");
			} else {
				$conn->query("update fetchReviews set reviews_cnt = ".$reviewsCount.", processed_page = ".($i-1)." where user_id = ".$user_id." and product_asin = '".mysqli_real_escape_string($conn, $asin)."'");	
			}	
			return true;
		}

		$additionalReviewsCount = $reviewsCount - $oldReviewsCnt;
		$additionalReviewPagesCount = ceil((int)$additionalReviewsCount / 20);
		if($additionalReviewsCount > 0){
			$reviewsArr = processReviewLink($sourceData);		
			insertIntoDB($user_id, $asin, $reviewsArr);
			$i = 2;
			for(; ($i <= $additionalReviewPagesCount || $i > 25); $i++){
				$link = $linkPrefix.'/ref=cm_cr_arp_d_paging_btm_next_2?pageNumber='.$i.'&sortBy=recent&pageSize=20';
				$sourceData = fetchReviewData($link);
				// Re-try if issue in fetching page
				$pattern = '/<div class="a-row a-spacing-mini customerReviewsTitle">(.*)<\/div>/ismU';
				$resp = preg_match_all($pattern, $sourceData, $result);
				if(!$resp){
					sleep(5);
					$sourceData = fetchReviewData($firstReviewURL);
					$pattern = '/<div class="a-row a-spacing-mini customerReviewsTitle">(.*)<\/div>/ismU';
					$resp = preg_match_all($pattern, $sourceData, $result);
					if(!$resp){						
						return false;
					}
				}
				$reviewsArr = processReviewLink($sourceData);
				insertIntoDB($user_id, $asin, $reviewsArr);
			}
		}
		$i = $processedPage + $additionalReviewPagesCount;
		for(; ($i <= $reviewPagesCount || $i > 25); $i++){
			$link = $linkPrefix.'/ref=cm_cr_arp_d_paging_btm_next_2?pageNumber='.$i.'&sortBy=recent&pageSize=20';
			$sourceData = fetchReviewData($link);
			// Re-try if issue in fetching page
			$pattern = '/<div class="a-row a-spacing-mini customerReviewsTitle">(.*)<\/div>/ismU';
			$resp = preg_match_all($pattern, $sourceData, $result);
			if(!$resp){
				sleep(5);
				$sourceData = fetchReviewData($firstReviewURL);
				$pattern = '/<div class="a-row a-spacing-mini customerReviewsTitle">(.*)<\/div>/ismU';
				$resp = preg_match_all($pattern, $sourceData, $result);
				if(!$resp){				
					$conn->query("update fetchReviews set reviews_cnt = ".$reviewsCount.", processed_page = ".($i-1)." where user_id = ".$user_id." and product_asin = '".mysqli_real_escape_string($conn, $asin)."'");	
					return false;
				}
			}
			$reviewsArr = processReviewLink($sourceData);
			insertIntoDB($user_id, $asin, $reviewsArr);
		}
		if($i >= $reviewPagesCount || $i > 25){
			$conn->query("update fetchReviews set status = 1, reviews_cnt = ".$reviewsCount.", processed_page = ".($i-1)." where user_id = ".$user_id." and product_asin = '".mysqli_real_escape_string($conn, $asin)."'");
		} else {
			$conn->query("update fetchReviews set reviews_cnt = ".$reviewsCount.", processed_page = ".($i-1)." where user_id = ".$user_id." and product_asin = '".mysqli_real_escape_string($conn, $asin)."'");	
		}	
		return true;
	}
	
	function insertIntoDB($user_id, $asin, $reviewsArr){
		global $conn;
		print_r($reviewsArr);
		foreach($reviewsArr as $reviewsObj){
			$reviewID = $reviewsObj['reviewID'];
			$checkExistance = $conn->query("select id from reviews where user_id = ".$user_id." and review_id = '".mysqli_real_escape_string($conn, $reviewID)."'");
			if($checkExistance->num_rows > 0){
				continue;
			}			
			echo "insert into reviews(product_asin, review_id, authorName, reviewDate, reviewDetails, reviewTitle, rating, imgArr, verifiedFlag, FoundHelpful, user_id, created_at, updated_at) values('".mysqli_real_escape_string($conn, $asin)."', '".mysqli_real_escape_string($conn, $reviewsObj['reviewID'])."', '".mysqli_real_escape_string($conn, $reviewsObj['authorName'])."', '".mysqli_real_escape_string($conn, $reviewsObj['reviewDate'])."', '".mysqli_real_escape_string($conn, $reviewsObj['reviewDetails'])."', '".mysqli_real_escape_string($conn, $reviewsObj['reviewTitle'])."', '".mysqli_real_escape_string($conn, $reviewsObj['rating'])."', '".mysqli_real_escape_string($conn, $reviewsObj['imgArr'])."', '".mysqli_real_escape_string($conn, $reviewsObj['varifiedFlag'])."', '".mysqli_real_escape_string($conn, $reviewsObj['FoundHelpful'])."', ".$user_id.", now(), now());<br /><br /><br />";
			$res = $conn->query("insert into reviews(product_asin, review_id, authorName, reviewDate, reviewDetails, reviewTitle, rating, imgArr, verifiedFlag, FoundHelpful, user_id, created_at, updated_at) values('".mysqli_real_escape_string($conn, $asin)."', '".mysqli_real_escape_string($conn, $reviewsObj['reviewID'])."', '".mysqli_real_escape_string($conn, $reviewsObj['authorName'])."', '".mysqli_real_escape_string($conn, $reviewsObj['reviewDate'])."', '".mysqli_real_escape_string($conn, $reviewsObj['reviewDetails'])."', '".mysqli_real_escape_string($conn, $reviewsObj['reviewTitle'])."', '".mysqli_real_escape_string($conn, $reviewsObj['rating'])."', '".mysqli_real_escape_string($conn, $reviewsObj['imgArr'])."', '".mysqli_real_escape_string($conn, $reviewsObj['varifiedFlag'])."', '".mysqli_real_escape_string($conn, $reviewsObj['FoundHelpful'])."', ".$user_id.", now(), now())");   if(!$res){
				echo $conn->error;
			}
		}
	}

	function processReviewLink($pageSource){
		global $conn;
		$reviewsArr = array();
		////  Regex Expression to crap the review details from the reviews page
		$reviewIdPattern = '/<div\s*id="(.*)-review-card"\s*class="a-row\s*a-spacing-none">/ismU';
        $author2 = '/<a\s*data-hook="review-author"\s*class="a-size-base a-link-normal author"\s*href=".*">(.*)<\/a><\/span><span/';
        $author = '/<div class="a-profile-content"><span class="a-profile-name">(.*)<\/span><\/div><\/a><\/div>/';
        $review_date = '/<span\s*data-hook="review-date"\s*class="a-size-base a-color-secondary review-date">(.*)<\/span><div/';
        $review_details = '/<div\s*data-hook="review-collapsed"\s*aria-expanded="false" class="a-expander-content a-expander-partial-collapse-content">(.*)<\/div><div\s* class="a-expander-header a-expander-partial-collapse-header"/';
        $rating = '/<i\s*data-hook="review-star-rating"\s*class=".*"><span class="a-icon-alt">(.*)<\/span><\/i>/';
        $review_title = '/<a\s*data-hook="review-title"\s*class="a-size-base a-link-normal review-title a-color-base a-text-bold"\s*href=".*">(.*)<\/a><\/div><span/';
        $review_date2 = '/<span\s*data-hook="review-date"\s*class="a-size-base\s*a-color-secondary\s*review-date">(.*)<\/span><\/div><div\s*class="a-row\s*a-spacing-mini/';
        $review_details2 = '/<span\sdata-hook="review-body"\s*class=".*">\s*(.*)\s*<\/span>\s*<\/div>/sU';
        $varified_flag = '/<span\s*data-hook="avp-badge"\s*class="a-size-mini\s*a-color-state\s*a-text-bold">(.*)<\/span>/sU';
        $helpful = '/<span\s*data-hook="helpful-vote-statement"\s*class="a-size-base a-color-tertiary\s*cr-vote-text">(.*) helpful<\/span>/sU';

		$reviewsContainerPattern = '/<div\s*id="cm_cr-review_list"\s*class="a-section\s*a-spacing-none\s*review-views\s*celwidget"\s*data-cel-widget="cm_cr-review_list">(.*)<\/ul>\s*<\/div>\s*<\/span>\s*<\/div>/msU';
		$reviewsContainerPattern1 = '/<div\s*id="cm_cr-review_list"\s*class="a-section\s*a-spacing-none\s*review-views\s*celwidget">(.*)<\/ul>\s*<\/div>\s*<\/span>\s*<\/div>/msU';
		$reviewsContainerPattern2 = '/<div\s*id="cm_cr-review_list"\s*class="a-section\s*a-spacing-none\s*review-views\s*celwidget">(.*)<\/div>\s*<div\s*class="a-spinner-wrapper\s*reviews-load-progess\s*aok-hidden\s*a-spacing-top-large">/msU';
		$resp = preg_match_all($reviewsContainerPattern, $pageSource, $result);
		if(!$resp){
			$resp = preg_match_all($reviewsContainerPattern1, $pageSource, $result);
		}
		if(!$resp){
			$resp = preg_match_all($reviewsContainerPattern2, $pageSource, $result);
		}
		if($resp){			
			$reviewsListPattern = '/<div\s*id=".*"\s*data-hook="review"\s*class="a-section\sreview\saok-relative">(.*)<\/div><\/div><\/div><\/div><\/div>/smU';
			$resp1 = preg_match_all($reviewsListPattern, $result[1][0], $result2);  
			foreach ($result2[1] as $key1 => $value1) {
				$tmp = array();

				$res = preg_match_all($reviewIdPattern, $value1, $result);
                if($res){
					$tmp['reviewID'] = $result[1][0];
				}
                $res = preg_match_all($author, $value1, $result);
                if($res){
					$tmp['authorName'] = $result[1][0];
				}
				
				$res = preg_match_all($review_date, $value1, $result);
                if($res){
					$re = preg_match_all("/([a-zA-Z]*)\s([0-9]{2}),\s([0-9]{4})/",$result[1][0],$resdate);
                    if($re){
						$tmp['reviewDate'] = $resdate[2][0]."-".substr($resdate[1][0],0,3)."-".$resdate[3][0];
					} else {
						$re = preg_match_all("/([a-zA-Z]*)\s([0-9]),\s([0-9]{4})/sU",$result[1][0],$resdate);
                        if($re){
							$tmp['reviewDate'] = $resdate[2][0]."-".substr($resdate[1][0],0,3)."-".$resdate[3][0];
						}
					}
				}
                
				$res = preg_match_all($review_details2, $value1, $result);
                if($res){
					$tmp['reviewDetails'] = $result[1][0];
				}
                
				$res = preg_match_all($review_details2, $value1, $result);
                if($res){
					$tmp['reviewDetails'] = $result[1][0];
                }
                
				$res = preg_match_all('/<a\s*data-hook="review-title"\s*class=".*" href=".*">\s*(.*)\s*<\/a>/sU', $value1, $result);
                if($res){
					if(strpos($result[1][0], "span")){
						$re = preg_match_all('/<span\s*class=".*">\s*(.*)\s*<\/span>/sU',$result[1][0],$match);
						if($re){
							$tmp['reviewTitle'] = $match[1][0];
						} else {
							$tmp['reviewTitle'] = $result[1][0];
						}
					} else {
						$tmp['reviewTitle'] = $result[1][0];
					}
				}
                $res = preg_match_all($rating, $value1, $result);
                if($res){
					$re = preg_match_all("/([1-9]\.[0-9])/U",$result[1][0],$rate);
                    if($re){
						$tmp['rating'] = $rate[1][0];  
					} else {
						$tmp['rating'] = $result[1][0];
					}
				}
                
				$res = preg_match_all($varified_flag, $value1, $result);
                if($res){
					$tmp['varifiedFlag'] = 'Verified';
				} else {
					$tmp['varifiedFlag'] = 'Un-Verified';
				}
                
				$res = preg_match_all('/<img\salt="review image"\ssrc="(.*)"\sdata-hook="review-image-tile"\sclass="review-image-tile"\sheight="88"\swidth="100%">/U',$value1,$review_images);
                if($res){
					$tmp['imgArr'] = implode("|",$review_images[1]);
				} else {
					$tmp['imgArr'] = "";
				}
                
				$res = preg_match_all($helpful, $value1, $result);
                if($res){
					$tmp['FoundHelpful'] = $result[1][0];
				} else {
					$tmp['FoundHelpful'] = '';
				}
				$tmp = array_map("trim", $tmp);
				$reviewsArr[] = $tmp;          
			}       
		}
	//	@mail("khariwal.rohit@gmail.com", "review data", json_encode($reviewsArr));
		return $reviewsArr;
	}

    function fetchReviewData($url) {	
        $proxy_port = "22225";//60099";
        $proxy_ip = "zproxy.lum-superproxy.io";//172.84.122.33";
        $loginpassw = "lum-customer-infoshore-zone-unblocked-country-us-unblocker:c48dcf0d7196";//pankajnarang81:6aLrPXoy";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_PROXY, 'http://zproxy.lum-superproxy.io:22225');
        curl_setopt($curl, CURLOPT_PROXYUSERPWD, 'lum-customer-infoshore-zone-unblocked-country-us-unblocker:c48dcf0d7196');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        $response  = curl_exec($curl);
    	curl_close($curl);
		return $response;
	}
	
	/*function fetchReviewData($url) {		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com/?key=e6585b7c2f1d8cc1842f3a77b4187ad0&url=".urlencode($url));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  "Accept: application/json"
		));

		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}*/

	function addlog($message, $type="INFO"){
		global $logfile;
		$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
		fwrite($logfile, $txt);
	}

	addlog("Execution Finished", "INFO");
	fclose($logfile);
?>