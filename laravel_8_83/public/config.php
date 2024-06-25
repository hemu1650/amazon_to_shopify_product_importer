<?php

   $conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');

	// Check connection
	if ($conn->connect_error) {
		addlog("Database connection failed: " . $conn->connect_error, "ERROR");
		die("Database Connection Failed");
	}
    addlog(json_encode($conn),"CONNECTION");
	mysqli_set_charset($conn, "utf8");

?>