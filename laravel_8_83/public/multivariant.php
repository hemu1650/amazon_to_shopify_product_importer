<?php

ini_set('memory_limit', '2048M');
set_time_limit(0);

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "infoshoreapps_aac1";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// If the connection is successful
echo "Connected successfully";

// Set autocommit to true
$conn->autocommit(true);



$restop = $conn->query("SELECT * FROM `bulk_imports` WHERE status = 0 and user_id in (SELECT id FROM `users` WHERE `installationstatus` = 1 and membershiptype = 'paid' and plan > 2 and user_id = 1)");



if ($restop->num_rows) {
    echo "success";
   
    processFetchImport($restop);
} else {
    echo "Error executing query: " . $conn->error;
}

function processFetchImport($restop)
{

    global $conn;

    
    $productObj = array();
    $productsvalue = array();
    

    while ($data = $restop->fetch_assoc()) {
        $userObject = $conn->query("SELECT * FROM `users` WHERE `id`='" . $data['user_id'] . "'");

        $settingObject = $conn->query("SELECT * FROM `setting` WHERE `id`='" . $data['user_id'] . "'");

        $settingRow = $settingObject->fetch_assoc();
         $multVaraint  = $settingRow['change_status'];
        print_r( $multVaraint );

       
        
   
        if ($userObject) {
            while ($userRow = $userObject->fetch_assoc()) {

                
                $skulimit = $userRow['skulimit'];
                $skuconsumed = $userRow['skuconsumed'];
                $user_id = $userRow['id'];
                $shopurl = $userRow['shopurl'];
                $token = $userRow['token'];

                if ($skuconsumed < $skulimit) {
                    $asins = explode("\n", $data['asin']);
                    $base_url = "https://" . $data['amazon_base_url'];
                     foreach ($asins as $asin) {
                        $producturl = $base_url . "/gp/product/" . $asin . "?th=1&psc=1";

                        $productObj = proxycrawlapinew($userRow['id'], $producturl);

                        

                    

           
                    // $temp = get_html_scraper_api_content($producturl);
                    // print_r( $temp  );
                    // $res = getjsonrdata($temp,$producturl,$user_id);

                    
                     
                           $product_id = insertproducts($productObj, $producturl, $asin, $user_id,$multVaraint);
               
                        // $productsvalue[] = $product_id;
                 }

                    //  createProductByGraphQL($productsvalue, $shopurl, $token);
                }
            }
        } else {
            echo "Error executing query: " . $conn->error;
        }
    }
}

