<?php
// to fetch customization such as padding, star-icon color etc
		//if($_SERVER['HTTP_ORIGIN'] == "https://infoshoredemo123.myshopify.com")
		//	{
		//		header('Access-Control-Allow-Origin: https://infoshoredemo123.myshopify.com');  
        //	}
        header('Access-Control-Allow-Origin: *');
		
		require_once("includes/config.php");
        $productid = $_GET['product_id'];                
        $reviewbegin = $_GET['reviewbegin'];
        $numrows = $_GET['numrows'];


        // $sql = "SELECT * FROM reviews WHERE product_id = $productid";
        // $result = $conn->query($sql);


        $asin = mysqli_query($conn, "SELECT asin FROM product_variants WHERE shopifyproductid = $productid");
        $asin = mysqli_fetch_assoc($asin);
        $asin = $asin['asin'];
        //echo 'here are vrar revbegin: '.$reviewbegin.'numrows:'.$numrows;
        $query = "SELECT reviewDetails, reviewTitle, rating, reviewDate, authorName FROM reviews WHERE product_asin IN ($productid, '$asin') AND status = 'published' LIMIT $numrows OFFSET $reviewbegin";
        //echo 'query : '.$query;
        
        $result = mysqli_query($conn, "SELECT reviewDetails, reviewTitle, rating, reviewDate, authorName FROM reviews WHERE product_asin IN ($productid, '$asin') AND status = 'published' LIMIT $numrows OFFSET $reviewbegin");
        // if (!$result || $result='' || $result==NULL) {
        //     echo("THIS Error is stoping the query from bringing data: " . mysqli_error($con));
        // }    
        
        //$result = mysqli_query($conn, "SELECT reviewDetails, reviewTitle, rating, reviewDate, authorName FROM reviews WHERE product_asin IN (5188807786629, 'B07PGL2ZSL') AND status = 'published' LIMIT 0,7");
//		$result = mysqli_query($conn, "SELECT `showreviews`, `starcolorreviews`, `paginatereviews`, `paddingreviews`, 'bordercolorreviews' FROM setting");
		
		while($row = mysqli_fetch_assoc($result)){
		$output[]=$row;
		}
		print(json_encode($output, JSON_PRETTY_PRINT));
		mysqli_close($conn);

?>		