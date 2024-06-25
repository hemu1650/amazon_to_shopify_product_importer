<?php
    ini_set('memory_limit', '-1');
	set_time_limit(0);

	$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
    if(!$conn){        
		addlog('Database Connection Error', "ERROR");
        die("Database Connection Error");
    }
	mysqli_set_charset($conn, "utf8");
    $filename = 'reviewsAll3208.csv';
    $FH = fopen($filename , 'w');
    fputcsv($FH, array("asin","state","rating","title","author","email","location","body","reply","created_at","replied_at"));
	$res = $conn->query("SELECT * FROM reviews WHERE user_id = 3208");
	if($res->num_rows > 0){
		while($row = $res->fetch_assoc()){
			fputcsv($FH, array($row['product_asin'],$row['status'],$row['rating'],strip_tags($row['reviewTitle']),strip_tags($row['authorName']),"","",strip_tags($row['reviewDetails']),"",$row['reviewDate'],""));
		}            
	}
	fclose($FH);
?>