function insertproducts($productObj, $producturl, $asin, $user_id,$multVaraint) {
    global $conn;

    if (!isset($productObj['message']['title'])) {
        echo "Title is missing in product data.";
        return false;
    }

    $title = trim($productObj['message']["title"]);
 
    $description = isset($productObj['message']["description"]) ? trim($productObj['message']["description"]) : "";
    $brand = isset($productObj['message']["brand"]) ? trim($productObj['message']["brand"]) : "";
    $product_type = isset($productObj['message']['category']) ? trim($productObj['message']["category"]) : "";

    // Assuming $productObj['message']['bullet_points'] is an array
    $bullet_points = isset($productObj['message']['bullet_points']) ? $productObj['message']['bullet_points'] : array();
    $feature1 = isset($bullet_points[0]) ? trim($bullet_points[0]) : "";
    $feature2 = isset($bullet_points[1]) ? trim($bullet_points[1]) : "";
    $feature3 = isset($bullet_points[2]) ? trim($bullet_points[2]) : "";
    $feature4 = isset($bullet_points[3]) ? trim($bullet_points[3]) : "";
    $feature5 = isset($bullet_points[4]) ? trim($bullet_points[4]) : "";

    $price = isset($productObj['message']['price']) ? trim($productObj['message']['price']) : "";
    $quantity = isset($productObj['message']["in_stock___out_of_stock"]) && $productObj['message']["in_stock___out_of_stock"] == "In stock." ? 1 : 0;

    $option1name = isset($productObj['message']['option_name'][0]) ? trim($productObj['message']['option_name'][0]) : "";
    $option2name = isset($productObj['message']['option_name'][1]) ? trim($productObj['message']['option_name'][1]) : "";
    $option3name = isset($productObj['message']['option_name'][2]) ? trim($productObj['message']['option_name'][2]) : "";

    $images = json_encode($productObj['message']["images"]);

    


  

    $query = "INSERT INTO products (title, description, feature1, feature2, feature3, feature4, feature5, brand, product_type, option1name, option2name, option3name, status, user_id, imageURL, created_at, updated_at) VALUES ('" . mysqli_real_escape_string($conn, $title) . "', '" . mysqli_real_escape_string($conn, $description) . "',
    '" . mysqli_real_escape_string($conn, $feature1) . "', '" . mysqli_real_escape_string($conn, $feature2) . "', '" . mysqli_real_escape_string($conn, $feature3) . "', '" . mysqli_real_escape_string($conn, $feature4) . "', '" . mysqli_real_escape_string($conn, $feature5) . "', '" . mysqli_real_escape_string($conn, $brand) . "', '" . mysqli_real_escape_string($conn, $product_type) . "',
    '" . mysqli_real_escape_string($conn, $option1name) . "',
    '" . mysqli_real_escape_string($conn, $option2name) . "',
    '" . mysqli_real_escape_string($conn, $option3name) . "',
    'Import in progress', " . $user_id . ",
    
    '" . mysqli_real_escape_string($conn, $images) . "',
         NOW(), NOW())";
  
    if(mysqli_query($conn, $query)){
        echo "Successfully inserted into productsc  dd  ddd  <br>";
        $product_id = mysqli_insert_id($conn);
     
        $option3val =" ";
        $sku = " ";
        $option1Val = [];
        $option2Val = [];
        $allarray = [];
      if($productObj['message']['variantion1'] == 'multiVariant' && $multVaraint){
  

        foreach ($productObj['message']['asinVariationValues'] as $variation) {
            
            if ($variation['variationName'] === $option1name) {
                echo "fffffffff   ";
                $option1Val[] = array("asin" => $variation['asin']  , 'optionVal' => $variation['variationValue'] , 'imageURL' => $variation['variationImageURL']  );


               //  $allarray[] =array("asin" => $variation['asin']  , 'optionVal' => $variation['variationValue'] );
            } elseif ($variation['variationName'] === $option2name) {
                $option2Val[] = $variation['variationValue'];
               // $allarray[] =array("asin" => $variation['asin']  , 'optionVal' => $variation['variationValue'] );
            }
        }

        echo " >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>.";
      

        foreach ($option1Val as $option1Valset) {
            foreach ($option2Val as $option2Valset) {
                $imageURL   = $option1Valset['imageURL'];
                   print_r($option1Valset['imageURL']);
                            // die();
                     
                            

                             $query1 = "INSERT INTO product_variants(product_id, sku, asin, price, saleprice, detail_page_url, option1val, option2val, option3val, user_id, created_at, updated_at) values (" . 
                                         "'" . mysqli_real_escape_string($conn, $product_id) . "', " .
                                         "'" . mysqli_real_escape_string($conn, $sku) . "', " .
                                         "'" . mysqli_real_escape_string($conn, $asin) . "', " .
                                         "'" . mysqli_real_escape_string($conn, $price) . "', " .
                                         "'" . mysqli_real_escape_string($conn, $price) . "', " .
                                         "'" . mysqli_real_escape_string($conn, $imageURL) . "', " .
                                         "'" . mysqli_real_escape_string($conn, $option1Valset) . "', " .
                                         "'" . mysqli_real_escape_string($conn, $option2Valset) . "', " .
                                         "'" . mysqli_real_escape_string($conn, $option3val) . "', " .
                                         "'" . mysqli_real_escape_string($conn, $user_id) . "', " .
                                         "now(), now())";
                                 
                                 
                                 
                                     if ($conn->query($query1) === TRUE) {
                                        
                                     } else {
                                         // echo "Error: " . $query . "<br>" . $conn->error;
                                     }
                                 
                                

                                 $variant_id = mysqli_insert_id($conn);

                                 $query2 = "INSERT INTO product_images (variant_id, asin, imgurl, user_id, created_at, updated_at) VALUES ('" . mysqli_real_escape_string($conn, $variant_id) . "', '" . mysqli_real_escape_string($conn, $asin) . "', '" . mysqli_real_escape_string($conn, " ") . "', " . $user_id . ", now(), now())";
                     
                                 if(mysqli_query($conn, $query2)){
                                     echo "Successfully inserted into product_images<br>";
                                    
                                 } else {
                                     echo "Error inserting into product_images: " . mysqli_error($conn);
                                 }
         
          }


        }


      }else{

        
      

     
        $query1 = "INSERT INTO product_variants (product_id, asin, price, saleprice, detail_page_url, user_id, created_at, updated_at) VALUES (" .
            "'" . mysqli_real_escape_string($conn, $product_id) . "', " .
            "'" . mysqli_real_escape_string($conn, $asin) . "', " .
            "'" . mysqli_real_escape_string($conn, $price) . "', " .
            "'" . mysqli_real_escape_string($conn, $price) . "', " .
            "'" . mysqli_real_escape_string($conn, $producturl) . "', " .
           
            "'" . mysqli_real_escape_string($conn, $user_id) . "', " .
            "now(), now())";

        if(mysqli_query($conn, $query1)){
            echo "Successfully inserted into product_variants<br>";
            $variant_id = mysqli_insert_id($conn);

        } else {
            echo "Error inserting into product_variants: " . mysqli_error($conn);
        }
    }

    } else {
        echo "Error inserting into products: " . mysqli_error($conn);
    }

   return  $product_id;
}



