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
			$productid = $_GET['product_id'];
			$asin = mysqli_query($conn, "SELECT asin FROM product_variants WHERE shopifyproductid = $productid");
			$asin = mysqli_fetch_assoc($asin);
			$asin = $asin['asin'];
			$result = mysqli_query($conn, "SELECT rating FROM reviews WHERE product_asin IN ($productid, '$asin') AND status='published'");
			// $result = mysqli_query($conn, "SELECT `showreviews`, `starcolorreviews`, `paginatereviews`, `paddingreviews`, 'bordercolorreviews' FROM setting");
			
			while($row = mysqli_fetch_assoc($result)){
			$output[]=$row;
			}
			print(json_encode($output, JSON_PRETTY_PRINT));
			mysqli_close($conn);
	
	?>		