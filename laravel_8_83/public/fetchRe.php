<?php
 header('Access-Control-Allow-Origin: *');

    $logfile = fopen("fetchReviews.txt", "a+") or die("Unable to open log file!");
	addlog("Execution Started", "INFO");
	
    ini_set('memory_limit', '640M');
  set_time_limit(0);
  //store();


    $conn = mysqli_connect('localhost','sbimgrix_root','XfCHw{J4V@QB','sbimgrix_amz_crawler');
    if($conn){
        addlog('Database connected',"INFO");
    }else{
        addlog('Database Connection Error',"ERROR");
        die("Database Connection Error");
    }
        $res = mysqli_query($conn,"SELECT * FROM `fetchReviews` WHERE `status`=0");
        if($res > 0){
            while($row = mysqli_fetch_assoc($res)){
              $asin = $row['product_asin'];
              $user_id = $row['user_id'];
              addlog($asin,"INFO");
              store($asin,$user_id,$conn);
            }
        }else{
            addlog("No data Found","ERROR");
        }

    function show($url) {
        $ch = curl_init();
        //echo "<br/><h1>$url</h1><br/>";
        $url = "http://api.scraperapi.com/?key=bccfd6a1043eeef4b878ab667efac22b&url=".urlencode($url);
        //echo "<br/><h1>".$url."</h1><br/>";
        curl_setopt($ch, CURLOPT_URL,
        "http://api.scraperapi.com/?key=bccfd6a1043eeef4b878ab667efac22b&url=".urlencode($url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Accept: html/text"
        ));

        $response = curl_exec($ch);   
        return $response;
    }

    function store($asin,$user_id,$conn){

        $ASIN = [$asin];//,"B01IFMWGSS","B01MQ3OEDU","B01IFMBN6O","B01MY5PTJR","B06XT95QXQ","B0721LGYVS","B074XTTLWJ","B074XT57S4","B078846SX9","B075JWYV4G","B075K3NSKL","B075JXGBTB","B073WK5N1X","B0755N8C66","B07CGHCN6R","B07C7PXXZS","B07CGKWK8S","B07C7B7PJ5","B073WK34JN","B073WJQ3LL","B073WJCSBW","B072JXZGK8","B01M9C8M0C","B01MQ3JNT9","B07C57JKR7","B077LG1F18","B077LHK3QG","B01NAHTL0U","B01N1R803X","B01N5EB2Q7","B0721S9JH1","B072N7G48J","B01MXX3SM1","B01MQQ2P4W","B01M2Z8KD6","B07G5KRPRB","B01L8P56Q8","B01MCVNN3E","B01IDOV198","B01IMTWV16","B078MFBDQZ","B07G9JX2XY","B07FRW5PX7","B07FYR3RMK","B078SNXRJJ","B07C5992QM","B07G4FC8XR","B077LG1F16","B01N5EC5K6","B072R5B6S4"];
        $document = [];
        $authors_data = [];
        $review_date_data = [];
        $review_details_data = [];
        $review_title_data = [];
        $rating_data = [];

        
        $pages = 0;
        $author2 = '/<a\s*data-hook="review-author"\s*class="a-size-base a-link-normal author"\s*href=".*">(.*)<\/a><\/span><span/';
        $author = '/<div class="a-profile-content"><span class="a-profile-name">(.*)<\/span><\/div><\/a><\/div>/';
        $review_date = '/<span\s*data-hook="review-date"\s*class="a-size-base a-color-secondary review-date">(.*)<\/span><div/';
        $review_details = '/<div\s*data-hook="review-collapsed"\s*aria-expanded="false" class="a-expander-content a-expander-partial-collapse-content">(.*)<\/div><div\s* class="a-expander-header a-expander-partial-collapse-header"/';
        $rating = '/<i\s*data-hook="review-star-rating"\s*class=".*"><span class="a-icon-alt">(.*)<\/span><\/i>/';
        $review_title = '/<a\s*data-hook="review-title"\s*class="a-size-base a-link-normal review-title a-color-base a-text-bold"\s*href=".*">(.*)<\/a><\/div><span/';
        $review_date2 = '/<span\s*data-hook="review-date"\s*class="a-size-base\s*a-color-secondary\s*review-date">(.*)<\/span><\/div><div\s*class="a-row\s*a-spacing-mini/';
        $review_details2 = '/<span\s*data-hook="review-body"\s*class="a-size-base review-text">(.*)<\/span><\/div><div/U';
        $all_reviews = '/<a\s*data-hook="see-all-reviews-link-foot"\s*class="a-link-emphasis a-text-bold"\s*href="(.*)">(.*)<\/a><\/div>/';
        $varified_flag = '/<span\s*data-hook="avp-badge"\s*class="a-size-mini\s*a-color-state\s*a-text-bold">(.*)<\/span>/sU';
        $helpful = '/<span\s*data-hook="helpful-vote-statement"\s*class="a-size-base a-color-tertiary\s*cr-vote-text">(.*) helpful<\/span>/sU';
        $link = '';
        $pageNum = 1;
        $pages = 0;
        $product_name = '';
        $product_url = '';
        $links = array();

        //$asinTmp = 'B01NAHTL0U';

        foreach ($ASIN as $key => $asinTmp) {
            $content = show('https://www.amazon.com/gp/product/'.$asinTmp);
                $document[$asinTmp]['productURL'] = 'https://www.amazon.com/gp/product/'.$asinTmp.'?th=1&psc=1';
                $res = preg_match_all($all_reviews, $content, $result);
                    if($res){
                        $reviews_more_link = $result[1];
                        $reviews_more = $result[2];
                        $res1 = preg_match_all('/\/(.*)\/product-reviews\/(.*)\/.*/', $reviews_more_link[0], $matches);
                                   // dd($matches);
                        
                        if($res1){
                            //dd($matches);
                            $document[$asinTmp]['productName'] = $matches[1][0];
                            $link = 'https://www.amazon.com/'.$matches[1][0].'/product-reviews/'.$matches[2][0];
                            $res2 = preg_match_all('/\d/', $reviews_more[0], $match);
                            if($res2){
                                $tmp = '';

                                    //dd($result);
                                foreach ($match[0] as $key) {
                                    $tmp .= $key;
                                }
                                // print_r($reviews_more_link);
                                $pages = ceil((int)$tmp/10);
                                //dd($pages);
                                for($i=1;$i<=$pages;$i++){
                                    $links[] = $link.'/ref=cm_cr_dp_d_show_all_btm_4?pageNumber='.$i; 
                                }
                            }else{
                                addlog("no matches found at line 110","ERROR");
                            }
                        }else{
                            addlog("Products Can't find at line 113","ERROR");
                        }
                    }

                    if($pages+1 > 0){
                      $i = 0;
                        $time_start = microtime(true);
                        foreach ($links as $key => $lnk) {
                          $i++;
                          echo $lnk;echo "<br/>";
                            $content = show($lnk);
                          $rs = preg_match_all('/<div\s*id="cm_cr-review_list"\s*class="a-section a-spacing-none\s*review-views\s*celwidget">(.*)<\/ul><\/div><\/span><\/div>/msU', $content, $ma);
                            $time_start1 = microtime(true);
                            if($rs){
                                $key2 =$ma[1][0];
                            }else{
                                $res = preg_match_all($author, $content, $result);
                                  if($res){
                                    $tmp['authorName'] = $result[1][0];
                                  }
                                $res = preg_match_all($review_date, $content, $result);
                                  if($res){
                                    $tmp['reviewDate'] = $result[1][0];
                                  }
                                $res = preg_match_all($review_details2, $content, $result);
                                  if($res){
                                    $tmp['reviewDetails'] = $result[1][0];
                                  }
                                $res = preg_match_all($review_title, $content, $result);
                                  if($res){
                                    $tmp['reviewTitle'] = $result[1][0];
                                  }
                                $res = preg_match_all($rating, $content, $result);
                                  if($res){
                                    $tmp['rating'] = $result[1][0];
                                  }
                                $res = preg_match_all($varified_flag, $content, $result);
                                  if($res){
                                    $tmp['varifiedFlag'] = 'Varified';
                                  }else{
                                    $tmp['varifiedFlag'] = 'Un-Varified';
                                  }
                                $res = preg_match_all($helpful, $content, $result);
                                  if($res){
                                    $tmp['FoundHelpful'] = $result[1][0];
                                  }else{
                                    $tmp['FoundHelpful'] = '';
                                  }
                                  
                                  /*Reviews::create([
                                        "product_asin" =>  $asinTmp,
                                        "authorName" => $tmp['authorName'],
                                        "reviewDate" => $tmp['reviewDate'],
                                        "reviewDetails" => $tmp['reviewDetails'],
                                        "reviewTitle" => $tmp['reviewTitle'],
                                        "rating" => $tmp['rating'],
                                        "verifiedFlag" => $tmp['varifiedFlag'],
                                        "FoundHelpful" => $tmp['FoundHelpful'],
                                        "user_id" => $user_id
                                  ]);*/
                                  $query = "INSERT INTO `reviews`(`product_asin`, `authorName`, `reviewDate`, `reviewDetails`, `reviewTitle`, `rating`, `verifiedFlag`, `FoundHelpful`, `user_id`,`created_at`,`updated_at`) VALUES ('".$asinTmp."','".str_replace("'","\'",$tmp['authorName'])."','".$tmp['reviewDate']."','".str_replace("'","\'",$tmp['reviewDetails'])."','".str_replace("'","\'",$tmp['reviewTitle'])."','".$tmp['rating']."','".$tmp['varifiedFlag']."','".$tmp['FoundHelpful']."',".$user_id.",NOW(),NOW())";
                                  $r = mysqli_query($conn,$query);
                                  if($r == 0){
                                    addlog("Error in database update atline 174","ERROR");
                                    addlog($query,"INFO");
                                    //addlog($conn->error(),"ERROR");
                                    die("data saving error");
                                  }else{
                                      if($j == 0){
                                          $res = mysqli_query($conn,"UPDATE `fetchReviews` SET `status`='1',`updated_at`=NOW() WHERE user_id = ".$user_id." AND product_asin = '".$asin."'");
                                      }
                                      addlog("Review Updated");
                                      echo "review updated";
                                  }
                                $document[$asinTmp]['productReviews'][] = $query;
                                addlog("Error in finding review Container 129","ERROR");
                            }
                          $rs1 = preg_match_all('/<div\s*id=".*"\s*data-hook="review"\s*class="a-section review">(.*)<\/div><\/div><\/div><\/div><\/div>/smU', $key2, $m);
                            if($rs1){
                              //dd($m);
                              $j = 0;
                              foreach ($m[1] as $key1 => $value1) {
                                $tmp = array();
                                $res = preg_match_all($author, $value1, $result);
                                  if($res){
                                    $tmp['authorName'] = $result[1][0];
                                  }
                                $res = preg_match_all($review_date, $value1, $result);
                                  if($res){
                                    $tmp['reviewDate'] = $result[1][0];
                                  }
                                $res = preg_match_all($review_details2, $value1, $result);
                                  if($res){
                                    $tmp['reviewDetails'] = $result[1][0];
                                  }
                                $res = preg_match_all($review_title, $value1, $result);
                                  if($res){
                                    $tmp['reviewTitle'] = $result[1][0];
                                  }
                                $res = preg_match_all($rating, $value1, $result);
                                  if($res){
                                    $tmp['rating'] = $result[1][0];
                                  }
                                $res = preg_match_all($varified_flag, $value1, $result);
                                  if($res){
                                    $tmp['varifiedFlag'] = 'Varified';
                                  }else{
                                    $tmp['varifiedFlag'] = 'Un-Varified';
                                  }
                                $res = preg_match_all($helpful, $value1, $result);
                                  if($res){
                                    $tmp['FoundHelpful'] = $result[1][0];
                                  }else{
                                    $tmp['FoundHelpful'] = '';
                                  }
                                  
                                  /*Reviews::create([
                                        "product_asin" =>  $asinTmp,
                                        "authorName" => $tmp['authorName'],
                                        "reviewDate" => $tmp['reviewDate'],
                                        "reviewDetails" => $tmp['reviewDetails'],
                                        "reviewTitle" => $tmp['reviewTitle'],
                                        "rating" => $tmp['rating'],
                                        "verifiedFlag" => $tmp['varifiedFlag'],
                                        "FoundHelpful" => $tmp['FoundHelpful'],
                                        "user_id" => $user_id
                                  ]);*/
                                  $query = "INSERT INTO `reviews`(`product_asin`, `authorName`, `reviewDate`, `reviewDetails`, `reviewTitle`, `rating`, `verifiedFlag`, `FoundHelpful`, `user_id`,`created_at`,`updated_at`) VALUES ('".$asinTmp."','".str_replace("'","\'",$tmp['authorName'])."','".$tmp['reviewDate']."','".str_replace("'","\'",$tmp['reviewDetails'])."','".str_replace("'","\'",$tmp['reviewTitle'])."','".$tmp['rating']."','".$tmp['varifiedFlag']."','".$tmp['FoundHelpful']."',".$user_id.",NOW(),NOW())";
                                  $r = mysqli_query($conn,$query);
                                  if($r == 0){
                                    addlog("Error in database update atline 174","ERROR");
                                    addlog($query,"INFO");
                                    //addlog($conn->error(),"ERROR");
                                    die("data saving error");
                                  }else{
                                      if($j == 0){
                                          $res = mysqli_query($conn,"UPDATE `fetchReviews` SET `status`='1',`updated_at`=NOW() WHERE user_id = ".$user_id." AND product_asin = '".$asin."'");
                                      }
                                      addlog("Review Updated");
                                      //echo "review updated";
                                  }
                                $document[$asinTmp]['productReviews'][] = $query;
                              }
                              if($res != 0){
                                addlog('fetchReview status Changed',"INFO");
                              }else{
                                addlog('Error In Saving Fetch Review Status',"INFO");
                                die("FetchReview Updation Error");
                              }
                                $j++;
                            }else{
                              addlog("Data Scrapping Error at line 179","ERROR");
                              echo "Data Scrapping Error at line 179 <br/>";
                              die("Data Scrapping Error");
                            }
                            $time_end1 = microtime(true);
                            $execution_time = ($time_end1 - $time_start1);
                            addlog('Total Execution Time: '.$execution_time.' Sec ',"INFO");
                        }
                        $time_end = microtime(true);
                        $execution_time = ($time_end - $time_start);
                        addlog('Total Execution Time: '.$execution_time.' Sec',"INFO");
                    }
        }
        //echo "running";
        
    } 
    function addlog($message, $type){
		global $logfile;
		$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
		fwrite($logfile, $txt);
	}

	addlog("Execution Finished", "INFO");
	fclose($logfile);
?>