function createProductByGraphQL($productsvalue, $shopurl, $token)
{
    global $conn;
    $productIdsString = implode(',', $productsvalue);
    $string = "mutation {";

    $jsonnew = '';
    $jsonnewimg='';
   

    $ProductObject = $conn->query("SELECT * FROM products WHERE status = 'Import in progress' and user_id = 1 AND block = 0 AND duplicate = 0 AND product_id IN (1)");


    if ($ProductObject->num_rows) {
        while ($ProdRow = $ProductObject->fetch_assoc()) {

            $title = isset($ProdRow['title']) ? $ProdRow['title'] : "";
            $bhtml = isset($ProdRow['description']) ? $ProdRow['description'] : "";
            $ptype = isset($ProdRow['product_type']) ? $ProdRow['product_type'] : "";
            $brand = isset($ProdRow['brand']) ? $ProdRow['brand'] : "";
            $product_id = isset($ProdRow['product_id']) ? $ProdRow['product_id'] : "";
            $option1name = isset($ProdRow['option1name']) ? $ProdRow['option1name'] : "";
            $option2name = isset($ProdRow['option2name']) ? $ProdRow['option2name'] : "";
            $option3name = isset($ProdRow['option3name']) ? $ProdRow['option3name'] : "";

            $imageURL = isset($ProdRow['imageURL']) ? $ProdRow['imageURL'] : "";
      

         $imageArry =   json_decode($imageURL);
         $jsonnewimg = "[";
         foreach ($imageArry as $src) {
            
   
	
                  
            $jsonnewimg .= "{";
                $jsonnewimg .= "mediaContentType: IMAGE,";
                $jsonnewimg .= "originalSource:\"" . $src . "\"";
            $jsonnewimg .= "}";
        
    
       
          }
          $jsonnewimg .= "]";

            $optionnameall = '';

	if ($option1name !== "" && $option2name === "" && $option3name === "") {
		$optionnameall = 'options: ["' . $option1name . '"],';
	}
	if ($option1name !== "" && $option2name !== "" && $option3name === "") {
		$optionnameall = 'options: ["' . $option1name . '","' . $option2name . '"],';
	}
	if ($option1name !== "" && $option2name !== "" && $option3name !== "") {
		$optionnameall = 'options: ["' . $option1name . '","' . $option2name . '","' . $option3name . '"],';
	}
    
    $variantResult = $conn->query("SELECT * FROM product_variants WHERE product_id = 1 AND user_id = 1 AND block = 0 AND duplicate = 0 AND shopifyvariantid = ''");

      

            $noOfVariants = $variantResult->num_rows;

            echo "fffffff       no    ";

          

            $jsonnew1 ="";
$index = 0;

            if ($noOfVariants == 1) {

              
                $variantnew = $variantResult->fetch_assoc();
                
               
              
                $price = isset($variantnew['price']) ? $variantnew['price'] : "";
                
            
                $sku = isset($variantnew['sku']) ? $variantnew['sku'] : "";
                $weight = isset($variantnew['weight']) ? $variantnew['weight'] : "";
                $weightUnit = isset($variantnew["weight_unit"]) ? $variantnew["weight_unit"] : "";
                $inventoryPolicy = isset($variantnew['inventoryPolicy']) ? $variantnew['inventoryPolicy'] : "";
                $requiresShipping = isset($variantnew['requiresShipping']) ? $variantnew['requiresShipping'] : "";
                $location_id = isset($variantnew['shopifylocationid']) ? $variantnew['shopifylocationid'] : "";
                $availableQuantity = isset($variantnew['quantity']) ? $variantnew['quantity'] : "";
                $src = isset($variantnew['imageurl']) ? $variantnew['imageurl'] : "";
                $op1 = isset($variantnew['option1val']) ? $variantnew['option1val'] : "";
                $op2 = isset($variantnew['option2val']) ? $variantnew['option2val'] : "";
                $op3 = isset($variantnew['option3val']) ? $variantnew['option3val'] : "";

              
                $jsonnew .= "{";
                $jsonnew .= "price: $price, ";

                if ($op1 != "" && $op2 == "" && $op3 == "") {
                    $jsonnew .= "options: [\"$op1\"], ";
                }
                if ($op1 != "" && $op2 != "" && $op3 == "") {
                    $jsonnew .= "options: [\"$op1\",\"$op2\"], ";
                }
                if ($op1 != "" && $op2 != "" && $op3 != "") {
                    $jsonnew .= "options: [\"$op1\",\"$op2\",\"$op3\"], ";
                }
                $jsonnew .= "weight: $weight, ";
                $jsonnew .= "weightUnit:POUNDS, ";
                if ($inventoryPolicy == "DENY") {
                    $jsonnew .= "inventoryPolicy:DENY ,";
                }
                $jsonnew .= "requiresShipping: " . ($requiresShipping == '1' ? 'true' : 'false') . ", ";
              
                $jsonnew .= "}";

              


            } elseif ($noOfVariants > 1) {

                while ($variantnew = $variantResult->fetch_assoc()) {
$index++;
                    $vid = $variantnew['id'];
                    $price = isset($variantnew['price']) ? floatval($variantnew['price']) : 0;
                    $sku = isset($variantnew['sku']) ? $variantnew['sku'] : "";
                    $weight = isset($variantnew['weight']) ? $variantnew['weight'] : "";
                    $weightUnit = isset($variantnew["weight_unit"]) ? $variantnew["weight_unit"] : "";
                    $inventoryPolicy = isset($variantnew['inventoryPolicy']) ? $variantnew['inventoryPolicy'] : "";
                    $requiresShipping = isset($variantnew['requiresShipping']) ? $variantnew['requiresShipping'] : "";
                    $optionsString = isset($variantnew['optionval']) ? $variantnew['optionval'] : "";
                    $tax =intval($variantnew['taxable']);
                    $src = isset($variantnew['imageurl']) ? $variantnew['imageurl'] : "";

                    $op1img = isset($variantnew['image']) ? trim($variantnew['image']) : "";
                    $location_id = isset($variantnew['shopifylocationid']) ? $variantnew['shopifylocationid'] : "";
                    $availableQuantity = isset($variantnew['quantity']) ? $variantnew['quantity'] : "";

                    $op1 = isset($variantnew['option1val']) ? $variantnew['option1val'] : "";
                    $op2 = isset($variantnew['option2val']) ? $variantnew['option2val'] : "";
                    $op3 = isset($variantnew['option3val']) ? $variantnew['option3val'] : "";
    
                    
                    $jsonnew .= "{";
                    $jsonnew .= "barcode: \"$vid\", ";
                    $jsonnew .= "price: $price, ";

                   
                    

                    if ($op1 != "" && $op2 == "" && $op3 == "") {
                        $jsonnew .= "options: [\"$op1\"], ";
                    }
                    if ($op1 != "" && $op2 != "" && $op3 == "") {
                        $jsonnew .= "options: [\"$op1\",\"$op2\"], ";
                    }
                    if ($op1 != "" && $op2 != "" && $op3 != "") {
                        $jsonnew .= "options: [\"$op1\",\"$op2\",\"$op3\"], ";
                    }
                

                    $jsonnew .= "weight: $weight, ";
                    $jsonnew .= "weightUnit:POUNDS ";
                    if ($inventoryPolicy == "DENY") {
                        $jsonnew .= "inventoryPolicy:DENY ,";
                    }
                
                  
                 


   if($noOfVariants === $index){

    $jsonnew .= "}";

   }else{
    $jsonnew .= "},";

   }
                

                   
                  
                    

                }
            }
        }
    } else {
        // $conn->query("update products set status = 'reimport in progress' where product_id = " . $product_id . " and user_id = " . $user_id);
        // echo "no variant found!";
    }
echo "     >>>>>>>" ;

   
    $string .= "createProduct{$product_id}: productCreate(input: {
        title: \"$title\",
        descriptionHtml: \"$bhtml\",
        vendor: \"$brand\",
        productType: \"$ptype\",
        status: ACTIVE,
        tags: [\"$brand\"],
        " . ($optionnameall ? $optionnameall : "") . "
        variants:  [ $jsonnew ]
    },
    media:$jsonnewimg
    ) {
        product {
            title
            id
            productType
            variants(first: 100) {
                edges {
                    node {
                        id
                        inventoryItem {
                            id
                        }
                        price
                        sku
                        barcode
                        selectedOptions {
                            value
                        }
                    }
                }
            }
        }
    }";
    
    $string .= '}';
  

    $datatoinsert = array();

   
    
  echo "                 ";

  
