<?php

    // Create connection
    
    // Dev
    $conn = new mysqli('localhost', 'root', '', 'aac_dev');	

    // Production
	$conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');

	// Check connection
	if ($conn->connect_error) {		
		addlog("Database connection failed: " . $conn->connect_error, "ERROR");
		die("");
	}
	mysqli_set_charset($conn, "utf8");
?>