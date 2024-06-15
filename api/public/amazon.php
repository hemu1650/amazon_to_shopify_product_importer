<?php
//function parseAmazon($data,$site_url) {
function parseAmazon($data) {	
	if($data){
				$title = '';$description = '';$brand = '';$product_type = '';$option1name = '';$option2name = '';$option3name = '';$images = array();$variants = array();$sku = '';$price = 0;
				$list_price = 0;$quantity = 1;$option1val = '';$option2val = '';$option3val = '';$weight = '';$weight_unit = '';$variant_images = array();				
				preg_match('/<span\s*id="productTitle"\s*class="a-size-large">(.*?)<\/span>/ms',$data,$title_str);
				preg_match('/<input\s*type="hidden"\s*id="ASIN"\s*name="ASIN"\s*value="(.*?)">\s*<input\s*type="hidden"/ms',$data,$asin_str);
				if($title_str){
					$title = trim(str_replace("'",'',$title_str[1]));
				}else{
					preg_match('/<span\s*id="productTitle"\s*class="a-size-large\s*product-title-word-break">(.*?)<\/span>/ms',$data,$title_str);
					if($title_str){
						$title = trim(str_replace("'",'',$title_str[1]));
					}
				}
				
				if($asin_str){
					$sku = trim($asin_str[1]);
				}
				
				preg_match('/<span\s*id="priceblock_ourprice"\s*class="a-size-medium\s*a-color-price\s*priceBlockBuyingPriceString">(.*?)<\/span>/ms',$data,$price_str);
				$price_str[0] = trim(str_replace(',','',$price_str[0]));$price_str[1] = trim(str_replace(',','',$price_str[1]));
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
				
				preg_match('/<a\s*id="bylineInfo"\s*class="a-link-normal"\s*href="(.*?)">(.*?)<\/a>/ms',$data,$brand_str);
				if($brand_str){
					$brand = trim($brand_str[2]);
				}
					
				preg_match('/<div\s*id="availability"\s*class="a-section\s*a-spacing-none">\s*<span\s*class="a-size-medium\s*a-color-success">(.*?)<\/span>\s*<\/div>/ms',$data,$in_stock_str);
				if($in_stock_str){
					$quantity = 1;
				}else{
					preg_match('/<div\s*id="availability"\s*class="a-section\s*a-spacing-base">\s*<span\s*class="a-size-medium\s*a-color-price">(.*?)<\/span>/ms',$data,$in_stock_str);
					if($in_stock_str && trim($in_stock_str[1]) == 'Currently unavailable.'){
						$quantity = 0;
					}else{
						preg_match('/<div\s*id="availability"\s*class="a-section\s*a-spacing-base">\s*<span\s*class="a-size-medium\s*a-color-state">(.*?)<\/span>/ms',$data,$in_stock_str);
						if($in_stock_str && trim($in_stock_str[1]) == 'Temporarily out of stock.'){
							$quantity = 0;
						}else{
							$quantity = 1;
						}
					}
				}
				
				preg_match('/var data =(.*?)return\s*data;/ms',$data,$image_str);
				if($image_str){
					preg_match("/'initial':(.*?)},\s*'colorToAsin'/ms",$image_str[1],$str);
					if($str){
						$array = json_decode($str[1], true);
						$length = count($array);
						for($i=0;$i<$length;$i++){
							if($array[$i]['hiRes']){
								$array[$i]['hiRes'] = str_replace('"','',$array[$i]['hiRes']);
								array_push($images,trim($array[$i]['hiRes']));
							}else{
								$array[$i]['large'] = str_replace('"','',$array[$i]['large']);
								array_push($images,trim($array[$i]['large']));
							}
						}
					}
				}
				
				preg_match('/<div\s*id=\'nav-subnav\'\s*class="spacious"\s*data-category="(.*?)">/ms',$data,$category_arr);
				if($category_arr && $category_arr[1] !== 'hi'){
					$product_type = trim($category_arr[1]);
				}else{
					preg_match('/<div\s*id=\'nav-subnav\'\s*data-category="(.*?)">/ms',$data,$category_arr);
					if($category_arr && $category_arr[1] !== 'hi'){
						$product_type = trim($category_arr[1]);
					}
				}
				
				preg_match('/<tr>\s*<th\s*class="a-color-secondary\s*a-size-base\s*prodDetSectionEntry">\s*Shipping\s*Weight\s*<\/th>\s*<td\s*class="a-size-base">(.*?)\(<a\s*href=(.*?)>View\s*shipping\s*rates\s*and\s*policies<\/a>\)\s*<\/td>\s*<\/tr>/ms',$data,$shipping_str);
				if($shipping_str){
					$weight = trim($shipping_str[1]);
				}
						
				/*preg_match('/<tr>\s*<th\s*class="a-color-secondary\s*a-size-base\s*prodDetSectionEntry">\s*Product\s*Dimensions\s*<\/th>\s*<td\s*class="a-size-base">(.*?)<\/td>\s*<\/tr>/ms',$data,$dimension_str);
				if(!$dimension_str)
				{
					$dimension = '';
				}
				else
				{
					$dimension = trim($dimension_str[1]);
				}*/
				 
				preg_match('/<ul\s*class="a-unordered-list\s*a-vertical\s*a-spacing-none">(.*?)<\/ul>/ms',$data,$bullet_strs);
				if($bullet_strs){
					$description = $bullet_strs[1];
					$description = iconv('utf-8','ASCII//IGNORE//TRANSLIT',$description);
				}else{
					preg_match('/<div\s*id="productDescription"\s*class="a-section\s*a-spacing-small">(.*?)<\/div>/ms',$data,$bullet_strs);
					if($bullet_strs){
						$description = $bullet_strs[1];
						$description = iconv('utf-8','ASCII//IGNORE//TRANSLIT',$description);
					}
				}
				
				preg_match_all('/"dimensionsDisplay"\s*:\s*\[(.*?)\],/ms',$data,$optionv_arr);
				if($optionv_arr){
					if(isset($optionv_arr[1][0])){
						$varOption = explode(",",$optionv_arr[1][0]);
						if(isset($varOption[0])){$option1name = str_replace('"','',$varOption[0]);}else{$option1name = '';}
						if(isset($varOption[1])){$option2name = str_replace('"','',$varOption[1]);}else{$option2name = '';}
						if(isset($varOption[2])){$option3name = str_replace('"','',$varOption[2]);}else{$option3name = '';}
					}
				}
				
				preg_match('/"dimensionValuesDisplayData"\s*:\s*{(.*?)}/ms',$data,$variant_arr);
				$exist = 0;
				if($variant_arr){
					$variant_arr[1] = '{'.$variant_arr[1].'}';
					$var = json_decode($variant_arr[1], true);
					if(!empty($var)){
						foreach($var as $key=>$value){
							$option1val = '';$option2val = '';$option3val = '';
							$sku = $key;$quantity = 1;
						  	$curl = curl_init();
                    		curl_setopt($curl, CURLOPT_URL, 'https://www.amazon.com/dp/'.$key);
                    		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    		curl_setopt($curl, CURLOPT_HEADER, false);
                    		$amzData = curl_exec($curl);
                    		curl_close($curl);
							if($amzData){
								preg_match('/"selected_variations"\s*:\s*{(.*?)}/ms',$amzData,$variantVal);
								if($variantVal){
									$exist = 1;
									$variantVal[1] = '{'.$variantVal[1].'}';
									$varVal = json_decode($variantVal[1], true);
									if(!empty($varVal)){
										$index = 0;
										foreach($varVal as $key=>$value){
											if($index == 0){$option1val = $value;}
											if($index == 1){$option2val = $value;}
											if($index == 2){$option3val = $value;}
											$index++;
										}
									}
									
									preg_match('/<span\s*id="priceblock_ourprice"\s*class="a-size-medium\s*a-color-price\s*priceBlockBuyingPriceString">(.*?)<\/span>/ms',$amzData,$price_str);
									if(!$price_str){
										preg_match('/<span\s*id="priceblock_dealprice"\s*class="a-size-medium\s*a-color-price\s*priceBlockDealPriceString">(.*?)<\/span>/ms',$amzData,$price_str);
										if(!$price_str){
											preg_match('/<span\s*id="price_inside_buybox"\s*class="a-size-medium\s*a-color-price">(.*?)<\/span>/ms',$amzData,$price_str);
											if(!$price_str){
												preg_match('/<span\s*id="priceblock_saleprice"\s*class="a-size-medium\s*a-color-price\s*priceBlockSalePriceString">(.*?)<\/span>/ms',$amzData,$price_str);
												if($price_str){
													preg_match('/([0-9]+\.[0-9]+)/', $price_str[1], $p_str);
													$price = trim($p_str[1]);
												}else{
													preg_match('/<span\s*class=\'a-color-price\'>(.*?)<\/span>/ms',$amzData,$price_str);
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
									
									preg_match('/<span\s*class="priceBlockStrikePriceString\s*a-text-strike">(.*?)<\/span>/ms',$amzData,$list_str);
									if($list_str){
										preg_match('/([0-9]+\.[0-9]+)/', $list_str[1], $l_str);
										$list_price = trim($l_str[1]);
									}
									preg_match('/<tr>\s*<th\s*class="a-color-secondary\s*a-size-base\s*prodDetSectionEntry">\s*Shipping\s*Weight\s*<\/th>\s*<td\s*class="a-size-base">(.*?)\(<a\s*href=(.*?)>View\s*shipping\s*rates\s*and\s*policies<\/a>\)\s*<\/td>\s*<\/tr>/ms',$amzData,$shipping_str);
									if($shipping_str){
										$weight = trim($shipping_str[1]);
									}
									
									preg_match('/var data =(.*?)return\s*data;/ms',$amzData,$var_image_str);
									if($var_image_str){
										preg_match("/'initial':(.*?)},\s*'colorToAsin'/ms",$var_image_str[1],$var_str);
										if($var_str){
											$var_array = json_decode($var_str[1], true);
											if($var_array[0]['hiRes']){
												$var_array[0]['hiRes'] = str_replace('"','',$var_array[0]['hiRes']);
												array_push($variant_images,trim($var_array[0]['hiRes']));
											}else{
												$var_array[0]['large'] = str_replace('"','',$var_array[0]['large']);
												array_push($variant_images,trim($var_array[0]['large']));
											}
										}
									}
									
									$variants[] = array("sku" => $sku, "price" => $price, "list_price" => $list_price, "quantity" => $quantity, "option1val" => $option1val, "option2val" => $option2val, "option3val" => $option3val, "weight" => $weight, "weight_unit" => $weight_unit, "variant_images" => $variant_images);
									$variant_images = array();	
								}
							}
							if(empty($variants)){
								$option1name = '';$option2name = '';$option3name = '';
							}
						}
					}else{
						$exist = 1;
						$variants[] = array("sku" => $sku, "price" => $price, "list_price" => $list_price, "quantity" => $quantity, "option1val" => $option1val, "option2val" => $option2val, "option3val" => $option3val, "weight" => $weight, "weight_unit" => $weight_unit, "variant_images" => $variant_images);
					}
				}else{
					$exist = 1;
					$variants[] = array("sku" => $sku, "price" => $price, "list_price" => $list_price, "quantity" => $quantity, "option1val" => $option1val, "option2val" => $option2val, "option3val" => $option3val, "weight" => $weight, "weight_unit" => $weight_unit, "variant_images" => $variant_images);
				}
				
				if($exist == 0){
					$variants[] = array("sku" => $sku, "price" => $price, "list_price" => $list_price, "quantity" => $quantity, "option1val" => $option1val, "option2val" => $option2val, "option3val" => $option3val, "weight" => $weight, "weight_unit" => $weight_unit, "variant_images" => $variant_images);
				}
				
				$products = array(
                  "title" => $title, 
                  "description" => $description, 
                  "brand" => $brand, 
                  "product_type" => $product_type,  
                  "option1name" => $option1name, 
                  "option2name" => $option2name, 
                  "option3name" => $option3name,  
                  "images"  => $images,
                  "variants"  => $variants 
				);
			return array("status" => "success", "message" => $products);
	}else
	{
		return array("status" => "failure", "message" => "There was some error fetching the product, please verify the product URL again. Contact support if the issue still persist.");
	}
}