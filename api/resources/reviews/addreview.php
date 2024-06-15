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
        $review = $_GET['review'];
        $title = $_GET['title'];
        $star = $_GET['star'];
        $customer_name = $_GET['customername'];
        $customer_email = $_GET['customeremail'];
        $user_id = $_GET['user_id'];
        $asin = $_GET['asin'];


$sql = "INSERT INTO reviews (product_asin, reviewDetails, reviewTitle, rating, reviewDate, authorName, authorEmail, user_id)
VALUES ('$asin', '$review', '$title', '$star', now(), '$customer_name', '$customer_email', '$user_id');";
		
		
		if ($conn->multi_query($sql) === TRUE) {
			echo "New records created successfully";
		} else {
			echo "Error: " . $sql . "<br>" . $conn->error;
		}

		print(json_encode($output, JSON_PRETTY_PRINT));
		mysqli_close($conn);

?>	