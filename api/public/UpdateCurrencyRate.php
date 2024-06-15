<?php
	ini_set('memory_limit','2048M');
	$logfile = fopen("logs/updateCurrencyRate.txt", "a+") or die("Unable to open log file!");
	addlog("Currency Rate Initiated","INFO");
	set_time_limit(0);
	require("config.php");
	
	$endpoint = 'latest';
	$access_key = '5e8afbc2511aa00e914ef10fd2202b7a';
	
	// Initialize CURL:
	$ch = curl_init('http://data.fixer.io/api/'.$endpoint.'?access_key='.$access_key.'');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// Store the data:
	$json = curl_exec($ch);
	curl_close($ch);
	
	// Decode JSON response:
	$exchangeRates = json_decode($json, true);
	
	// Access the exchange rate values, e.g. GBP:
	//print_r($exchangeRates);
	foreach ($exchangeRates['rates'] as $key => $value) {
	  	$currencyQuery = $conn->query("select * from currencies where currency_code = '".$key."'");
		addlog("select * from currencies where currency_code = '".$key."'","INFO");
		if($currencyQuery->num_rows == 1){
			$currencyrow = $currencyQuery->fetch_assoc();
			$conn->query("update currencies set conversionrates = '".$value."' where id = ".$currencyrow['id']);
			addlog("update currencies set conversionrates = '".$value."' where id = ".$currencyrow['id'],"INFO");
		}else{
			addlog('Record not Found',"INFO");
		}
	}
	
	function addlog($message, $type){
    	global $logfile;
    	$txt = date("Y-m-d H:i:s")." [".$type."]: ".$message."\n";
    	fwrite($logfile, $txt);
	}
?>