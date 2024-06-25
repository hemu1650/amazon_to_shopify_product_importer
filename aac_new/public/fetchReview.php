<?php
    die("");
	header('Access-Control-Allow-Origin: *');
    $logfile = fopen("logs/fetchReviews.txt", "a+") or die("Unable to open log file!");
	addlog("Execution Started", "INFO");
    ini_set('memory_limit', '1024M');
	set_time_limit(0);
	//$conn = new mysqli('localhost', 'root', '', 'aac_dev');		
	$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
    if($conn){
        addlog('Database connected',"INFO");
    }else{
        addlog('Database Connection Error',"ERROR");
        die("Database Connection Error");
    }         
	
	$cronQuery = $conn->query("select isrunning from crons where crontype = 'fetchReview'");
    $cronrow = $cronQuery->fetch_assoc();
    if($cronrow['isrunning'] == 1){
        @mail("pankajnarang81@gmail.com", "applypricingrule: Cron already running", "fetchReview: Cron already running");
        die("Connection failure!");
    }

    $conn->query("update crons set lastrun = now(), isrunning = 1 where crontype = 'fetchReview'");
 
        $res = $conn->query("SELECT * FROM `fetchReviews` WHERE `status`=0 and user_id = 13625 limit 0, 10");
        if($res->num_rows > 0){
            while($row = $res->fetch_assoc()){
              $totalpage = $row['totalpage'];
              $currentpage = $row['currentpage'];
              $asin = $row['product_asin'];
              $user_id = $row['user_id'];
              addlog($asin,"INFO");
              fetchReviewsAndStoreInDB(trim($asin),$user_id,$conn,$totalpage,$currentpage);
            }
            
        }else{
            addlog("No data Found","ERROR");
        }
        
        $res = $conn->query("SELECT * FROM `failed_review_pages` WHERE `status`=0 ");
        if($res->num_rows > 0){
            while($data = $res->fetch_assoc()){
                retryScrapAndSave(0,$data['url'],get_html_scraper_api_content($data['url']),$data['asin'],$data["user_id"]);
                if(!$conn->query("UPDATE `failed_review_pages` SET `status`= 1 WHERE id = ".$data['id'])){
                    addlog("Error While changing the status of the failed reviews pages","ERROR");
                }
            }
        }
        $conn->query("update crons set lastrun = now(), isrunning = 0 where crontype = 'fetchReview'");
    function retryScrapAndSave($j,$url,$cont,$asin,$user_id){
        
        $author2 = '/<a\s*data-hook="review-author"\s*class="a-size-base a-link-normal author"\s*href=".*">(.*)<\/a><\/span><span/';
        $author = '/<div class="a-profile-content"><span class="a-profile-name">(.*)<\/span><\/div><\/a><\/div>/';
        $review_date = '/<span\s*data-hook="review-date"\s*class="a-size-base a-color-secondary review-date">(.*)<\/span><div/';
        $review_details = '/<div\s*data-hook="review-collapsed"\s*aria-expanded="false" class="a-expander-content a-expander-partial-collapse-content">(.*)<\/div><div\s* class="a-expander-header a-expander-partial-collapse-header"/';
        $rating = '/<i\s*data-hook="review-star-rating"\s*class=".*"><span class="a-icon-alt">(.*)<\/span><\/i>/';
        $review_title = '/<a\s*data-hook="review-title"\s*class=".*" href=".*">\s*<span\s*class=".*">(.*)<\/span>\s*<\/a>/';
        $review_date2 = '/<span\s*data-hook="review-date"\s*class="a-size-base\s*a-color-secondary\s*review-date">(.*)<\/span><\/div><div\s*class="a-row\s*a-spacing-mini/';
        $review_details2 = '/<span\s*data-hook="review-body"\s*class="a-size-base review-text">(.*)<\/span><\/div><div/U';
        $varified_flag = '/<span\s*data-hook="avp-badge"\s*class="a-size-mini\s*a-color-state\s*a-text-bold">(.*)<\/span>/sU';
        $helpful = '/<span\s*data-hook="helpful-vote-statement"\s*class="a-size-base a-color-tertiary\s*cr-vote-text">(.*) helpful<\/span>/sU';
        
        
        global $conn;
       
            if($cont != ""){
                ////  If page Content is not blank then scrap reviews list container
                $rs = preg_match_all('/<div\s*id="cm_cr-review_list"\s*class="a-section a-spacing-none\s*review-views\s*celwidget">(.*)<\/ul><\/div><\/span><\/div>/msU', $cont, $ma);
                $time_start1 = microtime(true);
                if($rs){
                    ////  if Review list container Scrapped Successfully
                    $key2 =$ma[1][0];
                    //echo $key2;echo "<br/><br/>";
                }else{
                    //// if the Reviews List Container Cannot Scrapped Then then Trying using x-path
                    $doc = new \DOMDocument();
                    libxml_use_internal_errors(true) AND libxml_clear_errors();
                    $doc->preserveWhiteSpace = true;
                    $doc->loadHTML($cont);
                    $reviewsContainer = $doc->saveHTML($doc->getElementById('cm_cr-review_list'));
                    $key2 = str_replace(array("\r", "\n"), '', $reviewsContainer);
                    if($key2 == ""){
                        ////   if the Review Container cannot Be scrapped then fetch page once again
                        $cont = get_html_scraper_api_content($lnk);
                        $rs = preg_match_all('/<div\s*id="cm_cr-review_list"\s*class="a-section a-spacing-none\s*review-views\s*celwidget">(.*)<\/ul><\/div><\/span><\/div>/msU', $cont, $ma);
                        if($rs){
                            ////  if Review list container Scrapped Successfully
                            $key2 =$ma[1][0];
                            //echo $key2;echo "<br/><br/>";
                        }else{
                            //// if the Reviews List Container Cannot Scrapped Then then Trying using x-path
                            $doc = new \DOMDocument();
                            libxml_use_internal_errors(true) AND libxml_clear_errors();
                            $doc->preserveWhiteSpace = true;
                            $doc->loadHTML($cont);
                            $reviewsContainer = $doc->saveHTML($doc->getElementById('cm_cr-review_list'));
                            $key2 = str_replace(array("\r", "\n"), '', $reviewsContainer);
                            if($key2 == ""){
                                //// if Review list Container Not Found again
                                addlog("Error in finding review Container 129","ERROR");
                                addlog($key2,"CONTECT TO BEScraped");   
                            }
                        }
                    }
                }
                $rs1 = preg_match_all('/<div\s*id=".*"\s*data-hook="review"\s*class="a-section review">(.*)<\/div><\/div><\/div><\/div><\/div>/smU', $key2, $m);
                if($rs1){
                    //// finding the reviews from the Reviews list Container
                    echo "<h2>fetching data</h2>";echo "<br/>";
                    foreach ($m[1] as $key1 => $value1) {
                        ////  Now Scrapping details for each reviews And Saving to database
                        $tmp = array();
                        $res = preg_match_all($author, $value1, $result);
                        if($res){
                            $tmp['authorName'] = $result[1][0];
                        }
                        $res = preg_match_all($review_date, $value1, $result);
                        if($res){
                            $re = preg_match_all("/([a-zA-Z]*)\s([0-9]{2}),\s([0-9]{4})/",$result[1][0],$resdate);
                            if($re){
                                $tmp['reviewDate'] = $resdate[2][0]."-".substr($resdate[1][0],0,3)."-".$resdate[3][0];
                                // echo $tmp['reviewDate'];
                            }else{
                                $re = preg_match_all("/([a-zA-Z]*)\s([0-9]),\s([0-9]{4})/sU",$result[1][0],$resdate);
                                if($re){
                                    $tmp['reviewDate'] = $resdate[2][0]."-".substr($resdate[1][0],0,3)."-".$resdate[3][0];   
                                }
                                //echo $tmp['reviewDate'];
                            }
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
                                }else{
                                    $tmp['reviewTitle'] = $result[1][0];
                                }
                            }else{
                                $tmp['reviewTitle'] = $result[1][0];
                            }
                        }
                        /*$res = preg_match_all($review_title, $value1, $result);
                        if($res){
                            $tmp['reviewTitle'] = $result[1][0];
                        }*/
                        $res = preg_match_all($rating, $value1, $result);
                        if($res){
                            $re = preg_match_all("/([1-9]\.[0-9])/U",$result[1][0],$rate);
                            if($re){
                                $tmp['rating'] = $rate[1][0];  
                                //echo $tmp['rating'];
                            }else{
                                $tmp['rating'] = $result[1][0];
                                //echo $tmp['rating'];
                            }
                        }
                        $res = preg_match_all($varified_flag, $value1, $result);
                        if($res){
                            $tmp['varifiedFlag'] = 'Verified';
                        }else{
                            $tmp['varifiedFlag'] = 'Un-Verified';
                        }
                        $res = preg_match_all('/<img\salt="review image"\ssrc="(.*)"\sdata-hook="review-image-tile"\sclass="review-image-tile"\sheight="88"\swidth="100%">/U',$value1,$review_images);
                        if($res){
                            $tmp['imgArr'] = implode("|",$review_images[1]);
                        }else{
                            $tmp['imgArr'] = "";
                        }
                        $res = preg_match_all($helpful, $value1, $result);
                        if($res){
                            $tmp['FoundHelpful'] = $result[1][0];
                        }else{
                            $tmp['FoundHelpful'] = '';
                        }
                        ////  Checking for the Existance of the Review
                        $permission = mysqli_query($conn,"SELECT * FROM `reviews` WHERE `product_asin`='$asin' AND `rating`='".$tmp['rating']."' AND `reviewTitle`='".str_replace("'","\'",$tmp['reviewTitle'])."' AND `authorName`='".str_replace("'","\'",$tmp['authorName'])."' AND `user_id`='".$user_id."'");
                        if($arr = mysqli_fetch_array($permission)){
                            ////  if reviews already Exists in database
                            echo "<h4>Review Exists</h4>";
                        }else{
                            //// If Review does Not Exists then storing it to the database
                            $query = "INSERT INTO `reviews`(`product_asin`, `authorName`, `reviewDate`, `reviewDetails`, `reviewTitle`, `rating`, `imgArr` ,`verifiedFlag`, `FoundHelpful`, `user_id`,`created_at`,`updated_at`) VALUES ('".$asin."','".str_replace("'","\'",$tmp['authorName'])."','".str_replace("'","\'",$tmp['reviewDate'])."','".str_replace("'","\'",$tmp['reviewDetails'])."','".str_replace("'","\'",$tmp['reviewTitle'])."','".str_replace("'","\'",$tmp['rating'])."','".str_replace("'","\'",$tmp['imgArr'])."','".str_replace("'","\'",$tmp['varifiedFlag'])."','".str_replace("'","\'",$tmp['FoundHelpful'])."',".$user_id.",NOW(),NOW())";
                            $r = mysqli_query($conn,$query);
                            if($r == 0){
                                //// if Error in Storing Review
                                addlog("Error in database update atline 174","ERROR");
                                addlog($query,"INFO");
                                echo "Error in database update atline 174 <br/>";
                                //addlog($conn->error(),"ERROR");
                                //die("data saving error");
                            }else{
                                addlog("Review Updated","INFO");
                                echo "review updated";
                            }
                        }
                    }
                }
            }
     }    
     function fetchReviewsAndStoreInDB($asin,$user_id,$conn,$totalpage,$currentpage){
         $variantObj = $conn->query("SELECT * FROM `product_variants` WHERE asin = '".$asin."' and user_id = $user_id");
         if($variantObj->num_rows > 0){
             $variant = $variantObj->fetch_assoc();
             $page_url = $variant['detail_page_url'];
             $page_url = str_replace("https://","",$page_url);
             $page_url = explode("?",$page_url);
             $page_url = explode("/",$page_url[0]);
             $base_url = $page_url[0];
         }else{
            addlog("base url Not Found Replasing hardcoded");
            $base_url = "www.amazon.com";
         }
         addlog($base_url,"BASE URL");
         echo $base_url;
        addlog("Start Execution","STORE METHOD");
        $ASIN = [$asin];//,"B01IFMWGSS","B01MQ3OEDU","B01IFMBN6O","B01MY5PTJR","B06XT95QXQ","B0721LGYVS","B074XTTLWJ","B074XT57S4","B078846SX9","B075JWYV4G","B075K3NSKL","B075JXGBTB","B073WK5N1X","B0755N8C66","B07CGHCN6R","B07C7PXXZS","B07CGKWK8S","B07C7B7PJ5","B073WK34JN","B073WJQ3LL","B073WJCSBW","B072JXZGK8","B01M9C8M0C","B01MQ3JNT9","B07C57JKR7","B077LG1F18","B077LHK3QG","B01NAHTL0U","B01N1R803X","B01N5EB2Q7","B0721S9JH1","B072N7G48J","B01MXX3SM1","B01MQQ2P4W","B01M2Z8KD6","B07G5KRPRB","B01L8P56Q8","B01MCVNN3E","B01IDOV198","B01IMTWV16","B078MFBDQZ","B07G9JX2XY","B07FRW5PX7","B07FYR3RMK","B078SNXRJJ","B07C5992QM","B07G4FC8XR","B077LG1F16","B01N5EC5K6","B072R5B6S4"];
        //$document = [];
        $authors_data = [];
        $review_date_data = [];
        $review_details_data = [];
        $review_title_data = [];
        $rating_data = [];

        
        $pages = 0;
        $all_reviews = '/<a\s*data-hook="see-all-reviews-link-foot"\s*class="a-link-emphasis a-text-bold"\s*href="(.*)">(.*)<\/a>/U';
        $link = '';
        $pageNum = 1;
        $pages = 0;
        $product_name = '';
        $product_url = '';
        $links = array();
        $newpage = 0;

        
        addlog('https://'.$base_url.'/gp/product/'.$asin,"PASSING TO fetchDataFromURL METHOD");
        $content = get_html_scraper_api_content('https://'.$base_url.'/gp/product/'.$asin);
       
        addlog('fetching reviews for'.$asin,"INFO");
        addlog('fetching reviews for'.$asin.' --- '.$content,"INFO");
            $res = preg_match_all($all_reviews, $content, $result);/// finding the all reviews container
            if($res){
                addlog('Parsing Content',"INFO");
                $reviews_more_link = $result[1];
                addlog("review more link".$reviews_more_link[0],"REVIEWS MORE LINK");
                $reviews_more = $result[2];
                //Scrapping link script and review asin sometime it differs from main asin will be used for new links
                $res1 = preg_match_all('/\/(.*)\/product-reviews\/(.*)\/.*/', $reviews_more_link[0], $matches);
                if($res1){
                    //$document[$asin]['productName'] = $matches[1][0];
                    ///  preparing base link for reviews
                    $link = 'https://'.$base_url.'/'.$matches[1][0].'/product-reviews/'.$matches[2][0];
                    $res2 = preg_match_all('/\s([0-9]*)\s.*/sU', str_replace(",","",$reviews_more[0]), $match);
                    if($res2){
                        // calculating Total No Of Reviews
                        addlog(json_encode($match),"no of reviews");
                        $tmp = '';
                        foreach ($match[0] as $key) {
                            $tmp .= $key;
                        }
                        $pages = ceil((int)$tmp/10);
                        if($pages > 100){
                            if($conn->query("UPDATE `fetchReviews` SET `status`= 11 WHERE product_asin='".$asin."'  and `user_id`=$user_id")){
                              addlog("too many reviews so changing status to 11 ceil".$asin,"Review Limit");
                             // return "";
                            }else{
                                addlog("Error in updating staus in fetch reviews table". "UPDATE `fetchReviews` SET `status`= 11 WHERE product_asin='".$asin."'  and `user_id`=$user_id","Error");
                            }
                        }
                        echo "Pages :".$pages;echo '<br/>';
                            
                        if($totalpage < (int)$tmp){
                            /// this block will work when we already fetched review and serching for new one
                            $newReviews = (int)$tmp - $totalpage;
                            $totalpage = (int)$tmp;
                            $newpage = ceil($newReviews/10);
                            addlog($newpage,"TOTAL NEW PAGES TO SCRAP");
                            for($i=1;$i<=$newpage;$i++){
                                ///  preparing links for extra reviews found
                                $links[] = $link.'/ref=cm_cr_arp_d_paging_btm_next_2?pageNumber='.$i.'&sortBy=recent&pageSize=10'; 
                            }
                            /// updationg new details for total reviews
                            if(!$conn->query("UPDATE `fetchReviews` SET `totalpage`='".$totalpage."' WHERE product_asin='".$asin."' and `user_id`=".$user_id)){
                                addlog("Error While updating the total reviews in fetchreviews table for asin :".$asin,"ERROR");
                            }
                        }else if($totalpage == (int)$tmp){
                            /////   Executes whene some pages remains to fetch previously
                            $pages = ceil($totalpage/10);
                            addlog($pages,"TOTAL PAGES TO SCRAP");
                            if($currentpage < $pages){
                                $i = $currentpage;
                                while($i <= $pages){
                                    $links[] = $link.'/ref=cm_cr_arp_d_paging_btm_next_2?pageNumber='.$i.'&sortBy=recent&pageSize=10';
                                    $i++;
                                }
                            }else{
                                addlog("All Reviews have been fetched previously","ALREADY UPDATED");
                                $res = mysqli_query($conn,"UPDATE `fetchReviews` SET `status`='1',`totalpage`='".$totalpage."',`updated_at`=NOW() WHERE user_id = ".$user_id." AND product_asin = '".$asin."'");
                                if($res != 0){
                                    addlog($res." result code for asin ".$asin,"INFO");
                                    addlog('fetchReview status Changed as all reviews fetched',"INFO");
                                    echo "fetchReview status Changed<br/>";
                                }else{
                                    addlog('Error In Saving Fetch Review Status',"INFO");
                                    echo '<br/><br/><h2>Error In Saving Fetch Review Status<h2>';
                                   //die("FetchReview Updation Error");
                                }          
                            }
                        }
                    }else{
                        $pages = 1;
                        $links[] = $link.'/ref=cm_cr_arp_d_paging_btm_next_2?pageNumber=1&sortBy=recent';
                        addlog("Error while finding total reviews","ERROR");
                        //echo "no matches";echo '<br/>';
                    }
                }else{
                    addlog("Products Can't find at line 113","ERROR");//echo "products cann't find";echo '<br/>';
                }
            }else{
                /// if one regex failes then trying using Different regex finding for data-hook total-review-count
                addlog("regex failes then trying using Different regex finding for data-hook","INFO");
                $resNo = preg_match_all('/<h2 data-hook="total-review-count">(.*)<\/h2>/sU',$content,$resultNo);
                if($resNo){ 
                    ////   finding Data Hook Container for getting total reviews count from the first page
                    addlog(json_encode($result[1][0]),"Review Found");
                    if($result[1][0]=="No customer reviews" || $result[1][0] == ""){
                        /////   if there is no reviews available for perticular ASIN
                        addlog('No Reviews Found For This Product',"RETURN FROM SHOW METHOD");
                        if(!$conn->query("UPDATE `fetchReviews` SET `status`=1 WHERE product_asin='$asin' AND user_id='$user_id'")){
                            ////   Changing Status to 1 for fetch Reviews 
                            addlog('Error While Updating Reviews Table',"ERROR");
                        }
                        if($variantObj = $conn->query("SELECT * FROM `product_variants` where `asin` = '$asin' AND `user_id`=$user_id")){
                            /////   finding the product id to set the product reviews counter
                            if($variantObj->num_rows > 0){
                                $variant = $variantObj->fetch_assoc();
                                if(!$conn->query("UPDATE `products` SET `reviews` = -2 WHERE `product_id` = ".$variant['product_id']." and `user_id`=$user_id")){
                                    ////  setting the product review counter to -2 that indicates no reviews available for the product
                                    addlog("Error in updating products reviews status","DATABASE CONNECTION RROR");
                                }
                            }
                        }
                        addlog(json_encode($resultNo),"RESPONSE ARRAY");
                    }else{
                        ////   if the reviews count found in product then scrap then calculate review pages
                        addlog(json_encode($result),"Review Found");
                        $result[1][0] = str_replace(',',"",$result[1][0]);
                        $resN = preg_match_all('/([0-9]*)\s.*/sU',$result[1][0],$resultN);
                        if($resN){
                            /////    finding the total review count from the string
                            addlog("Parsing Pages for Reviews","INFO");
                            $tmp = $resultN[1][0];
                            $pages = ceil((int)$tmp/10);////   building the pages 
                            if($pages > 50){
                                //////    product hase more then 500 reviews then return not allowed and changing status to 11
                                if($conn->query("UPDATE `fetchReviews` SET `status`= 11 WHERE product_asin='".$asin."'  and `user_id`=$user_id")){
                                    addlog("too many reviews so changing status to 11 ceil".$asin,"Review Limit");
                                    return "";
                                }else{
                                   addlog("Error in updating staus in fetch reviews table". "UPDATE `fetchReviews` SET `status`= 11 WHERE product_asin='".$asin."'  and `user_id`=$user_id","Error");
                                }
                            }
                            //Calculating total Review Pages and links
                            if($totalpage < (int)$tmp){
                                /// if the new riews are more then previously fetched reviews
                                $newReviews = (int)$tmp - $totalpage;
                                $totalpage = (int)$tmp;
                                $newpage = ceil($newReviews/10);
                                addlog($newpage,"TOTAL NEW PAGES TO SCRAP");
                                for($i=1;$i<=$newpage;$i++){
                                    ///   building the pagination url
                                    $links[] = $link.'/ref=cm_cr_arp_d_paging_btm_next_2?pageNumber='.$i.'&sortBy=recent&pageSize=10'; 
                                }   
                            }else if($totalpage == (int)$tmp){
                                /// if some pages remain to fetch in previous scan
                                $pages = ceil($totalpage/10);
                                addlog($pages,"TOTAL PAGES TO SCRAP");
                                if($currentpage < $pages){
                                    ////  building the next pages
                                    $i = $currentpage;
                                    while($i <= $pages){
                                        ////  building the pagination urls
                                        $links[] = $link.'/ref=cm_cr_arp_d_paging_btm_next_2?pageNumber='.$i.'&sortBy=recent&pageSize=10';
                                        $i++;
                                    }
                                }else{
                                    addlog("All Reviews Are being fetched previously","ALREADY UPDATED");
                                }
                            }
                        }
                    }
                }else{
                    ////  To view the Scrapped review for which both regex fails.
                    
                    ////   parsing just to Know Why both above case failed to scrap reviews and also set the flags and status
                    addlog("To view the Scrapped review for which both regex fails","INFO");
                    $doc = new \DOMDocument();
                    libxml_use_internal_errors(true) AND libxml_clear_errors();
                    $doc->preserveWhiteSpace = true;
                    $doc->loadHTML($content);
                    $reviewsContainer = $doc->saveHTML($doc->getElementById('reviews-medley-footer'));
                    addlog($reviewsContainer,"INFO");
                    if($variantObj = $conn->query("SELECT * FROM `product_variants` where `asin` = '$asin' AND `user_id`=$user_id")){
                        ///  finding the product id for the ASIN
                        if($variantObj->num_rows > 0){
                            $variant = $variantObj->fetch_assoc();
                            if($conn->query("UPDATE `products` SET `reviews` = '-2' where `user_id` = $user_id and `product_id`=".$variant['product_id'])){
                                /// Updating the status for the product to -2 
                                addlog("No Reviews Available For".$asin,"INFO");
                            }else{
                                addlog("Error in updating reviews in products tale for asin: ".$asin,"ERROR");
                                addlog("UPDATE `products` SET `reviews` = '-2' where `user_id` = $user_id and `product_id`=".$variant['product_id'],"QUERY");
                            }
                        }
                    }else{
                        addlog("Error in updating reviews in products tale for asin: ".$asin,"ERROR");
                    }
                    if(!$conn->query("UPDATE `fetchReviews` SET `status`='1',`updated_at`=NOW() WHERE user_id = ".$user_id." AND product_asin = '".$asin."'")){
                        ////   changing the fetchreview Status to 1
                        addlog("error in current page counter updation","UPDATION ERROR");
                    }else{
                        addlog("status changed for fetch reviews for asin:".$asin);
                    }
                }
            }
                    
                //// Passing Controll to fetch the review for multipale pages

            $counter = 1;
            if($pages > 0){
                ////  if pagination available then 
                if($currentpage == 0){
                  $currentpage = 1;
                }
                if($currentpage != 0){$counter = $currentpage;}
                parallelFetch(0,$links,$asin,$user_id,$pages,$totalpage,$currentpage);
            }
    } 
    
    
    
    function fetchProductDataWithRetry($url){
        $temp= fetchProductData($url);
        if($temp == "ERROR" || strpos($temp,"Error") || $temp == []){
            sleep(2);
            $temp = fetchProductData($url);
            if($temp == "ERROR" || strpos($temp,"Error")){
                sleep(4);
                $temp = fetchProductData($url);
                if($temp == "ERROR" || strpos($temp,"Error")){
                    // sleep(7);
                    //  $failedAsin = $asin; /*getDataFromDoc(fetchProductData($url))*/;
                    return $temp;
                }else{
                    $data[$asin] = $temp;
                }
            }else{
                $data[$asin] = $temp;
            }
        }else{
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
		curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com/?key=9b0f3100086c345f97d98f1784f75cce&country_code=us&url=".urlencode($url));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  "Accept: application/json"
		));

		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}
	
    function fetchProductData($url){
        return get_html_scraper_api_content($url);
        sleep(1);
        global $conn;
        $randomCode = GenCode(6);
        //return get_html_using_postman($url);
        $url =  str_replace("\r", '', $url);
        $proxy_port = "22225";//60099";lum-customer-hl_c27ea444-zone-static
        $proxy_ip = "zproxy.lum-superproxy.io";//172.84.122.33";lum-customer-hl_c27ea444-zone-static
        $loginpassw = "lum-customer-hl_c27ea444-zone-static-country-us-session-".$randomCode.":0w29dqxs53i7";//pankajnarang81:6aLrPXoy";
        //$username = 'lum-customer-hl_d56dacba-zone-static';
        //$password = 'uzdebqenv5mf';
        //$port = 22225;

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
        curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );

        $response = curl_exec($ch);
        $err = curl_error($ch);

        curl_close($ch);

        if ($err) {
          echo "cURL Error #:" . $err;
          return "ERROR";
        } else {
           
          return $response;
        }
    }

    function get_html_using_postman($url){
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url."?oauth_signature_method=HMAC-SHA1&oauth_timestamp=1547209129&oauth_nonce=GDXhIR&oauth_version=1.0&oauth_signature=t5cfwaBJ4d4I%20jCiRfbX6GLjsAc%3D",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "Host: www.amazon.com",
            "content-type: application/x-www-form-urlencoded",
            "postman-token: 6dd6d796-3bc3-1218-8758-2e8e58daf636"
          ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $header = curl_getinfo($curl);
       // Log::info("postman header");
       // Log::info($header);
        
        curl_close($curl);
        
        if ($err) {
           // Log::info("Error in Post man");
        //  Log::info("cURL Error #:" . $err);
        } else {
          return $response;
        }
    }
        
    function parallelFetch($j,$links,$asin,$user_id,$pages,$totalpage,$currentpage){
        global $conn;
        addlog("Parallel fetching for user:".$user_id." and for ASIN:".$asin,"PARALLEL FETCH INFO");
        $curl_arr = array();
        
        $master = curl_multi_init();
        $counter = 0;
        $urls = array();
        addlog(sizeof($links),"SIZE OF LINKS");
        
        foreach($links as $key => $lnk){
         $temp= get_html_scraper_api_content($lnk);
        // addlog("data via multiple attempts are ".$temp,"ERROR");
        if($temp !== "ERROR") {
          addlog("data found for asin ".$asin,"Info");
           $j = scrapAndSave($j,$lnk,$temp,$asin,$user_id,$pages,$totalpage,$currentpage);
           $currentpage++;
            
        }
        
        
        /*
            echo $lnk."<br/>";
            addlog($lnk,"PARALLEL URL");
            /////   creatingparellel requests for the links
            if($lnk != ""){
                ///   if the link is not blank
                $urls[$counter] = $lnk;
                $curl_arr[$counter] = curl_init();
                curl_setopt($curl_arr[$counter], CURLOPT_URL, "http://api.scraperapi.com/?key=bccfd6a1043eeef4b878ab667efac22b&url=".urlencode($lnk));
                curl_setopt($curl_arr[$counter], CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($curl_arr[$counter], CURLOPT_HEADER, FALSE);
                curl_setopt($curl_arr[$counter], CURLOPT_HTTPHEADER, array(
                    "Accept: application/json"
                ));
                                            
                curl_multi_add_handle($master, $curl_arr[$counter]);
                $counter++;
                echo sizeof($links);
                if($counter == $pages || $key == sizeof($links)-1 || $counter == 10){
                    ///  enter in this case only when the the conter set to 10 or all links updated
                    ///  executes for 10 or less requests
                    do {
                        curl_multi_exec($master,$running);
                    } while($running > 0);
                    
                    for($i = 0; $i < $counter; $i++)
                    {
                        ////  pasing the that response for each parallel requests
                        $j = scrapAndSave($j,$urls[$counter],curl_multi_getcontent($curl_arr[$i]),$asin,$user_id,$pages,$totalpage,$currentpage);
                        $currentpage++;
                    }
                    $counter = 0;
                }
            }
        */}
        $reviewCountObj = $conn->query("SELECT count(product_asin) FROM `reviews` WHERE `product_asin` = '".$asin."' and `user_id` = $user_id");
        addlog("Changing Staus for the reviews in products table","INFO");
        ////   when all reviews updated then we need to update the reviews count in products table
        if($reviewCountObj->num_rows > 0 ){
            ////  finding the total no of reviews available in database for perticular ASIN
            $count = $reviewCountObj->fetch_assoc();
            addlog(json_encode($count));
            if($variantObj = $conn->query("SELECT * FROM `product_variants` where `asin` = '$asin' AND `user_id`=$user_id")){
                //// finding the product id usign asin from the product variants table
                if($variantObj->num_rows > 0){
                    $variant = $variantObj->fetch_assoc();
                    ////  Updating the reviews count in products table now
                    if(!$conn->query("UPDATE `products` SET `reviews` = '".$count['count(product_asin)']."' where `user_id` = $user_id and `product_id`=".$variant['product_id'])){
                        addlog("UPDATE `products` SET `reviews` = '".$count['count(product_asin)']."' where `user_id` = $user_id and `product_id`=".$variant['product_id'],"QUERY");
                        addlog("error in updating products review count status","DATABASE QUERY ERROR");
                    }
                    else {
                        addlog("reviews count updated in products table for asin ".$asin,"INFO");
                    } 
                    
                }else{
                    addlog("No product_variants for reviews count for asin: ".$asin,"ERROR");
                    addlog("SELECT * FROM `product_variants` where `asin` = '$asin' AND `user_id`=$user_id","FOR QUERY");
                }
            }
        }else{
            addlog("No Review Found for ASIN: ".$asin,"INFO");
            ////  if No reviews available in database for perticular ASIN
            if($variantObj = $conn->query("SELECT * FROM `product_variants` where `asin` = '$asin' AND `user_id`=$user_id")){
                if($variantObj->num_rows > 0){
                    //// finding the product id usign asin from the product variants table
                    $variant = $variantObj->fetch_assoc();
                    if(!$conn->query("UPDATE `products` SET `reviews` = '-2' where `user_id` = $user_id and `product_id`=".$variant['product_id'])){
                        ////  Updating the reviews count in products table now
                      //  addlog("UPDATE `products` SET `reviews` = '".$count['count(product_asin)']."' where `user_id` = $user_id and `product_id`=".$variant['product_id'],"QUERY");
                        addlog("no reviews in db for ".$asin,"DATABASE QUERY ERROR");
                    }
                }
            }else{
                addlog("Error in frtching product review for ASIN :".$asin,"ERROR");
                addlog("SELECT * FROM `product_variants` where `asin` = '$asin' AND `user_id`=$user_id","FOR QUERY");
                
            }
        }
    }

    function fetchDataFromURL($url) {
        ////  fetching th epage from the url
        sleep(2);
        global $conn;
        $randomCode = GenCode(6);
       $url =  str_replace("\r", '', $url);
        $proxy_port = "22225";//60099";lum-customer-hl_c27ea444-zone-static
        $proxy_ip = "zproxy.lum-superproxy.io";//172.84.122.33";lum-customer-hl_c27ea444-zone-static
        $loginpassw = "lum-customer-hl_c27ea444-zone-static-country-us-session-".$randomCode.":0w29dqxs53i7";
        //$loginpassw = "lum-customer-hl_d56dacba-zone-static:uzdebqenv5mf";//pankajnarang81:6aLrPXoy";

        //$username = 'lum-customer-hl_d56dacba-zone-static';
        //$password = 'uzdebqenv5mf';
        //$port = 22225;

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

    function scrapAndSave($j,$lnk,$cont,$asin,$user_id,$pages,$totalpage,$currentpage){
        
        ////  Regex Expression to crap the review details from the reviews page
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
        
        
        global $conn;
        addlog("scrapping Content for page: ".$currentpage,"SCRAPPING");
       
        if($cont == ""){
            ////  Checking for blank content if blank then fetch again
            $cont = get_html_scraper_api_content($lnk);
        }
        if($cont != ""){
            ////  If page Content is not blank then scrap reviews list container
            $rs = preg_match_all('/<div\s*id="cm_cr-review_list"\s*class="a-section a-spacing-none\s*review-views\s*celwidget">(.*)<\/ul><\/div><\/span><\/div>/msU', $cont, $ma);
            $time_start1 = microtime(true);
                if($rs){
                    ////  if Review list container Scrapped Successfully
                    $key2 =$ma[1][0];
                    //echo $key2;echo "<br/><br/>";
                }else{
                    //// if the Reviews List Container Cannot Scrapped Then then Trying using x-path
                    $doc = new \DOMDocument();
                    libxml_use_internal_errors(true) AND libxml_clear_errors();
                    $doc->preserveWhiteSpace = true;
                    $doc->loadHTML($cont);
                    $reviewsContainer = $doc->saveHTML($doc->getElementById('cm_cr-review_list'));
                    $key2 = str_replace(array("\r", "\n"), '', $reviewsContainer);
                    if($key2 == ""){
                        ////   if the Review Container cannot Be scrapped then fetch page once again
                        $cont = get_html_scraper_api_content($lnk);
                        $rs = preg_match_all('/<div\s*id="cm_cr-review_list"\s*class="a-section a-spacing-none\s*review-views\s*celwidget">(.*)<\/ul><\/div><\/span><\/div>/msU', $cont, $ma);
                        if($rs){
                            ////  if Review list container Scrapped Successfully
                            $key2 =$ma[1][0];
                            //echo $key2;echo "<br/><br/>";
                        }else{
                            //// if the Reviews List Container Cannot Scrapped Then then Trying using x-path
                            $doc = new \DOMDocument();
                            libxml_use_internal_errors(true) AND libxml_clear_errors();
                            $doc->preserveWhiteSpace = true;
                            $doc->loadHTML($cont);
                            $reviewsContainer = $doc->saveHTML($doc->getElementById('cm_cr-review_list'));
                            $key2 = str_replace(array("\r", "\n"), '', $reviewsContainer);
                            if($key2 == ""){
                                //// if Review list Container Not Found again
                                addlog("Error in finding review Container 129","ERROR");
                                addlog($key2,"CONTECT TO BEScraped");   
                            }
                        }
                    }
                }
                $rs1 = preg_match_all('/<div\s*id="customer_review-.*"\s*class="a-section celwidget">\s*(.*)\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*/smU', $key2, $m);
                if($rs1){
                    //// finding the reviews from the Reviews list Container
                    echo "<h2>fetching data</h2>";echo "<br/>";
                    
                    foreach ($m[1] as $key1 => $value1) {
                        ////  Now Scrapping details for each reviews And Saving to database
                        $tmp = array();
                        $res = preg_match_all($author, $value1, $result);
                        if($res){
                            $tmp['authorName'] = $result[1][0];
                        }
                        $res = preg_match_all($review_date, $value1, $result);
                        if($res){
                            $re = preg_match_all("/([a-zA-Z]*)\s([0-9]{2}),\s([0-9]{4})/",$result[1][0],$resdate);
                            if($re){
                                $tmp['reviewDate'] = $resdate[2][0]."-".substr($resdate[1][0],0,3)."-".$resdate[3][0];
                                // echo $tmp['reviewDate'];
                            }else{
                                $re = preg_match_all("/([a-zA-Z]*)\s([0-9]),\s([0-9]{4})/sU",$result[1][0],$resdate);
                                if($re){
                                    $tmp['reviewDate'] = $resdate[2][0]."-".substr($resdate[1][0],0,3)."-".$resdate[3][0];   
                                }
                                //echo $tmp['reviewDate'];
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
                                }else{
                                    $tmp['reviewTitle'] = $result[1][0];
                                }
                            }else{
                                $tmp['reviewTitle'] = $result[1][0];
                            }
                        }
                        /*$res = preg_match_all($review_title, $value1, $result);
                        if($res){
                            $tmp['reviewTitle'] = $result[1][0];
                        }*/
                        $res = preg_match_all($rating, $value1, $result);
                        if($res){
                            $re = preg_match_all("/([1-9]\.[0-9])/U",$result[1][0],$rate);
                            if($re){
                                $tmp['rating'] = $rate[1][0];  
                                //echo $tmp['rating'];
                            }else{
                                $tmp['rating'] = $result[1][0];
                                //echo $tmp['rating'];
                            }
                        }
                        $res = preg_match_all($varified_flag, $value1, $result);
                        if($res){
                            $tmp['varifiedFlag'] = 'Verified';
                        }else{
                            $tmp['varifiedFlag'] = 'Un-Verified';
                        }
                        $res = preg_match_all('/<img\salt="review image"\ssrc="(.*)"\sdata-hook="review-image-tile"\sclass="review-image-tile"\sheight="88"\swidth="100%">/U',$value1,$review_images);
                        if($res){
                            $tmp['imgArr'] = implode("|",$review_images[1]);
                        }else{
                            $tmp['imgArr'] = "";
                        }
                        $res = preg_match_all($helpful, $value1, $result);
                        if($res){
                            $tmp['FoundHelpful'] = $result[1][0];
                        }else{
                            $tmp['FoundHelpful'] = '';
                        }
                        ////  Checking for the Existance of the Review
                        $permission = mysqli_query($conn,"SELECT * FROM `reviews` WHERE `product_asin`='$asin' AND `rating`='".$tmp['rating']."' AND `reviewTitle`='".str_replace("'","\'",$tmp['reviewTitle'])."' AND `authorName`='".str_replace("'","\'",$tmp['authorName'])."' AND `user_id`='".$user_id."'");
                        if($arr = mysqli_fetch_array($permission)){
                            ////  if reviews already Exists in database
                            echo "<h4>Review Exists</h4>";
                        }else{
                            //// If Review does Not Exists then storing it to the database
                            $query = "INSERT INTO `reviews`(`product_asin`, `authorName`, `reviewDate`, `reviewDetails`, `reviewTitle`, `rating`, `imgArr` ,`verifiedFlag`, `FoundHelpful`, `user_id`,`created_at`,`updated_at`) VALUES ('".$asin."','".str_replace("'","\'",$tmp['authorName'])."','".str_replace("'","\'",$tmp['reviewDate'])."','".str_replace("'","\'",$tmp['reviewDetails'])."','".str_replace("'","\'",$tmp['reviewTitle'])."','".str_replace("'","\'",$tmp['rating'])."','".str_replace("'","\'",$tmp['imgArr'])."','".str_replace("'","\'",$tmp['varifiedFlag'])."','".str_replace("'","\'",$tmp['FoundHelpful'])."',".$user_id.",NOW(),NOW())";
                            $r = mysqli_query($conn,$query);
                            if($r == 0){
                                //// if Error in Storing Review
                                addlog("Error in database update atline 174","ERROR");
                                addlog($query,"INFO");
                                echo "Error in database update atline 174 <br/>";
                                //addlog($conn->error(),"ERROR");
                                //die("data saving error");
                            }else{
                                addlog("Review Updated","INFO");
                                echo "review updated";
                            }
                        }
                    }
                    
                    echo "UPDATE `fetchReviews` SET `currentpage`='".$currentpage."',`updated_at`=NOW() WHERE user_id = ".$user_id." AND product_asin = '".$asin."'";
                    if(!$conn->query("UPDATE `fetchReviews` SET `currentpage`='".$currentpage."',`updated_at`=NOW() WHERE user_id = ".$user_id." AND product_asin = '".$asin."'")){
                        addlog("error in current page counter updation","UPDATION ERROR");
                    }
                    echo $pages." : ".$currentpage;
                    if($j == 0 && $pages == $currentpage){
                        $res = mysqli_query($conn,"UPDATE `fetchReviews` SET `status`='1',`totalpage`='".$totalpage."',`updated_at`=NOW() WHERE user_id = ".$user_id." AND product_asin = '".$asin."'");
                        if($res != 0){
                            addlog($res." result code for asin ".$asin,"INFO");
                            addlog('fetchReview status Changed',"INFO");
                            echo "fetchReview status Changed<br/>";
                        }else{
                            addlog('Error In Saving Fetch Review Status',"INFO");
                            echo '<br/><br/><h2>Error In Saving Fetch Review Status<h2>';
                            //die("FetchReview Updation Error");
                        }
                        $j++;
                    }
                }else{
                    addlog($key2);
                    addlog("Data Scrapping Error at line 179","ERROR");
                    $conn->query("Insert into `failed_review_pages`(url,asin,user_id,status) VALUES('".$lnk."','".$asin."','".$user_id."',0)");
                }
                $time_end1 = microtime(true);
                $execution_time = ($time_end1 - $time_start1);
                addlog('Total Execution Time: '.$execution_time.' Sec ',"INFO");
                return $j;
        }
    }
    
    function addlog($message, $type="INFO"){
		global $logfile;
		$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
		fwrite($logfile, $txt);
	}

	addlog("Execution Finished", "INFO");
	fclose($logfile);
?>