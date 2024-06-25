<?php
//function parseAmazon($data,$site_url) {
function parseAmazon($data,$url,$user_id) {	
	if($data){
		$doc = new \DOMDocument("1.0", "utf-8");
        $doc->recover = true;
        $errors = libxml_get_errors();
        $saved = libxml_use_internal_errors(true);
        //Log::info($data);
        if(strlen($data)<200){
            return null;
        }
        $doc->loadHTML($data);
        //$handle = fopen('Product.html', 'wr');
        //fwrite($handle, $data);
        $xp = new \DOMXPath($doc);
        $dataArr = array();
        //$dataArr['url'] = $producturl;
		$currency = '';
        $titleBlock = $doc->getElementById('title');
        //$dataArr['Title'] = $xp->evaluate('string(.//*[@class="a-size-extra-large"])', $titleBlock);
        $dataArr['Title'] = trim( $xp->evaluate('string(.//*[@class="a-size-large"])', $titleBlock) );
        //case where capatcha is there  
       //   if(strlen($dataArr['Title'])==0){//Log::info("No Data Available in getjsonrdata");return null;}
        //if(strlen($dataArr['Title'])==0){
            //$dataArr['Title'] = trim($xp->evaluate('string(//*[@id="productTitle"])', $titleBlock));
            //if(strlen($dataArr['Title'])==0){
                 ////Log::info("title not found for ".$url);
                //return null;
            //}
        //}
        if(strlen($dataArr['Title'])==0){
            $dataArr['Title'] = trim($xp->evaluate('string(//*[@id="productTitle"])', $titleBlock));
            if(strlen($dataArr['Title'])==0){
                $dataArr['Title'] = trim($xp->evaluate('string(//*[@id="title"])', $titleBlock));
                if(strlen($dataArr['Title'])==0){
                    //Log::info("No Data Available");
                    return null;
                }
            }
        }

         
      /*  //Log::info($data);
       $res = preg_match_all('/<div id="productDescription" class="a-section a-spacing-small">\s|.*<div class="disclaim">.*<\/div>(.*)<\/div>/sU',  $data, $matches);
         if($res){
                $dataArr['description'] = '<p><b>Product Details:</b></p>'.trim($matches[1][0]);//*[@id="productDescription"]
                //Log::info("description is done in fdsdsdirst regular expression ".$dataArr['description']);
                exit;
            }
       exit; */    
       $All = [];
       $tables = $doc->getElementById('productDetails_techSpec_section_1');
       if(!$tables){
       $tables = $xp->query("//*[@id='productDetails_techSpec_section_1']")->item(0);
       //Log::info("ttables found via xpath".$tables);
       
       }
       
       $tables2 = $doc->getElementById('productDetails_techSpec_section_2');
       $props = '';
     
     if($tables || $tables2){
        //Log::info("desc foundddddddddd");
        $tr = $tables->getElementsByTagName('tr'); 
		//Log::info("props in productDetails_techSpec_section_1");
		//echo $tr;
        foreach ($tr as $element1) {        
            //for ($i = 0; $i < count($element1); $i++) {
                //Not able to fetch the user's link :(
                $name  = $element1->getElementsByTagName('th')->item(0)->textContent;    // To fetch user link
                $value  = $element1->getElementsByTagName('td')->item(0)->textContent;  // To fetch name
                                 // To fetch country
        // Rohit uncomment
                /*if($name.contains('Brand') || $name.contains('Dimension') || $name.contains('Color') || $name.contains('Size') || $name.contains('Number') || $name.contains('Weight') ) {
                     $All[$name] = $value;
                }*/
            //}
        }
        
        
      
     
     if($tables2){
        $tr = $tables2->getElementsByTagName('tr'); 
        //Log::info("props in productDetails_techSpec_section_2");
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
        $props = '<p><b>Specifications</b></p><p><ul>';
     }
        foreach($All as $key => $value) {
        $props = $props.'<li><b>'.$key.'</b>:'. $value.'</li>';
        }
        $props= $props.'</ul></p>';
    
        //$dataArr['description'] = $props.$dataArr['description'];
       ////Log::info("desc after props".$dataArr['description']);

     }
     
     else {
     
     
        //here we will see how to fetch color brand publisher and other details
     /*
      $brandnameheaddiv = $doc->getElementById('bylineInfo_feature_div');
       //Log::info("props in bylineInfo_feature_div");
      if($brandnameheaddiv)
        {
         $props = '<p><b>Specifications</b></p><p><ul>';
         $link = $brandnameheaddiv->getElementsByTagName('div');//->getElementsByClassName('a-link-normal')->item(0)->textContent;         
         foreach ($link as $element1) {
          for ($i = 0; $i < count($element1); $i++) {     
            $brandInfo =  $element1->getElementsByTagName('a')->item(0)->textContent; 
            //Log::info("brand in else".$brandInfo);
            if($brandInfo !== ''){
               $props = $props.'<li><b>Brand Name</b>: '.$brandInfo.'</li>';
                 }    
            ////Log::info("brand in else".$props);
          }
         }
        }
        
       */ 
        
      $sizediv = $doc->getElementById('variation_size_name');
      $tmpsizeprops = "";
        if($sizediv)
        {
         $link = $sizediv->getElementsByTagName('div'); 
         if($link){
             foreach ($link as $element1) {
                //Log::info(json_encode($element1));
                if(!$element1->getElementsByTagName('span')){
                 continue;
                }
                if(!$element1->getElementsByTagName('span')->item(0)){
                 continue;
                }
                $sizeInfo =  $element1->getElementsByTagName('span')->item(0)->textContent; 
                if(isset($sizeInfo)){
                    //Log::info("size in else".trim($sizeInfo));
                     $props = $props.'<li><b>Size</b>: '.trim($sizeInfo).'</li>';    
                    if($sizeInfo){
                        break;
                    }
                }
                ////Log::info("color in else".$props);
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
                //Log::info(json_encode($element1));
                $colorInfo =  $element1->getElementsByTagName('span')->item(0)->textContent; 
                if(isset($colorInfo)){
                    ////Log::info("color in else".$colorInfo);
                     $props = $props.'<li><b>Color</b>: '.trim($colorInfo).'</li>';    
                    if($colorInfo){
                        break;
                    }
                }
                //Log::info("color in else".$tmpprops);
             } 
         }
         
         
         /*if( $tmpprops == '' || $tmpprops == null){
             //Log::info(str_replace("\n","",$doc->saveHTML($link->item(0))));
             $res = preg_match_all('/<div class="a-row a-spacing-micro">\s*<strong>(.*)<\/strong>\s*(.*)<\/div>/U',str_replace("\n","",$doc->saveHTML($link->item(0))),$matches);
             if($res){
                 //Log::info($matches);
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
      //Log::info(" props ".$props);
      
}

          $bulletPoints = '';
          $featuredetailsBlock = $doc->getElementById('featurebullets_feature_div');
         // if()
          $res = preg_match_all('/<div id="feature-bullets".*>(.*)<\/div>/sU',$doc->saveHTML($featuredetailsBlock),$matches);
            if($res){
                //Log::info("bulletPoints in first re".json_encode($matches) );
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
                //Log::info($bulletPoints);
            }
       
      
        
            if(!isset($bulletPoints) || $bulletPoints == '' || $bulletPoints == null){
                $descriptionAndDetailsBlock = $doc->getElementById('descriptionAndDetails');
                $res = preg_match_all('/<div id="detailBullets_feature_div">(.*)<\/div>/sU',$doc->saveHTML($descriptionAndDetailsBlock),$matches);
                if($res){
                     //Log::info("bulletPoints in second re");
                    $detailBullets = $matches[1][0];
                    $detailBullets = str_replace('<li><span class="a-list-item">
                       
                    </span></li>',"",$detailBullets);
                    $detailBullets = str_replace('Amazon',"",$detailBullets);
                    $bulletPoints = str_replace("(<a","<!--",$bulletPoints);
                    $bulletPoints = str_replace("</a>)","-->",$bulletPoints);
                    $bulletPoints = str_replace("(","",$bulletPoints);
                    $bulletPoints = str_replace(")","",$bulletPoints);
                    $bulletPoints .= "<br/><p></p><p><strong>Features</strong></p>".$bulletPoints;
                    //Log::info($detailBullets);
                }
            }
        
       
       if(!isset($bulletPoints) || $bulletPoints == '' || $bulletPoints == null){
          $featurebullets = $doc->getElementById('feature-bullets');
          //Log::info("bulletPoints in third attempet".$bulletPoints);
          
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
             
             
             //Log::info($bulletPoints);
       }
       
       if(!isset($bulletPoints) || $bulletPoints == '' || $bulletPoints == null){
           $bulletPoints = '';
            //Log::info("bulletPoints is empty after all efforts");
       }
    
    //Log::info("feature bullet points are now".$bulletPoints);
        
      
     // the below code is for description 
       $dblock = '';
       $productDescriptionBlock = $doc->getElementById('descriptionAndDetails');
       $dataArr['description'] = '';
      if($productDescriptionBlock){
        // //Log::info("description block ".$doc->saveHtml($productDescriptionBlock));
         $dblock = $doc->saveHtml($productDescriptionBlock);
       }
       else {
          $centerBlock = $doc->getElementById('dpx-product-description_feature_div');
      if($centerBlock){
          $centerDatBlock = $doc->saveHtml($centerBlock);
          //Log::info("center block ".$centerDatBlock );
          $dblock =  $centerDatBlock;
       }
          
      //    exit;
       }
         
       
       if($dblock !== ''){//
         $res = preg_match_all('/<div id="productDescription" class="a-section a-spacing-small">\s|.*<div class="disclaim">.*<\/div>(.*)<\/div>/sU',  $dblock, $matches);
            //<div id="productDescription" class="a-section a-spacing-small">\n*.*<div class="disclaim">.*<\/div>(.*)<\/div>
             //Log::info("trying regular expressions on ");
            if($res && $matches[1][0] !== '' ){
                $dataArr['description'] = '<p><b>Product Details:</b></p>'.trim($matches[1][0]);//*[@id="productDescription"]
                //Log::info("description is done in first regular expression ".$dataArr['description']);
            }
            else{
                $res = preg_match_all('/<div id="productDescription" class="a-section a-spacing-small">\s*(.*)<\/div>/sU', $dblock, $matches);
                if($res && $matches[1][0] !== ''){
                    $dataArr['description'] = trim($matches[1][0]);//*[@id="productDescription"]
                    //Log::info("description is done in second regular expression ".$dataArr['description']);
                }else{
                    $res = preg_match_all('/<div id="productDescription" class="a-section a-spacing-small">\s*(.*)<\/div>\s*<style\s*/sU',  $dblock, $matches);
               if($res && $matches[1][0] !== ''){
                   $dataArr['description'] = '<p><b>Product Details:</b></p>'.trim($matches[1][0]);//*[@id="productDescription"]
                   //Log::info("description is done in third regular expression ".$dataArr['description']);
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
            //Log::info("description is done in third xpath attempt we wish to avoid".$dataArr['description']);
        }
       

         if($dataArr['description'] == '' || $dataArr['description']==null){
            $dataArr['description'] =  trim($doc->saveHTML($productDescriptionBlock));
            $dataArr['description'] = str_replace("\n", "", $data);
            $res = preg_match_all('/<div id="productDescription" class="a-section a-spacing-small">\n*.*<div class="disclaim">.*<\/div>(.*)<\/div>/sU', $data, $matches);
            if($res){
                $dataArr['description'] = trim($matches[1][0]);//*[@id="productDescription"]
                //Log::info("description is done in 4th attempt".$dataArr['description']);
            }else{
                $res = preg_match_all('/<div id="productDescription" class="a-section a-spacing-small">\s*(.*)<\/div>/sU', $data, $matches);
                if($res){
                    $dataArr['description'] = trim($matches[1][0]);//*[@id="productDescription"]
                    //Log::info("description is done in 5th attempt".$dataArr['description']);
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
                    ////Log::info($matches[1]);
                    foreach($matches[1] as $p){
                        if(strpos($p, '<img') !== false){
                            
                        }else{
                            $tmp .= "<p>".$p."</p>"; 
                        }
                    }
                    //Log::info($tmp);
                     //Log::info("description is done in 4th aplus regular expression attempt");
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
                    ////Log::info($matches[1]);
                    foreach($matches[1] as $p){
                        if(strpos($p, '<img') !== false){
                            
                        }else{
                            $tmp .= "<p>".$p."</p>"; 
                        }
                    }
                    //Log::info($tmp);
                    $dataArr['description'] = $tmp;
                }
                //Log::info("description is done in 6th attempt");
            }else{
                if(trim($xp->evaluate('string(//*[@id="descriptionAndDetails"])',  $doc)) != '' ||trim($xp->evaluate('string(//*[@id="aplus"])',  $doc)) != null){
                    //*[@id="descriptionAndDetails"]
                    $dataArr['description'] = '';//$doc->saveHTML($doc->getElementById('descriptionAndDetails'));
                    //Log::info("description is done in 8th attempt".$dataArr['description']);
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
           // echo  str_replace(">","",str_replace("<","",$doc->saveHTML($temp)));
            $res = preg_match_all("/<noscript>\s*(.*)\s*.*\s*<\/noscript>/", $doc->saveHTML($temp), $matches);
            if($res){
                //echo "<br/><br/>";
                //print_r($matches);
                //echo "<br/><br/>";
            }else{
                //echo "<br/><br/> <h1>Error In Pattern matching</h1><br/><br/>";
            }
        }else{
            //echo "<br/><br/> <h1>Iframe Description Not Found</h1><br/><br/>";
        }
       }

       if($dataArr['description'] == '' || $dataArr['description']==null){
            $descriptionAndDetails = $doc->saveHTML($doc->getElementById('bookDescription_feature_div'));
            $res = preg_match_all("/<noscript>\s*(.*)\s*<\/noscript>/sU", $descriptionAndDetails, $matches);
            if($res){
                    $dataArr['description'] .= $matches[1][0];
            }
        }

   
        
         
            //*[@id="productDescription"]/p
           
        
        
        if($dataArr['description']){
           $dataArr['description'] = '<b>Description : </b>'. $dataArr['description'];
        }
       //remove_emoji("description found");

       // above code for description is done.
        //$dataArr['description'] = $props.$bulletPoints.$dataArr['description'];
        $dataArr['description'] = $dataArr['description'].$bulletPoints.$props;
        $dataArr['description'] = str_replace('none','',$dataArr['description']); 
       
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
       
        if(ctype_alnum($asin)){
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
        //Log::info("feature bullet points are".json_encode($bulletData));
        $dataArr['bullet_points'] = $bulletData;*/
        
        $dataArr['bullet_points'] = '';
        ////Log::info("bullet points is done".json_encode($dataArr['bullet_points']) );
        $salepricediv = $xp->evaluate('string(//span[contains(@id,"ourprice") or contains(@id,"saleprice") or contains(@id,"priceblock_ourprice") or contains(@id,"buyNew_noncbb") or contains(@id,"priceblock_dealprice")]/text())',$doc);
        if(strpos($salepricediv, '-') !== false){
          $pricediv = explode("-", $salepricediv);
          ////Log::info($pricediv);
          $salepricediv = trim($pricediv[0]);
        }
        ////Log::info($salepricediv);
        if($salepricediv == ""){
         $salepricediv =$xp->evaluate('string(//div[@id="cerberus-data-metrics"]//@data-asin-price)',$doc);
          if(strpos($salepricediv, '-') !== false){
              $pricediv = explode("-", $salepricediv);
              //Log::info($pricediv);
              //Log::info($salepricediv);
              $salepricediv = trim($pricediv[0]);
          }
        }
        $dataArr['price'] = $salepricediv;
		
        if($dataArr['price'] == ''){
            $salepricediv =$xp->evaluate('string(//*[@id="buyNewSection"]/a/h5/div/div[2]/div/span[2])',$doc);
            if(strpos($salepricediv, '-') !== false){
              $pricediv = explode("-", $salepricediv);
               //Log::info($pricediv);
              //Log::info($salepricediv);
              $salepricediv = trim($pricediv[0]);
            }
        }
        $dataArr['price'] = $salepricediv;
		
        if($dataArr['price'] == ""){
            $salepricediv =$xp->evaluate('string(//*[@id="olp-upd-new"]/span/a/text())',$doc);
            $salepricediv = explode("$",$salepricediv);
            //Log::info($salepricediv);
            //Log::info("sales div");
            if( isset($salepricediv[1]) ) {
              $dataArr['price'] = "$ ".$salepricediv[1];
			  
            }
        }
    // fetch price from comparison table
     if($dataArr['price'] == ""){
        $All = [];
       $tables = $doc->getElementById('HLCXComparisonTable');
      
		if($tables){
			$tr = $tables->getElementsByTagName('tr'); 
			foreach ($tr as $element1) {        
				for($i = 0; $i < count($element1); $i++) {
					$id = $element1->getAttribute('id');
					//Log::info("id is found in table ".$id);
					if($id == 'comparison_price_row') {
						$price = $element1->getElementsByTagName('td')->item(0)->textContent; 
						//Log::info("price is found in table ".$price);
						$dataArr['price'] = "$ ".substr($price, strpos($price, "$") + 1);
						//Log::info("price is found in table ".$dataArr['price']);
						break;
					}
			
				}
			}

							/*$props = '<p><b>Specifications</b></p><p><ul>';
							foreach($All as $key => $value) {
							$props = $props.'<li><b>'.$key.'</b>:'. $value.'</li>';
							}
							$props= $props.'</ul></p>';*/
							
						// $dataArr['description'] = $props.$dataArr['description'];
						// //Log::info("desc after props".$dataArr['description']);

		}
            
	}
        
        $originalpricediv = $xp->evaluate('string(//td[contains(text(),"List Price") or contains(text(),"M.R.P") or contains(text(),"Price")]/following-sibling::td/text())', $doc);
        
        if(trim($originalpricediv) == ''){
            $dataArr['list_price'] = $dataArr['price'];
			
        }else{
            //Log::info("original price div");
            //Log::info($originalpricediv);
            $dataArr['list_price'] = $originalpricediv;
			
        }
        
        //$currUser = Auth::User();
	if($user_id == 3602 || $user_id == 5619 || $user_id == 8855){
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
								$price = trim($price_str[1]);
							}else{
								preg_match('/<span\s*class=\'a-color-price\'>(.*?)<\/span>/ms',$data,$price_str);
								if($price_str){
									$price = trim(str_replace(chr(0xE2).chr(0x82).chr(0xAC), "", $price_str[1]));$price = trim(str_replace(',','',$price));
								}
							}
						}else{
							$price = trim(str_replace(chr(0xE2).chr(0x82).chr(0xAC), "", $price_str[1]));$price = trim(str_replace(',','',$price));
						}
					}else{
						$price = trim(str_replace(chr(0xE2).chr(0x82).chr(0xAC), "", $price_str[1]));$price = trim(str_replace(',','',$price));
					}
				}else{
					$price = trim(str_replace(chr(0xE2).chr(0x82).chr(0xAC), "", $price_str[1]));$price = trim(str_replace(',','',$price));
				}
				
				preg_match('/<span\s*class="priceBlockStrikePriceString\s*a-text-strike">(.*?)<\/span>/ms',$data,$list_str);
				if($list_str){
					$list_price = trim($list_str[1]);
				}

		$dataArr['price'] = $price;
		$dataArr['list_price'] = $list_price;
		if($list_price == 0){
		    $dataArr['list_price'] = $price;
		}
	}
	if($user_id == 8535 && $dataArr['price'] == 0){
	    $price = 0;
		$list_price = 0;
	    preg_match('/<span\s*class="a-size-base\s*a-color-price\s*a-color-price">(.*?)<\/span>/msiU', $data, $price_str);
	    if(isset($price_str[1])){
	        $list_price = trim($price_str[1]);
		    $price = $list_price;
	    }
	    $dataArr['price'] = $price;
		$dataArr['list_price'] = $list_price;
	    
	}

if($dataArr['price'] == ''){
	preg_match('/<span\s*id="newBuyBoxPrice"\s*class="a-size-base\s*a-color-price\s*header-price\s*a-text-normal">(.*?)<\/span>/ms',$data,$price_str);
	if(isset($price_str[1])){
	    $arrlist_price = trim($price_str[1]);
		$price = $arrlist_price;$dataArr['price'] = $price;
	 }
}
if($dataArr['list_price'] == ''){
	preg_match('/<span\s*id="listPrice"\s*class="a-color-secondary\s*a-text-strike">(.*?)<\/span>/ms', $data, $price_str);
	if(isset($price_str[1])){
	    $list_price = trim($price_str[1]);
		$list_price = $list_price;$dataArr['list_price'] = $list_price;
	}
}
if($dataArr['price'] == ''){
	preg_match('/<span\s*id="price"\s*class="a-size-medium\s*a-color-price\s*header-price\s*a-text-normal">(.*?)<\/span>/ms',$data,$price_str);
	if(isset($price_str[1])){
	   	$arrlist_price = trim($price_str[1]);
		$price = $arrlist_price;$dataArr['price'] = $price;
	 }
}
if($dataArr['price'] == ''){
	preg_match('/<tr\s*class="kindle-price">(.*?)<\/tr>/ms',$data,$priceBlock);
	if($priceBlock){
		preg_match('/<span\s*class="a-size-medium\s*a-color-price">(.*?)<\/span>/ms',$priceBlock[1],$price_str);
		if(isset($price_str[1])){
			$arrlist_price = trim($price_str[1]);
			$price = $arrlist_price;$dataArr['price'] = $price;
		 }
	}
}
if($dataArr['price'] == ''){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, 'https://www.amazon.com/gp/offer-listing/'.$asin.'/ref=dp_olp_unknown_mbc');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	$sourceHTML = curl_exec($curl);
	curl_close($curl);
	
	preg_match('/<span\s*class="a-size-large\s*a-color-price\s*olpOfferPrice\s*a-text-bold">(.*?)<\/span>/ms', $sourceHTML, $price_str);
	
	if(isset($price_str[1])){
	    $list_price = trim($price_str[1]);
		$list_price = $list_price;$dataArr['list_price'] = $list_price;$dataArr['price'] = $list_price;
	}
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
		
		if($imageArr == null || sizeof($imageArr) == 0){
			preg_match('/<div\s*id="mainImageContainer"\s*class="a-row\s*center-align\s*litb-on-click">(.*?)<\/div>/ms',$data,$img_str);
			if($img_str){
				$dom = new DOMDocument();
				$dom->loadHTML($img_str[1]);
				$liList = $dom->getElementsByTagName('img');
				$liValues = array();
				foreach ($liList as $li) {
					$li_img = str_replace('"','',$li->getAttribute('data-a-dynamic-image'));
					preg_match('/{(.*?):\[/ms',$li_img,$imgg_str);
					array_push($imageArr,$imgg_str[1]);
				}
			}
		}
		
		if($imageArr == null || sizeof($imageArr) == 0){
			preg_match('/<div\s*id="mainImageContainer"\s*class="a-row\s*center-align">(.*?)<\/div>/ms',$data,$img_str);
			if($img_str){
				$dom = new DOMDocument();
				$dom->loadHTML($img_str[1]);
				$liList = $dom->getElementsByTagName('img');
				$liValues = array();
				foreach ($liList as $li) {
					$li_img = str_replace('"','',$li->getAttribute('data-a-dynamic-image'));
					preg_match('/{(.*?):\[/ms',$li_img,$imgg_str);
					array_push($imageArr,$imgg_str[1]);
				}
			}
		}
		
        if($imageArr == null || sizeof($imageArr) == 0){
			$images = $doc->saveHTML($doc->getElementById('ebooks-img-canvas'));
            if($images){
				$res = preg_match_all('/<img\salt=".*"\ssrc="(.*)"\s*.*>/U', $images, $matches);
                if($res){
					$imageArr[] = end($matches[1]);
                }
            }
        }

        foreach ($imageArr as $image) {
            $imagepipe = $imagepipe."|".$image;
        }
        
        $dataArr['high_resolution_image_urls'] =  $imagepipe;  //$imageArr[0]."|".$imageArr[1]."|".$imageArr[2]."|".$imageArr[3]."|".$imageArr[4]."|".$imageArr[5]."|".$imageArr[6]."|".$imageArr[7]."|".$imageArr[8]."|".$imageArr[9];
		
		preg_match('/[£\$€]+/', $dataArr['price'], $matches);
		if($matches){$dataArr['currency '] = $matches[0];}else{$dataArr['currency '] = '';}
       
      if( !isset( $dataArr['Title'] ) || !isset( $dataArr['description'] ) || !isset( $dataArr['asin'] ) || !isset( $dataArr['bullet_points'] ) || !isset( $dataArr['price'] ) || !isset( $dataArr['list_price'] )  || !isset( $dataArr['category'] ) || !isset( $dataArr['brand'] ) || !isset( $dataArr['high_resolution_image_urls'] )       ){
            return json_encode(array());
        }else{
           return array("status" => "success", "message" => json_encode($dataArr));
        }
				
				
	}else
	{
		return array("status" => "failure", "message" => "There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist.");
	}
}