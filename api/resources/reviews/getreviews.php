<?php
        header('Access-Control-Allow-Origin: *');
		
		$conn = new mysqli('localhost', 'root', '', 'aac_dev');		
		if($conn){
    		//addlog('Database connected',"INFO");
        } else {
    		die("Database Connection Error");
        }

		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		mysqli_set_charset($conn, "utf8");
        $productid = $_GET['id'];                
       
        $result = mysqli_query($conn, "SELECT setting.*,product_variants.*,reviews.* FROM setting,product_variants,reviews WHERE product_variants.asin = reviews.product_asin AND product_variants.shopifyproductid = $productid AND reviews.user_id=setting.user_id");
        
        while($row = mysqli_fetch_assoc($result)){
		$output[]=$row;
		}
		print(json_encode($output, JSON_PRETTY_PRINT));
		mysqli_close($conn);
?>		