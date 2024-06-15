<?php
	header('Access-Control-Allow-Origin: *');
	if(!isset($_GET['shop'])){
		die("");
	}
	$shop = trim($_GET['shop']);
	if($_SERVER['HTTP_ORIGIN'] != $shop) {
	//	die("");
	}

	require_once("includes/config.php");
	
	$result = $conn->query("select * from users where shopurl = '".mysqli_real_escape_string($conn, $shop)."'");
	if($result->num_rows < 1){
		die("");
	}
	$row = $result->fetch_assoc();
	$user_id = $row['id'];
    
	$result = mysqli_query($conn, "SELECT paginatereviews, showreviews FROM setting where user_id = ".$user_id);
		
	while($row = mysqli_fetch_assoc($result)){
		$output[]=$row;
	}
	$output = print(json_encode($output, JSON_PRETTY_PRINT));
	mysqli_close($conn);
?>