echo $string;


echo "                 ";
$string1 = 'mutation {
    createProduct34: productCreate(input: {
      title: "test 5",
      bodyHtml: "tffyhtuyuyuyu",
      vendor: "Unbranded",
      productType: "crocs",
      status: ACTIVE,
      published: true,
      options: ["Size"],
      variants: [
        {
          
          price: 10.00,
          options: ["L"],
          taxable: true,
          weight: 0
       
              },
              {
          
                price: 9.00,
                options: ["M"],
                taxable: true,
                weight: 0
             
                    }

              
       
           ]
         }  ,
         
         media :[{mediaContentType: IMAGE,originalSource:"https://images-na.ssl-images-amazon.com/images/I/31Cwy0vHaaL.jpg"}
         {mediaContentType: IMAGE,originalSource:"https://i.ebayimg.com/00/s/Njc5WDM4OA==/z/eRMAAOSwMCRlcGPR/$_57.JPG?set_id=880000500F"}{mediaContentType: IMAGE,originalSource:"https://i.ebayimg.com/00/s/Mzk4WDg0Mg==/z/Y4UAAOSwzTVlcGPR/$_57.JPG?set_id=880000500F"}]) {
      product {
        title
        id
        productType
        variants(first: 100) {
          edges {
            node {
              id
              price
              barcode
            }
          }
        }
      }
    }
  }';








