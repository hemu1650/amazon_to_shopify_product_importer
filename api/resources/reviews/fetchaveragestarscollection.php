<?php
// to fetch customization such as padding, star-icon color etc
		//if($_SERVER['HTTP_ORIGIN'] == "https://infoshoredemo123.myshopify.com")
		//	{
		//		header('Access-Control-Allow-Origin: https://infoshoredemo123.myshopify.com');  
        //	}
        header('Access-Control-Allow-Origin: *');
		

		// Create connection
		//$conn = new mysqli('127.0.0.1', 'infoshore123_dbuser', 'AP^xVt3KI&j)', 'infoshore123_mainsite');
		$conn = new mysqli('localhost', 'root', '', 'aac_dev');		
		if($conn){
    		//addlog('Database connected',"INFO");
        } else {
    		die("Database Connection Error");
        }
		// Check connection
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
        $productIdListJson = $_GET['product_id'];
        $productIdList = json_decode($productIdListJson);
        //take count of product ids --> make for loop --> pass each product_id[i] in query --> single or multiple query
        
        $listcount = count($productIdList);
       
        $outp = array();
        for($i=0; $i< $listcount; $i++)
         {
             
             $asin = mysqli_query($conn, "SELECT asin FROM product_variants WHERE shopifyproductid = $productIdList[$i]");
             $asin = mysqli_fetch_assoc($asin);
             $asin = $asin['asin'];
             //echo "<br/>This is QUERY : SELECT asin FROM product_variants WHERE shopifyproductid = ".$productIdList[$i]."<br/><br/>";
             
             //echo "<br/>This is QUERY2 : SELECT product_asin, rating FROM reviews WHERE product_asin = ".$asin."<br/><br/>";
             $stmt = $conn->prepare("SELECT product_asin, rating FROM reviews WHERE product_asin = '$asin'");
             $stmt->execute();
             $result = $stmt->get_result();
             $outp[$i] = $result->fetch_all(MYSQLI_ASSOC);
             
             
             //echo "<br/><br/>this is output of query 2:<br/><br/>";
             //print_r($outp[$i]);
             
             
             
             //$result[$i] = mysqli_query($conn, "SELECT product_asin, rating FROM reviews WHERE product_asin = $productIdList[$i]");
         }
         
         for ($i=0; $i<sizeof($outp); $i++)
         {
             for($j=0; $j<sizeof($outp[$i]); $j++)
             {
                 $outp[$i][$j]['product_asin'] = $productIdList[$i];
             }
         }
       echo json_encode($outp);
        die();
        
        
        
        
        
        
         $result = array();
         $output = array();
         for($i=0; $i< $listcount; $i++)
         {
             $result[$i] = mysqli_query($conn, "SELECT product_asin, rating FROM reviews WHERE product_asin = $productIdList[$i]");
             while($row[$i] = mysqli_fetch_assoc($result[$i])){
             $output[$i]=$row[$i];
         }
         }
         
         // $result = mysqli_query($conn, "SELECT `showreviews`, `starcolorreviews`, `paginatereviews`, `paddingreviews`, 'bordercolorreviews' FROM setting");
         
 // 		while($row = mysqli_fetch_assoc($result)){
 // 		$output[]=$row;
 // 		}
         
         
         print(json_encode($output, JSON_PRETTY_PRINT));
         mysqli_close($conn);
 
 ?>