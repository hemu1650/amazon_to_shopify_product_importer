<?php

use Illuminate\Support\Facades\Log;
//function parseAmazon($data,$site_url) {
function getjsonrdata($data,$url,$user_id) {	
	if($data){
		$doc = new \DOMDocument("1.0", "utf-8");
        $doc->recover = true;
        $errors = libxml_get_errors();
        $saved = libxml_use_internal_errors(true);
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
        
                 if(strpos($name, 'Brand') !== false || strpos($name, 'Dimension') !== false || strpos($name, 'Color') !== false || strpos($name, 'Size') !== false || strpos($name, 'Number') !== false|| strpos($name, 'Weight') !== false ) {
                    $All[$name] = $value;
                }
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
        
                if(strpos($name, 'Brand') !== false || strpos($name, 'Dimension') !== false || strpos($name, 'Color') !== false || strpos($name, 'Size') !== false || strpos($name, 'Number') !== false|| strpos($name, 'Weight') !== false ) {
               
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
          $salepricediv = trim($pricediv[1]);
        }
        ////Log::info($salepricediv);
        if($salepricediv == ""){
         $salepricediv =$xp->evaluate('string(//div[@id="cerberus-data-metrics"]//@data-asin-price)',$doc);
          if(strpos($salepricediv, '-') !== false){
              $pricediv = explode("-", $salepricediv);
              //Log::info($pricediv);
              //Log::info($salepricediv);
              $salepricediv = trim($pricediv[1]);
          }
        }
        $dataArr['price'] = $salepricediv;
		
        if($dataArr['price'] == ''){
            $salepricediv =$xp->evaluate('string(//*[@id="buyNewSection"]/a/h5/div/div[2]/div/span[2])',$doc);
            if(strpos($salepricediv, '-') !== false){
              $pricediv = explode("-", $salepricediv);
               //Log::info($pricediv);
              //Log::info($salepricediv);
              $salepricediv = trim($pricediv[1]);
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
	preg_match('/<span\s*class="a-size-medium\s*a-color-price\s*header-price">(.*?)<\/span>/ms',$data,$price_str);
	if(isset($price_str[1])){
	   	$arrlist_price = trim($price_str[1]);
		$list_price = $arrlist_price;$dataArr['list_price'] = $list_price;$price = $arrlist_price;$dataArr['price'] = $price;
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
	preg_match('/<span\s*id="kindle-price"\s*class="a-size-medium\s*a-color-price">(.*?)<\/span>/ms',$data,$price_str);
	if(isset($price_str[1])){
		$arrlist_price = trim($price_str[1]);
		$price = $arrlist_price;$dataArr['price'] = $price;
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
		
		

		preg_match('/<input\s*type="hidden"\s*name="displayedPriceCurrencyCode"\s*value="(.*?)"[^>]*>/ms',$data,$matches);
		if($matches){
			$currency = trim($matches[1]);
		}else{
			preg_match('/"currencyCode":"(.*?)","shouldTruncateCents"/ms',$data,$matches);
			if($matches){
				$currency = trim($matches[1]);
			}
		}
		
		$dataArr['currency'] = $currency;
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

	function verifyCurrency($producturl){
		$domain = parse_url($producturl, PHP_URL_HOST);
		if($domain == "amazon.com" || $domain == "www.amazon.com"){
			return "USD";
		}
		if($domain == "amazon.ca" || $domain == "www.amazon.ca"){
			return "CAD";
		}
		if($domain == "amazon.in" || $domain == "www.amazon.in"){
			return "INR";
		}
		if($domain == "amazon.co.uk" || $domain == "www.amazon.co.uk"){
			return "GBP";
		}
		if($domain == "amazon.com.br" || $domain == "www.amazon.com.br"){
			return "BRL";
		}
		if($domain == "amazon.com.mx" || $domain == "www.amazon.com.mx"){
			return "MXN";
		}
		if($domain == "amazon.de" || $domain == "www.amazon.de"){
			return "EUR";
		}
		if($domain == "amazon.es" || $domain == "www.amazon.es"){
			return "EUR	";
		}
		if($domain == "amazon.fr" || $domain == "www.amazon.fr"){
			return "EUR";
		}
		if($domain == "amazon.it" || $domain == "www.amazon.it"){
			return "EUR";
		}
		if($domain == "amazon.co.jp" || $domain == "www.amazon.co.jp"){
			return "JPY";
		}
		if($domain == "amazon.cn" || $domain == "www.amazon.cn"){
			return "CNY";
		}
       if($domain == "amazon.com.au" || $domain == "www.amazon.com.au"){
			return "AUD";
		}
		if($domain == "amazon.ae" || $domain == "www.amazon.ae"){
			return "AED";
		}
		return false;
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
		if($domain == "amazon.se" || $domain == "www.amazon.se"){
          return true; 
        }
        if($domain == "amazon.ae" || $domain == "www.amazon.ae"){
          return true; 
        }
    	return false;
	}
	
	function getAmount($money) {
	    $temp = substr($money, 1);
	    if (strpos($temp, '$') !== false) {
	        $price_arr = explode("$", $temp);
	        $money = trim($price_arr[0]);
	    }
	    $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
		$onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);
	    $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;
	    $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
		$removedThousendSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '',  $stringWithCommaOrDot);
	    return (float) str_replace(',', '.', $removedThousendSeparator);
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
          

      
        $response = curl_exec($ch);
		//print_r($response);
	    $err = curl_error($ch);
	    curl_close($ch);
         if ($err) {
           return "ERROR";
	    } else {
	      return $response;
	    }
    }
	
	function GenCode($size=6){
		$code = '';
		$validchars = 'abcdefghijkmnopqrstuvwxyz23456789';
		mt_srand ((double) microtime() * 1000000);
		for ($i = 0; $i < $size; $i++) {
			$index = mt_rand(0, strlen($validchars));
			if(isset($validchars[$index])){$code .= $validchars[$index];}
		}
		return $code;
	}
	
	function fetchProductDataWithRetry($url){
		$temp = fetchProductData($url);
        if($temp == "ERROR" || strpos($temp,"Error") || $temp == []) {
	        sleep(2);
            $temp = fetchProductData($url);
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

	function getCountry($producturl){
		$domain = parse_url($producturl, PHP_URL_HOST);
		if($domain == "amazon.com" || $domain == "www.amazon.com"){
			return "US";
		} else if($domain == "amazon.ca" || $domain == "www.amazon.ca"){
			return "CA";
		} else if($domain == "amazon.in" || $domain == "www.amazon.in"){
			return "IN";
		} else if($domain == "amazon.co.uk" || $domain == "www.amazon.co.uk"){
			return "UK";
		} else if($domain == "amazon.com.br" || $domain == "www.amazon.com.br"){
			return "BR";
		} else if($domain == "amazon.com.mx" || $domain == "www.amazon.com.mx"){
			return "MX";
		} else if($domain == "amazon.de" || $domain == "www.amazon.de"){
			return "DE";
		} else if($domain == "amazon.es" || $domain == "www.amazon.es"){
			return "ES";
		} else if($domain == "amazon.fr" || $domain == "www.amazon.fr"){
			return "FR";
		} else if($domain == "amazon.co.jp" || $domain == "www.amazon.co.jp"){
			return "JP";
		} else if($domain == "amazon.cn" || $domain == "www.amazon.cn"){
			return "CN";
		} else if($domain == "amazon.com.au" || $domain == "www.amazon.com.au"){
			return "AU";
		} 
		return false;
	}

	function proxycrawlapi($producturl){


        
		$country = getCountry($producturl);
	    $response = [];
		$url = 'https://api.proxycrawl.com/?token=A8zfXIDXwsj2o5A_1upnJg&autoparse=true&url='.urlencode($producturl);
		if($country){
			$url = 'https://api.proxycrawl.com/?token=A8zfXIDXwsj2o5A_1upnJg&autoparse=true&country='.$country.'&url='.urlencode($producturl);
		}
	    //$url = 'https://api.proxycrawl.com/scraper?token=A8zfXIDXwsj2o5A_1upnJg&url='.$producturl;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$data = curl_exec($curl);
		curl_close($curl);
		
		$res1 = json_decode($data, true);
		
		if($res1['original_status'] == 200 && isset($res1['body'])){
		    $res = $res1['body'];
            
			if(isset($res['name'])){$response['Title'] = $res['name'];}	
			if(isset($res['description'])){$response['description'] = $res['description'];}	
			if(isset($res['brand'])){$response['brand'] = $res['brand'];}	
			if(isset($res['breadCrumbs'][0])){$response['category'] = $res['breadCrumbs'][0]['name'];}	
			$response['url'] = $producturl;$response['currency'] = '';
			if(isset($res['price'])){if (strpos($res['price'], '-') !== false) {$price_arr = explode("-",$res['price']);$response['price'] = trim($price_arr[0]);}else{$response['price'] = $res['price'];}}
			if(isset($res['inStock']) && $res['inStock'] == true){$response['in_stock___out_of_stock'] = 'In stock.';}	
			if(isset($res['images'])){$response['high_resolution_image_urls'] = $res['mainImage'].'|'.implode("|",$res['images']);}	
			if(isset($res['features'])){$response['bullet_points'] = $res['features'];}
			
			if(isset($res['productInformation'][0])){
				for($i=0;$i<count($res['productInformation']);$i++){
					$k = preg_replace("/[^a-zA-Z0-9\s]/", "", $res['productInformation'][$i]['name']);
					$k = preg_replace("~[^a-z0-9:]~i", "", $k); 
					if(trim($k) == 'ASIN'){
					    $asin = $res['productInformation'][$i]['value'];
					    $asin = preg_replace("~[^a-z0-9:]~i", "", $asin); 
					    //$asin = preg_replace("/[^a-zA-Z0-9\s]/", "", $asin);
					    $response['asin'] = $asin;
					}
				}
				$response['productInformation'] = $res['productInformation'];
			}

            if (isset($res['asinVariationValues'])) {

                // print_r($res['asinVariationValues']);
                
                  $variationValues = $res['asinVariationValues'];
                  $uniqueVariationNames = [];
                  foreach ($variationValues as $x) {
                      $variationName = $x['variationName'];
                      $uniqueVariationNames[$variationName] = true;
                  }
                  $uniqueVariationNames = array_keys($uniqueVariationNames);
                  $response['variantion1'] = "multiVariant";
                  $response['option_name'] = $uniqueVariationNames;
                  $response['asinVariationValues'] = $variationValues;
              }
            
			$output['message'] = json_encode($response);return $output;
		}else{return null;}
	}
	
	function get_html_scraper_api_content($url,$user_id) {
	    $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com/?key=7a8ceb5a4f523bc3c82a69c9a759ddca&url=".urlencode($url));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  "Accept: application/json"
		));
		$response = curl_exec($ch);
		curl_close($ch);
		return getjsonrdata($response,$url,$user_id);
	}
	
	function get_html_luminato2_crawl_content($producturl,$user_id){
	     $user_agentObj = UserAgents::orderByRaw("RAND()")->first();
			if(sizeof($user_agentObj)>0){
			    $user_agent = $user_agentObj->ua_string;
			}else{
			    $user_agent = "Mozilla/6.0 (Macintosh; I; Intel Mac OS X 11_7_9; de-LI; rv:1.9b4) Gecko/2012010317 Firefox/10.0a4";
			}
        $proxy_port = "22225";//60099";
        $proxy_ip = "zproxy.lum-superproxy.io";//172.84.122.33";
        $loginpassw = "lum-customer-hl_d56dacba-zone-static:uzdebqenv5mf";//pankajnarang81:6aLrPXoy";
       // $loginpassw = "lum-customer-hl_c27ea444-zone-static:0w29dqxs53i7";//pankajnarang81:6aLrPXoy";
        
        $ch = curl_init();
    
            curl_setopt( $ch, CURLOPT_USERAGENT,  $user_agent );
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
            curl_setopt( $ch, CURLOPT_URL, $producturl );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt( $ch, CURLOPT_ENCODING, "UTF8" );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
            curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
            curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
           // Log::info("http code is ".json_encode(curl_getinfo($ch)));
        /*if($httpcode == 302){
          if (preg_match('~Location: (.*)~i', $content, $match)) {
               $location = trim($match[1]);
               $url = $location;
                $permission = ProductVariant::where("user_id",$currUser->id)->where("asin",strtok($matches[1][0],'/'))->get();
			    if(sizeof($permission)>0){
				    return response()->json(['error' => ["msg"=>["Product Already Exists"]]], 406);
			    }
               Log::info("since it is a 302 redirect check this ".$url);
                                                                }   
        }*/
    
        $response = curl_exec($ch);
        $err = curl_error($ch);
    
        curl_close($ch);
        return getjsonrdata($response,$producturl,$user_id);

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
        
        curl_close($curl);
        
        if ($err) {
         } else {
          return $response;
        }
    }
	
	function get_html_proxy_content($url,$user_id) {
		$start_proxy = 0;$first = 0;
		while($first!=2){
			$lastUsedProxy = Proxy::where('flag',1)->first();
			
			$currentused = 0;
			if(isset($lastUsedProxy)){
				$currentProxy = Proxy::where('id','>',$lastUsedProxy)->where('flag','>',-1)->orderByRaw("RAND()")->first();
			 if(!isset($currentProxy)){
			  	$currentProxy = Proxy::where('flag',0)->orderByRaw("RAND()")->first();
			  }
			}else{
			    $currentProxy = Proxy::where('flag',0)->orderByRaw("RAND()")->first();
			}

			if(!isset($currentProxy)){
				return null;
			}

			if($first == 0){$start_proxy = $currentProxy;}

			$first = 1;
			 
			$PROXY_USER = $currentProxy->username;   //"pankajnarang81";
			$PROXY_PASS = $currentProxy->password;     //"6KkuuDVH";
			$PROXY_IP = $currentProxy->url;
			$PROXY_PORT =$currentProxy->port;
			$proxyd = $PROXY_IP.':'.$PROXY_PORT;
			$auth = "$PROXY_USER:$PROXY_PASS";
			
			
			$user_agentObj = UserAgents::orderByRaw("RAND()")->first();
			if(sizeof($user_agentObj)>0){
			    $user_agent = $user_agentObj->ua_string;
			}else{
			    $user_agent = "Mozilla/6.0 (Macintosh; I; Intel Mac OS X 11_7_9; de-LI; rv:1.9b4) Gecko/2012010317 Firefox/10.0a4";
			}
			
			$d = proxyCheck($PROXY_PORT,$PROXY_IP,$auth,strtok($url, "?"),$user_agent,$user_id);
			
			if($d == null){
				$currentProxy->flag=-1;
				$currentProxy->update([
					    "flag" => -1
					]);
				return null;
			}else{
				$currentProxy->flag=1;
				if(isset($lastUsedProxy)){
					$lastUsedProxy->update([
					    "flag" => 0
					]);
				}
			    $currentProxy->update([
					    "flag" => 1
					]);
				return $d;
			}
		}
	}
	
	function proxyCheck($proxy_port,$proxy_ip,$loginpassw,$url,$user_agent,$user_id){
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
        curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	    $content = curl_exec( $ch );
	    $response = curl_getinfo( $ch );
	    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        /*if($httpcode == 302){
          if (preg_match('~Location: (.*)~i', $content, $match)) {
               $location = trim($match[1]);
               $url = $location;
                $permission = ProductVariant::where("user_id",$currUser->id)->where("asin",strtok($matches[1][0],'/'))->get();
			    if(sizeof($permission)>0){
				    return response()->json(['error' => ["msg"=>["Product Already Exists"]]], 406);
			    }
               Log::info("since it is a 302 redirect check this ".$url);
                                                                }   
        }*/
	    curl_close ( $ch );
	    $d = getjsonrdata($content,$url,$user_id);
	    if($d == null){
	    	 //print_r($content);
	    	return $d;
	    }
	   	return $d;
	    //print_r($content);
	}
	
	function get_html_luminato_crawl_content($producturl,$user_id){
		$user_agentObj = UserAgents::orderByRaw("RAND()")->first();
		if(sizeof($user_agentObj)>0){
			$user_agent = $user_agentObj->ua_string;
		} else {
			$user_agent = "Mozilla/6.0 (Macintosh; I; Intel Mac OS X 11_7_9; de-LI; rv:1.9b4) Gecko/2012010317 Firefox/10.0a4";
		}
        $proxy_port = "22225";//60099";
        $proxy_ip = "zproxy.lum-superproxy.io";//172.84.122.33";
		$randomCode = GenCode(6);
		$loginpassw = "lum-customer-hl_c27ea444-zone-static-session-".$randomCode.":0w29dqxs53i7";//pankajnarang81:6aLrPXoy";
       
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_USERAGENT,  $user_agent );
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
            curl_setopt( $ch, CURLOPT_URL, $producturl );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt( $ch, CURLOPT_ENCODING, "UTF8" );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
            curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
            curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
           // Log::info("http code is ".json_encode(curl_getinfo($ch)));
        /*if($httpcode == 302){
          if (preg_match('~Location: (.*)~i', $content, $match)) {
               $location = trim($match[1]);
               $url = $location;
                $permission = ProductVariant::where("user_id",$currUser->id)->where("asin",strtok($matches[1][0],'/'))->get();
			    if(sizeof($permission)>0){
				    return response()->json(['error' => ["msg"=>["Product Already Exists"]]], 406);
			    }
               Log::info("since it is a 302 redirect check this ".$url);
                                                                }   
        }*/
    
        $response = curl_exec($ch);
        $err = curl_error($ch);
    
        curl_close($ch);
        return getjsonrdata($response,$producturl,$user_id);

	}
	
	function get_html_proxy_crawl_content($producturl,$user_id){
	    $user_agentObj = UserAgents::orderByRaw("RAND()")->first();
			if(sizeof($user_agentObj)>0){
			    $user_agent = $user_agentObj->ua_string;
			}else{
			    $user_agent = "Mozilla/6.0 (Macintosh; I; Intel Mac OS X 11_7_9; de-LI; rv:1.9b4) Gecko/2012010317 Firefox/10.0a4";
			}
			
	    
	    $proxy_ip = gethostbyname("proxy.proxycrawl.com");
	    $proxy_port = 9000;
	    
	     $ch = curl_init();
	    curl_setopt( $ch, CURLOPT_USERAGENT, $user_agent );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
        curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTPS');
        curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
        //curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);
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
        curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
	    $content = curl_exec( $ch );
	    $response = curl_getinfo( $ch );
	    curl_close ( $ch );
	    $d = getjsonrdata($content,$url,$user_id);
	    if($d == null){
	    	 //print_r($content);
	    	return $d;
	    }
	   	return $d;
	}
	
	function getProxyData($proxy_port,$proxy_ip,$loginpassw,$url){
	    /*$aContext = array(
	    			'http' => array(
	        		'proxy' => "tcp://$proxy",
	        		'timeout' => 30,
	        		'request_fulluri' => true,
	        		'header' => "Proxy-Authorization: Basic ".$auth
			    ),
			);
			
			
	    $cxContext = stream_context_create($aContext);*/
	       // try{
				//$sFile = file_get_contents($url,true,$cxContext);
				$cookie = tempnam ("/tmp", "CURLCOOKIE");
                $ch = curl_init();
                curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
                curl_setopt( $ch, CURLOPT_URL, $url );
                curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
                curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
                curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');
                curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);
                curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
                curl_setopt( $ch, CURLOPT_ENCODING, "" );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
                curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
                curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
                curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
                curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
                $content = curl_exec( $ch );
                $response = curl_getinfo( $ch );
                curl_close ( $ch );
                 //print_r($content);
				return $content;
		//	}catch(\Exception $e){
			//	Log::info("Proxy Not Responding :");
			//	Log::info($e);
			//	return null;
				//@mail("pankajnarang81@gmail.com", "ProductController: Proxy Crawel Not Responding", json_encode($currentProxy));
		//	}
	}