//   mutation {createProduct1: productCreate(input: { title: "Velvode Men Fashion Everyday Sneakers with Comfortable Memory Foam Insoles",
//      descriptionHtml: "", 
//     vendor: "VelvodeFootwear-AustinTX",

//      productType: "Clothing, Shoes & Jewelry",


//       status: ACTIVE, tags: ["VelvodeFootwear-AustinTX"],
//        options: ["Color","Size"],
//         variants:
//          [ 
//             {barcode: "1", price: 33.99, options: ["Black"], weight: 0, weightUnit:POUNDS },
//          {barcode: "2", price: 33.99, options: ["White"], weight: 0, weightUnit:POUNDS },
//          {barcode: "3", price: 33.99, options: ["Red"], weight: 0, weightUnit:POUNDS },
//          {barcode: "4", price: 33.99, options: ["S"], weight: 0, weightUnit:POUNDS },
//          {barcode: "5", price: 33.99, options: ["M"], weight: 0, weightUnit:POUNDS },
//          {barcode: "6", price: 33.99, options: ["L"], weight: 0, weightUnit:POUNDS },
//          {barcode: "7", price: 33.99, options: ["X"], weight: 0, weightUnit:POUNDS },
//          {barcode: "8", price: 33.99, options: ["XL"], weight: 0, weightUnit:POUNDS },
//          {barcode: "9", price: 33.99, options: ["XXL"], weight: 0, weightUnit:POUNDS } ] },
         
//          media:[{mediaContentType: IMAGE,originalSource:"https://images-na.ssl-images-amazon.com/images/I/31ft0VOGIJL.jpg"}{mediaContentType: IMAGE,originalSource:"https://images-na.ssl-images-amazon.com/images/I/41J2rU7nomL.jpg"}{mediaContentType: IMAGE,originalSource:"https://images-na.ssl-images-amazon.com/images/I/41T7U5ZnaLL.jpg"}{mediaContentType: IMAGE,originalSource:"https://images-na.ssl-images-amazon.com/images/I/41dSzDaS7OL.jpg"}{mediaContentType: IMAGE,originalSource:"https://images-na.ssl-images-amazon.com/images/I/31z9H9qUvDL.jpg"}{mediaContentType: IMAGE,originalSource:"https://images-na.ssl-images-amazon.com/images/I/41Y9U0D5pjL.jpg"}{mediaContentType: IMAGE,originalSource:"https://images-na.ssl-images-amazon.com/images/I/A1LVGZG30DL.jpg"}] ) { product { title id productType variants(first: 100) { edges { node { id inventoryItem { id } price sku barcode selectedOptions { value } } } } } }
































    $test = importproduct($string , $shopurl, $token);
    
 

    $responseData = json_decode($test, true);
    print_r(json_encode($responseData));


    $product_id = null;
    $pid = null;




    foreach ($responseData['data'] as $key => $value) {

        $product_id = substr($key, strlen('createProduct'));
        $productId = $value['product']['id'];
    
		$pid = $value['product']['productType'];
     

        $datatoinsertv = array();

        
			if (isset($value['product']['variants']['edges'])) {
			
				$variantEdges = $value['product']['variants']['edges'];
	
			
				foreach ($variantEdges as $variantEdge) {
				
					$variantNode = $variantEdge['node'];
					$variantId = $variantNode['id'];
                
					$sku = $variantNode['sku'];
					$inventoryItemId = $variantNode['inventoryItem']['id'];
					$options = $variantNode['selectedOptions'];
					$optionValues = array_column($options, 'value');
					
					$option1val = isset($optionValues[0]) ? $optionValues[0] : '';
                    $option2val = isset($optionValues[1]) ? $optionValues[1] : '';
					$option3val = isset($optionValues[2]) ? $optionValues[2] : '';


                    preg_match('/(\d+)$/', $productId, $matches);
					$productId = $matches[0];
	
					preg_match('/(\d+)$/', $variantId, $matches);
					$variantId = $matches[0];
	
					preg_match('/(\d+)$/', $inventoryItemId, $matches);
					$inventoryItemId = $matches[0];
	
					// Create an array for insertion
					$datatoinsertv[] = array(
						"product_id" => $product_id,
						"shopifyproductid" => $productId,
						"shopifyvariantid" => $variantId,
						"shopifyinventoryid" => $inventoryItemId,
						"status" => 'imported',
						"sku" => $sku,
						"option1val" => $option1val,
						"option2val" => $option2val,
						"option3val" => $option3val,
						"user_id" => 1
					);
				}
			} else {
				echo "No variant data found in the response.\n";
			}
                
            $datatoinsert[] = $datatoinsertv;
	
       }
    
     
      



$productdata = array();
       

$sqlnewVariants = "UPDATE product_variants SET 
    shopifyproductid = (CASE ";
foreach ($datatoinsert as $data) {
    foreach ($data as $nestedData) {
        $shopifyproductid = $nestedData['shopifyproductid'];
        $option1val = $nestedData['option1val'];
        $option2val = $nestedData['option2val'];
        $option3val = $nestedData['option3val'];
        $product_id = $nestedData['product_id'];
		if($option1val == 'Default Title'){
        $sqlnewVariants .= "WHEN  product_id = $product_id THEN '$shopifyproductid' ";
		}
		else{
			$sqlnewVariants .= "WHEN option1val = '$option1val' AND option2val = '$option2val' AND option3val = '$option3val' AND product_id = $product_id THEN '$shopifyproductid' ";
		}
    }
}
$sqlnewVariants .= "ELSE shopifyproductid END),

shopifyvariantid = (CASE ";
foreach ($datatoinsert as $data) {
    foreach ($data as $nestedData) {
        $shopifyvariantid = $nestedData['shopifyvariantid'];
        $option1val = $nestedData['option1val'];
        $option2val = $nestedData['option2val'];
        $option3val = $nestedData['option3val'];
        $product_id = $nestedData['product_id'];
      

		   if($option1val == 'Default Title'){
			$sqlnewVariants .= "WHEN  product_id = $product_id THEN '$shopifyvariantid' ";
			}
			else{
				$sqlnewVariants .= "WHEN option1val = '$option1val' AND option2val = '$option2val' AND option3val = '$option3val' AND product_id = $product_id THEN '$shopifyvariantid' ";
			}
    }
}
$sqlnewVariants .= "ELSE shopifyvariantid END),

    shopifyinventoryid = (CASE ";
foreach ($datatoinsert as $data) {
    foreach ($data as $nestedData) {
        $shopifyinventoryid = $nestedData['shopifyinventoryid'];
        $option1val = $nestedData['option1val'];
        $option2val = $nestedData['option2val'];
        $option3val = $nestedData['option3val'];
        $product_id = $nestedData['product_id'];
       // $sqlnewVariants .= "WHEN option1val = '$option1val' AND option2val = '$option2val' AND option3val = '$option3val' AND product_id = $product_id THEN '$shopifyinventoryid' ";
	   if($option1val == 'Default Title'){
		$sqlnewVariants .= "WHEN  product_id = $product_id THEN '$shopifyinventoryid' ";
		}
		else{
			$sqlnewVariants .= "WHEN option1val = '$option1val' AND option2val = '$option2val' AND option3val = '$option3val' AND product_id = $product_id THEN '$shopifyinventoryid' ";
		}
    }
}
$sqlnewVariants .= "ELSE shopifyinventoryid END),

    status = (CASE ";
foreach ($datatoinsert as $data) {
    foreach ($data as $nestedData) {
        $status = $nestedData['status'];
        $option1val = $nestedData['option1val'];
        $option2val = $nestedData['option2val'];
        $option3val = $nestedData['option3val'];
        $product_id = $nestedData['product_id'];
      
		if($option1val == 'Default Title'){
			$sqlnewVariants .= "WHEN  product_id = $product_id THEN '$status' ";
			}
			else{
				$sqlnewVariants .= "WHEN option1val = '$option1val' AND option2val = '$option2val' AND option3val = '$option3val' AND product_id = $product_id THEN '$status' ";
			}
    }
}
$sqlnewVariants .= "ELSE status END)

WHERE user_id = 1";


if ($conn->query($sqlnewVariants) !== TRUE) {

    print_r("Not updated in product_variants table: " . $conn->error);
} else {
    print_r("Updated in product_variants table");
  
}


$sqlnewVariant = "UPDATE products SET 
    shopifyproductid = (CASE ";
foreach ($datatoinsert as $data) {
    foreach ($data as $nestedData) {
        $shopifyproductid = $nestedData['shopifyproductid'];
        $product_id = $nestedData['product_id'];
		
        $sqlnewVariant .= "WHEN  product_id = $product_id THEN '$shopifyproductid' ";
		
    }
}
$sqlnewVariant .= "ELSE shopifyproductid END),


    status = (CASE ";
foreach ($datatoinsert as $data) {
    foreach ($data as $nestedData) {
        $status = $nestedData['status'];
       $product_id = $nestedData['product_id'];
      
		
			$sqlnewVariant .= "WHEN  product_id = $product_id THEN '$status' ";
			
			
    }
}
$sqlnewVariant .= "ELSE status END)

WHERE user_id = 1";


if ($conn->query($sqlnewVariant) !== TRUE) {
    
    print_r("Not updated in products table: " . $conn->error);
} else {
    print_r("Updated in products table");

}


}


function get_html_scraper_api_content($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com/?key=e6585b7c2f1d8cc1842f3a77b4187ad0&url=".urlencode($url));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Accept: application/json"
    ));

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function importproduct($string,$shopurl,$token){

	
	$url = "https://{$shopurl}/admin/api/2023-10/graphql.json";
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token:' . $token));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(['query' => $string])); // Wrap the query in a 'query' field
	
	$response = curl_exec($curl);
	curl_close($curl);
	
	$response_arr = json_encode($response);
   
	return $response;
	
}
function cleanString($string) {
    // Remove quotes
    $string = str_replace(['"', "'"], '', $string);

   
    $string = str_replace(',', '', $string);

   
    $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);

   
    $string = trim($string);

    return $string;
}

function proxycrawlapinew($user_id, $producturl)
{
    $country = getCountry($producturl);

    $response = [];

   

$url = 'https://api.proxycrawl.com/?token=A8zfXIDXwsj2o5A_1upnJg&autoparse=true&url=' . urlencode($producturl);
    if ($country) {
        $url = 'https://api.proxycrawl.com/?token=A8zfXIDXwsj2o5A_1upnJg&autoparse=true&country=' . $country . '&url=' . urlencode($producturl);
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    $data = curl_exec($curl);
    curl_close($curl);

    $res1 = json_decode($data, true);

    print_r($res1);

    
    if ($res1 && $res1['original_status'] == 200 && isset($res1['body'])) {
        $res12 = $res1['body'];



       
        if (isset($res12['name'])) {
            $response['title'] = $res12['name'];
        } 
    
        if (isset($res12['description'])) {
            $response['description'] = $res12['description'];
        }
        if (isset($res12['brand'])) {
            $response['brand'] = $res12['brand'];
        }
        if (isset($res12['breadCrumbs'][0])) {
            $response['category'] = $res12['breadCrumbs'][0]['name'];
        }
        $response['url'] = $producturl;
        $response['currency'] = '';
        if (isset($res12['price'])) {
            $response['price'] = getAmount($res12['price']);
        }
        if (isset($res12['inStock']) && $res12['inStock'] == true) {
            $response['in_stock___out_of_stock'] = 'In stock.';
        }
        if (isset($res12['images'])) {
            $response['high_resolution_image_urls'] = $res12['mainImage'] . '|' . implode("|", $res12['images']);
        }
        if (isset($res12['features'])) {
            $response['bullet_points'] = $res12['features'];
        }

        if (isset($res12['productInformation'])) {
            foreach ($res12['productInformation'] as $info) {
                if ($info['name'] == 'ASIN') {
                    $response['asin'] = $info['value'];
                }
            }
        }
      
        if (isset($res12['asinVariationValues'])) {

          print_r($res12['asinVariationValues']);
          
            $variationValues = $res12['asinVariationValues'];
            $uniqueVariationNames = [];
            foreach ($variationValues as $x) {
                $variationName = $x['variationName'];
                $uniqueVariationNames[$variationName] = true;
            }
            $uniqueVariationNames = array_keys($uniqueVariationNames);
            $response['variantion1'] = "multiVariant";
            $response['option_name'] = $uniqueVariationNames;
            $response['asinVariationValues'] = $variationValues;
        } else {
            $response['variantion1'] = "empty";
        }

        if (isset($response['high_resolution_image_urls'])) {
            $high_resolution_image_urls = $response['high_resolution_image_urls'];
            $images = explode("|", $high_resolution_image_urls);
            $images = array_map("trim", $images);
            $response['images'] = $images;
        }

        $output = [];
        $output["status"] = "success";
        $output['message'] = $response;

        return $output;

    } else {
        $response = ["status" => "error", "message" => "crawling error."];
        return $response;
    }
}


function getCountry($producturl)
{
    $domain = parse_url($producturl, PHP_URL_HOST);
    if ($domain == "amazon.com" || $domain == "https://www.amazon.com") {
        return "US";
    } else if ($domain == "amazon.ca" || $domain == "https://www.amazon.ca") {
        return "CA";
    } else if ($domain == "amazon.in" || $domain == "https://www.amazon.in") {
        return "IN";
    } else if ($domain == "amazon.co.uk" || $domain == "https://www.amazon.co.uk") {
        return "GB";
    } else if ($domain == "amazon.com.br" || $domain == "https://www.amazon.com.br") {
        return "BR";
    } else if ($domain == "amazon.com.mx" || $domain == "https://www.amazon.com.mx") {
        return "MX";
    } else if ($domain == "amazon.de" || $domain == "https://www.amazon.de") {
        return "DE";
    } else if ($domain == "amazon.es" || $domain == "https://www.amazon.es") {
        return "ES";
    } else if ($domain == "amazon.fr" || $domain == "https://www.amazon.fr") {
        return "FR";
    } else if ($domain == "amazon.co.jp" || $domain == "https://www.amazon.co.jp") {
        return "JP";
    } else if ($domain == "amazon.cn" || $domain == "https://www.amazon.cn") {
        return "CN";
    } else if ($domain == "amazon.com.au" || $domain == "https://www.amazon.com.au") {
        return "AU";
    }
    return false;
}
function getAmount($money)
{
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


?>