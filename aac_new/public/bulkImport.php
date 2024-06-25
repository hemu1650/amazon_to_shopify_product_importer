<?php
global $logfile;
ini_set('memory_limit', '2048M');
// echo "1";
require_once "includes/config.php";
$logfile = fopen("logs/bulkImport.txt", "a+") or die("Unable to open log file!");
addlog("bulkImport Initiated", "INFO");
set_time_limit(0);
require "../app/Http/Controllers/getJson.php";
//    require("proxycrawl/test.php");
// echo "test1 ";
// $conn = new mysqli('127.0.0.1', 'infoshoreapps_aac', '%@A{}QL;}RE&', 'infoshoreapps_aac');
$conn = new mysqli('localhost', 'root', '', 'infoshoreapps_aac');
// echo "test2 ";
$failed = array();
if ($conn) {
    addlog('Database connected', "INFO");
} else {
    addlog('Database Connection Error', "ERROR");
    die("Database Connection Error");
}
mysqli_set_charset($conn, "utf8");
$cronQuery = $conn->query("select isrunning from crons where crontype = 'bulkimport'");
$cronrow = $cronQuery->fetch_assoc();
// if ($cronrow['isrunning'] == 1) {
//     @mail("pankajnarang81@gmail.com", "bulk import: Cron already running", "bulkimport: Cron already running");
//     die("Connection failure!");
// }
// echo "test3 ";
$conn->query("update crons set lastrun = now(), isrunning = 1 where crontype = 'bulkimport'");
// echo "test4 ";
$conn->autocommit(true);
$restop = $conn->query("SELECT * FROM `bulk_imports` WHERE status = 0 and user_id in (SELECT id FROM `users` WHERE `installationstatus` = 1 and membershiptype = 'paid' and plan > 2)");
// echo "test5 ";
addlog("bulk import in progress for count" . $restop->num_rows . " rows", "INFO");
// print_r($restop);
while ($data = $restop->fetch_assoc()) {
    addlog("SELECT * FROM `users` WHERE `id` = '" . $data['user_id'] . "'", "USER QUERY");
    if ($userObject = $conn->query("SELECT * FROM `users` WHERE `id`='" . $data['user_id'] . "'")) {
        while ($userRow = $userObject->fetch_assoc()) {
            $skulimit = $userRow['skulimit'];
            addlog("skulimit for user " . $data['user_id'] . " is " . $skulimit, "ERROR");
            $skuconsumed = $userRow['skuconsumed'];
            if ($skuconsumed < $skulimit) {
                $asins = explode("\n", $data['asin']);
                addlog("asins being performed now" . json_encode($data['asin']), "INFO");
                $pendingSKUCnt = $skulimit - $skuconsumed;
                addlog("pending asins count are now" . $pendingSKUCnt, "INFO");
                $skuconsumed = $skuconsumed + addMultipleProduct($asins, $data['amazon_base_url'], $pendingSKUCnt, $userRow, $data['id']);
                addlog("skuconsumed update query " . "update users SET skuconsumed= '" . $skuconsumed . "' WHERE id = " . $data['id'] . "", "INFO");
                $conn->query("update users SET skuconsumed= '" . $skuconsumed . "' WHERE id = " . $data['id'] . "");
            }
        }
    }
} // while loop

$conn->query("update crons set lastrun = now(), isrunning = 0 where crontype = 'bulkimport'");

function addMultipleProduct($asins, $base_url, $pendingSKUCnt, $userDetails, $request_id)
{
    addlog("add Multiple Product Execution started", "STARTED");
    
	// echo "test1 <pre>";
	// print_r($asins);
	// echo "</pre>";
	// echo "\n";

	// echo "test2 <pre>";
	// print_r($base_url);
	// echo "</pre>";
	// echo "\n";

	// echo "test3 <pre>";
	// print_r($pendingSKUCnt);
	// echo "</pre>";
	// echo "\n";

	// echo "test4 <pre>";
	// print_r($userDetails);
	// echo "</pre>";
	// echo "\n";

	// echo "test5 <pre>";
	// print_r($request_id);
	// echo "</pre>";
	// echo "\n";

    global $conn;
    $base_url = "https://" . $base_url;
    $failedAsin = array();
    $successImports = 0; 
    $user_id = $userDetails['id'];
    $shopurl = $userDetails['shopurl'];
    $token = $userDetails['token'];
    $failed_count = 0;
    foreach ($asins as $asin) {
        $asin = trim($asin);
        // echo $asin;
        if ($asin != "") {
            addlog("SELECT * FROM `product_variants` WHERE `asin`='" . trim($asin) . "' and user_id = " . $user_id, "INFO");
            $existance = $conn->query("SELECT * FROM `product_variants` WHERE `asin`='" . trim($asin) . "' and user_id = " . $user_id);

			// echo "test6 <pre>";
			// print_r($existance);
			// echo "</pre>";
			// echo "\n";

            if ($existance->num_rows == 0) { // does not exist already

				// echo "test7";
				
                if ($pendingSKUCnt > 0 && $asin != "") {
					
					// echo "test8";

                    $producturl = $base_url . "/gp/product/" . $asin . "?th=1&psc=1";

                    // $producturl= preg_replace('/\?/', '', $producturl, 1);
                    echo "\n";
                    echo $producturl;
					echo "<br>";
                    $res = array();
                    if ($user_id == 1) {
                        $res = proxycrawlapi($user_id, $producturl);
                    } else {
                        $temp = get_html_scraper_api_content($producturl);
                        $res = getjsonrdata($temp, $producturl, $user_id);
                    }

                    if ($res["status"] != "success" || ($res["status"] == "success" && (!isset($res["message"]["title"]) || trim($res["message"]["title"]) == ""))) {
						if ($user_id == 1) {
							$res = proxycrawlapi($user_id, $producturl);
                            // print_r($res);
						} else {
							$temp = get_html_scraper_api_content($producturl);
							$res = getjsonrdata($temp, $producturl, $user_id);
						}
					}
					

                    if ($res["status"] != "success" || ($res["status"] == "success" && (!is_array($res["message"]) || !isset($res["message"]["title"]) || trim($res["message"]["title"]) == ""))) {
						// echo "dfdf    fgf";
						$temp = get_html_scraper_api_content($producturl);
						$res = getjsonrdata($temp, $producturl, $user_id);
						// echo "diooeuropetore dfgedouroieut";
					}
					

                    if ($res['status'] == "success") {

                        $product_data = $res['message'];
                        $importedProduct = "";
                        if ($user_id != 1) {
                            $importedProduct = addProduct3208($product_data, $user_id, $shopurl, $token, $asin, $producturl);
                        } else {
                            $importedProduct = addProduct($product_data, $user_id, $shopurl, $token, $asin, $producturl);
                        }

                        if ($importedProduct == "success") {
                            addlog("data imported for asin " . $asin, "ERROR");
                            echo " data imported successs   "; echo "<br>";
                            $successImports = $successImports + 1;
                            if ($successImports >= $pendingSKUCnt) {
                                @mail($userRow['email'], "Product Import Failed", "Import Limit Exceeded Increase Your Package");
                                break;
                            }
                        } else {
                            echo "  failed   ";
                            $failed_count = $failed_count + 1;
                            $failedAsin[] = $asin;
                        }
                    } else {
                        $conn->query("insert into failed_productimports(url, reason, type, user_id) values('" . mysqli_real_escape_string($conn, $producturl) . "', 'Something Wrong: Unable to import', 'Database', '" . mysqli_real_escape_string($conn, $user_id) . "')");
                        $failed_count = $failed_count + 1;
                        $failedAsin[] = $asin;
                    }
                } else {
                    $failed_count = $failed_count + 1;
                    $failedAsin[] = $asin;
                }
            } else {
                $failed_count = $failed_count + 1;
                $failedAsin[] = $asin;
            }
        }
    }
    addlog("successful asin implemeted are " . $successImports, "INFO");
    addlog("failed asin implemeted are" . sizeof($failedAsin), "INFO");
    $tmp = "";
    $i = 0;


	// echo  "failedAsin:\n";
	// print_r($failedAsin);
	// echo  "failedAsin:\n";


    if (sizeof($failedAsin) > 0) {
        for ($i; $i < sizeof($failedAsin); $i++) {
            $tmp = $failedAsin[$i] . "\n";
        }
    }
	

    $query = "UPDATE `bulk_imports` SET `status`=1, `failed` = '" . sizeof($failedAsin) . "', `failed_asin` ='" . json_encode($failedAsin) . "'," . "`updated_at`= NOW() WHERE `id`=" . $request_id . "";
    $conn->query($query);
    addlog("updating bulk import is " . $query, "INFO");
    return $successImports;
}

function get_html_scraper_api_content($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://api.scraperapi.com/?key=e6585b7c2f1d8cc1842f3a77b4187ad0&url=" . urlencode($url));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Accept: application/json",
    ));

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function getCountry($producturl)
{
    $domain = parse_url($producturl, PHP_URL_HOST);
    if ($domain == "amazon.com" || $domain == "www.amazon.com") {
        return "US";
    } else if ($domain == "amazon.ca" || $domain == "www.amazon.ca") {
        return "CA";
    } else if ($domain == "amazon.in" || $domain == "www.amazon.in") {
        return "IN";
    } else if ($domain == "amazon.co.uk" || $domain == "www.amazon.co.uk") {
        return "GB";
    } else if ($domain == "amazon.com.br" || $domain == "www.amazon.com.br") {
        return "BR";
    } else if ($domain == "amazon.com.mx" || $domain == "www.amazon.com.mx") {
        return "MX";
    } else if ($domain == "amazon.de" || $domain == "www.amazon.de") {
        return "DE";
    } else if ($domain == "amazon.es" || $domain == "www.amazon.es") {
        return "ES";
    } else if ($domain == "amazon.fr" || $domain == "www.amazon.fr") {
        return "FR";
    } else if ($domain == "amazon.co.jp" || $domain == "www.amazon.co.jp") {
        return "JP";
    } else if ($domain == "amazon.cn" || $domain == "www.amazon.cn") {
        return "CN";
    } else if ($domain == "amazon.com.au" || $domain == "www.amazon.com.au") {
        return "AU";
    }
    return false;
}

function proxycrawlapi($user_id, $producturl)
{
    // $country = getCountry($producturl);
    // addlog("proxycrawlapi 1", $country);
    // $response = [];
    // $url = 'https://api.proxycrawl.com/?token=A8zfXIDXwsj2o5A_1upnJg&autoparse=true&url=' . urlencode($producturl);
    // addlog("proxycrawlapi 2", $url);
    // if ($country) {
    //     $url = 'https://api.proxycrawl.com/?token=A8zfXIDXwsj2o5A_1upnJg&autoparse=true&country=' . $country . '&url=' . urlencode($producturl);
    //     addlog("proxycrawlapi 3", $url);
    // }
    
    // // $curl = curl_init();
    // // curl_setopt($curl, CURLOPT_URL, $url);
    // // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    // // curl_setopt($curl, CURLOPT_HEADER, false);
    // // $data = curl_exec($curl);

    // $curl = curl_init();

    // curl_setopt_array($curl, array(
    // CURLOPT_URL => $url,
    // CURLOPT_RETURNTRANSFER => true,
    // CURLOPT_ENCODING => '',
    // CURLOPT_MAXREDIRS => 10,
    // CURLOPT_TIMEOUT => 0,
    // CURLOPT_FOLLOWLOCATION => true,
    // CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    // CURLOPT_CUSTOMREQUEST => 'GET',
    // ));

    // $data = curl_exec($curl);

    // curl_close($curl);

    // echo $response;
    // echo "anshu 222222222222";

    // // Check for cURL errors
    // if ($data === false) {
    //     $response = array("status" => "error", "message" => "cURL error: " . curl_error($curl));
    //     curl_close($curl);
    //     return $response;
    // }

    // curl_close($curl);
    
    // $res1 = json_decode($data, true);

    // addlog("proxycrawlapi 4", $res1);

    $country = getCountry($producturl);
addlog("proxycrawlapi 1", $country);
$response = [];
$url = 'https://api.proxycrawl.com/?token=A8zfXIDXwsj2o5A_1upnJg&autoparse=true&url=' . urlencode($producturl);
addlog("proxycrawlapi 2", $url);
if ($country) {
    $url = 'https://api.proxycrawl.com/?token=A8zfXIDXwsj2o5A_1upnJg&autoparse=true&country=' . $country . '&url=' . urlencode($producturl);
    addlog("proxycrawlapi 3", $url);
}

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
));

$data = curl_exec($curl);

// Check for cURL errors
if ($data === false) {
    $response = array("status" => "error", "message" => "cURL error: " . curl_error($curl));
    curl_close($curl);
    echo json_encode($response);
    return;
}

curl_close($curl);

$res1 = json_decode($data, true);

addlog("proxycrawlapi 4", $res1);

$response = $res1;

echo json_encode($response);
echo "anshu 222222222222";


    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response = array("status" => "error", "message" => "JSON decode error: " . json_last_error_msg());
        return $response;
    }

    addlog("proxycrawlapi 5");

    if (isset($res1['original_status']) && $res1['original_status'] == 200 && isset($res1['body'])) 
    { 
        $res = $res1['body'];
        if (isset($res['name'])) {$response['title'] = $res['name'];}
        if (isset($res['description'])) {$response['description'] = $res['description'];}
        if (isset($res['brand'])) {$response['brand'] = $res['brand'];}
        if (isset($res['breadCrumbs'][0])) {$response['category'] = $res['breadCrumbs'][0]['name'];}
        $response['url'] = $producturl;
        $response['currency'] = '';
        if (isset($res['price'])) {
            $response['price'] = getAmount($res['price']);
            echo "\n";
            echo "price: " . $response['price'] . "\n";
        }
        if (isset($res['inStock']) && $res['inStock'] == true) {$response['in_stock___out_of_stock'] = 'In stock.';}
        if (isset($res['images'])) {$response['high_resolution_image_urls'] = $res['mainImage'] . '|' . implode("|", $res['images']);}
        if (isset($res['features'])) {$response['bullet_points'] = $res['features'];}

        if (isset($res['productInformation'][0])) {
            for ($i = 0; $i < count($res['productInformation']); $i++) {
                if ($res['productInformation'][$i]['name'] == 'ASIN') {$response['asin'] = $res['productInformation'][$i]['value'];}
            }
        }
        if ($user_id == 3208) {
            $description = "";
            if (isset($res['description'])) {
                $description = "<h4>" . trim($res['description']) . "</h4>";
            }

            if (isset($res['features']) && is_array($res['features'])) {
                $description .= "<h3><strong>Features</strong></h3><ul>";
                foreach ($res['features'] as $feature) {
                    $description .= '<li><h4><span class="a-list-item">' . $feature . '</span></h4></li>';
                }
                $description .= "</ul>";
            }
            if (isset($res['productInformation']) && is_array($res['productInformation'])) {
                $description .= "<h3><b>Specifications</b></h3>";
                foreach ($res['productInformation'] as $productInfo) {
                    $description .= '<h4><b>' . $productInfo['name'] . ' </b><span>: ' . $productInfo['value'] . '</span></h4>';
                }
            }
            $response['description'] = $description;
            $response['bullet_points'] = "";
        }
        if (isset($response['high_resolution_image_urls'])) {
            $high_resolution_image_urls = $response['high_resolution_image_urls'];
            $images = explode("|", $high_resolution_image_urls);
            $images = array_map("trim", $images);
            $response['images'] = $images;
        }
        $output = array();
        $output["status"] = "success";
        $output['message'] = $response;
        return $output;
    } else { 
        $response = array("status" => "error", "message" => "crawling error.");
        return $response;
    }
}


function addProduct3208($productObj, $user_id, $shopurl, $token, $asin, $producturl)
{	
	echo "<br>";
	echo "enter function addProduct3208 \n";
	
	// echo "<pre>";
	// print_r($productObj);
	// echo "</pre>";

	echo "<br>";
	

    addlog("Adding DATA TO DATABASE", "AddProduct");
    global $conn;	
    if (isset($productObj["title"])) {
		$title = trim($productObj["title"]);
		if ($title == "") {
			echo "title blank \n";
			addlog("title is not set", "INFO");
			return false;
		}
	} else {
		echo "title is not set \n";
		return false;
	}	

    if (isset($productObj["description"])) {
		$description = trim($productObj["description"]);
		// Your further processing for description...
	} else {
		echo "description is not set \n";
		addlog("description is not set", "INFO");
		// return false;
		$description = "";
	}

	if (isset($productObj["brand"])) {
		$brand = $productObj["brand"];
		// echo "brand: " . $brand . "\n";

		// Your further processing for description...
	} else {
		echo " brand is not set \n";
		addlog("brand is not set", "INFO");
		// return false;
		$brand = "";
	}

	if (isset($productObj["product_type"])) {
		$product_type = trim($productObj["product_type"]);
		// Your further processing for description...
	} else {
		echo "product_type is not set \n";
		addlog("product_type is not set", "INFO");
		echo "<br>";
		$product_type = "";
		// return false;
	}	
   
    $sku = $asin;
    $feature1 = "";
    $feature2 = "";
    $feature3 = "";
    $feature4 = "";
    $feature5 = "";
    if (isset($productObj['bullet_points'])) {
        $bullet_points = $productObj['bullet_points'];
        $tempArr = $bullet_points;
        $feature1 = isset($tempArr[0]) ? $tempArr[0] : "";
        $feature2 = isset($tempArr[1]) ? $tempArr[1] : "";
        $feature3 = isset($tempArr[2]) ? $tempArr[2] : "";
        $feature4 = isset($tempArr[3]) ? $tempArr[3] : "";
        $feature5 = isset($tempArr[4]) ? $tempArr[4] : "";
    }
    // $price = trim($productObj["price"]);
	if (isset($productObj["price"])) {
		$price = trim($productObj["price"]);
		// Your further processing for description...
	} else {
		echo "price is not set \n";
		$price = "";
		// return false;
	}
    $quantity = 0;
    if (isset($productObj["in_stock___out_of_stock"]) && $productObj["in_stock___out_of_stock"] == "In stock.") {
        $quantity = 1;
    }

    if (isset($productObj["images"]) && is_array($productObj["images"])) {
		$images = $productObj["images"];
		// Further processing of $images...
	} else {
		echo "images is not set or is not an array \n";
		return false;
	}
	
    $query = "INSERT INTO products(title, description, feature1, feature2, feature3, feature4, feature5, brand, product_type, status, user_id, created_at, updated_at) values ('" . mysqli_real_escape_string($conn, $title) . "', '" . mysqli_real_escape_string($conn, $description) . "', '" . mysqli_real_escape_string($conn, $feature1) . "', '" . mysqli_real_escape_string($conn, $feature2) . "', '" . mysqli_real_escape_string($conn, $feature3) . "', '" . mysqli_real_escape_string($conn, $feature4) . "', '" . mysqli_real_escape_string($conn, $feature5) . "', '" . mysqli_real_escape_string($conn, $brand) . "', '" . mysqli_real_escape_string($conn, $product_type) . "', 'Import in progress', " . $user_id . ", now(), now())";
    $conn->query($query);
    $product_id = $conn->insert_id;
    $query = "INSERT INTO product_variants(product_id, sku, asin, price, saleprice, detail_page_url, user_id, created_at, updated_at) values (" . $product_id . ", '" . mysqli_real_escape_string($conn, $sku) . "', '" . mysqli_real_escape_string($conn, $asin) . "', '" . mysqli_real_escape_string($conn, $price) . "', '" . mysqli_real_escape_string($conn, $price) . "', '" . mysqli_real_escape_string($conn, $producturl) . "', " . $user_id . ", now(), now())";
    $conn->query($query);
    $variant_id = $conn->insert_id;
    foreach ($images as $imageUrl) {
        $query = "INSERT INTO product_images(variant_id, asin, imgurl, user_id, created_at, updated_at) VALUES ('" . mysqli_real_escape_string($conn, $variant_id) . "', '" . mysqli_real_escape_string($conn, $asin) . "', '" . mysqli_real_escape_string($conn, $imageUrl) . "', " . $user_id . ", now(), now())";
        $conn->query($query);
    }
    //return "success";

    $res = insertToShopify($user_id, $shopurl, $token, $productObj, $product_id);
	// echo "1111111111";
	// echo "<pre>";
	// print_r($res);
	// echo "</pre>";
	// echo "1111111111";
	// echo "\n";
    if ($res) {
        return "success";
    } else {
        return false;
    }
}

function addProduct($productObj, $user_id, $shopurl, $token, $asin, $producturl)
{	
    $phpArray = json_decode($productObj, true);
	echo "<br>";
	echo "enter function addProduct \n";	

	echo "<br>";

    $title = $phpArray['Title'];
    $description = $phpArray['description'];
    $brand = $phpArray['brand'];
    if (isset($phpArray['product_type'])) {
        $product_type = $phpArray['product_type'];
    } else {
        // Handle the case where the key is missing (e.g., assign a default value)
        $product_type = null; // Or some other appropriate value
    }
    $bullet_points = $phpArray['bullet_points'];
    $price = $phpArray['price'];
    // $in_stock___out_of_stock = $phpArray['in_stock___out_of_stock'];
    if (isset($phpArray['in_stock___out_of_stock'])) {
        $in_stock___out_of_stock = $phpArray['in_stock___out_of_stock'];
    } else {
        // Handle the case where the key is missing (e.g., assign a default value)
        $in_stock___out_of_stock = null; // Or some other appropriate value
    }
    $highResolutionImageUrls1 = $phpArray['high_resolution_image_urls'];
	$images = explode('|', $highResolutionImageUrls1);

    addlog("Adding DATA TO DATABASE", "AddProduct");
    global $conn;	
    if (isset($title)) {
		$title = trim($title);
		if ($title == "") {
			echo "title blank \n";
			addlog("title is not set", "INFO");
			return false;
		}
	} else {
		echo "title is not set \n";
        addlog("title is not set", "INFO");
		return false;
	}	

    if (isset($description)) {
		$description = trim($description);
		// Your further processing for description...
	} else {
		echo "description is not set \n";
		addlog("description is not set", "INFO");
		$description = "";
		// return false;
	}

	if (isset($brand)) {
		$brand = $brand;
		// echo "brand: " . $brand . "\n";
	} else {
		echo " brand is not set \n";
		addlog("brand is not set", "INFO");
		$brand = "";
		// return false;
	}

	if (isset($product_type)) {
		$product_type = trim($product_type);
		// Your further processing for description...
	} else {
		echo "product_type is not set \n";
		addlog("product_type is not set", "INFO");
		$product_type = "";
		echo "<br>";
		// return false;
	}	
   
    $sku = $asin;
    $feature1 = "";
    $feature2 = "";
    $feature3 = "";
    $feature4 = "";
    $feature5 = "";
    if (isset($bullet_points)) {
        $bullet_points = $bullet_points;
        $tempArr = $bullet_points;
        $feature1 = isset($tempArr[0]) ? $tempArr[0] : "";
        $feature2 = isset($tempArr[1]) ? $tempArr[1] : "";
        $feature3 = isset($tempArr[2]) ? $tempArr[2] : "";
        $feature4 = isset($tempArr[3]) ? $tempArr[3] : "";
        $feature5 = isset($tempArr[4]) ? $tempArr[4] : "";
    }
    // $price = trim($productObj["price"]);
	if (isset($price)) {
		$price = trim($price);
		// Your further processing for description...
	} else {
		echo "price is not set \n";
        addlog("price is not set", "INFO");
		$price = 0.00;
		// return false;
	}
    $quantity = 0;
    if (isset($in_stock___out_of_stock) && $in_stock___out_of_stock == "In stock.") {
        $quantity = 1;
    }

    if (isset($images) && is_array($images)) {
		$images = $images;
		// Further processing of $images...
	} else {
		echo "images is not set or is not an array \n";
        addlog("images is not set or is not an array", "INFO");
		$images = "";
		// return false;
	}
	
    $query = "INSERT INTO products(title, description, feature1, feature2, feature3, feature4, feature5, brand, product_type, status, user_id, created_at, updated_at) values ('" . mysqli_real_escape_string($conn, $title) . "', '" . mysqli_real_escape_string($conn, $description) . "', '" . mysqli_real_escape_string($conn, $feature1) . "', '" . mysqli_real_escape_string($conn, $feature2) . "', '" . mysqli_real_escape_string($conn, $feature3) . "', '" . mysqli_real_escape_string($conn, $feature4) . "', '" . mysqli_real_escape_string($conn, $feature5) . "', '" . mysqli_real_escape_string($conn, $brand) . "', '" . mysqli_real_escape_string($conn, $product_type) . "', 'Import in progress', " . $user_id . ", now(), now())";
    $conn->query($query);
    $product_id = $conn->insert_id;
    $query = "INSERT INTO product_variants(product_id, sku, asin, price, saleprice, detail_page_url, user_id, created_at, updated_at) values (" . $product_id . ", '" . mysqli_real_escape_string($conn, $sku) . "', '" . mysqli_real_escape_string($conn, $asin) . "', '" . mysqli_real_escape_string($conn, $price) . "', '" . mysqli_real_escape_string($conn, $price) . "', '" . mysqli_real_escape_string($conn, $producturl) . "', " . $user_id . ", now(), now())";
    $conn->query($query);
    $variant_id = $conn->insert_id;
    foreach ($images as $imageUrl) {
        $query = "INSERT INTO product_images(variant_id, asin, imgurl, user_id, created_at, updated_at) VALUES ('" . mysqli_real_escape_string($conn, $variant_id) . "', '" . mysqli_real_escape_string($conn, $asin) . "', '" . mysqli_real_escape_string($conn, $imageUrl) . "', " . $user_id . ", now(), now())";
        $conn->query($query);
    }
    //return "success";

    $res = insertToShopify($user_id, $shopurl, $token, $productObj, $product_id);
	// echo "1111111111";
	// echo "<pre>";
	// print_r($res);
	// echo "</pre>";
	// echo "1111111111";
	// echo "\n";
    if ($res) {
        return "success";
    } else {
        return false;
    }
}

// function addProduct($product_data, $user_id, $shopurl, $token, $asin, $producturl)
// {
// 	echo "function addProduct \n";
//     $productObj = json_decode($product_data, true);
//     addlog("Adding DATA TO DATABASE", "AddProduct");
//     global $conn;
//     $title = "";
//     $description = "";
//     $brand = "";
//     $product_type = "";
//     $sku = "";
//     $asin = "";
//     $url = "";
//     $price = 0;
//     $list_price = 0;
//     $images = array();
//     $feature1 = "";
//     $feature2 = "";
//     $feature3 = "";
//     $feature4 = "";
//     $feature5 = "";
//     $quantity = 0;
//     if (isset($productObj['Title'])) {
//         $title = $productObj['Title'];
//     }
//     if (isset($productObj['description'])) {
//         $description = $productObj['description'];
//     }
//     if (isset($productObj['brand'])) {
//         $brand = $productObj['brand'];
//     }
//     if (isset($productObj['category'])) {
//         $product_type = $productObj['category'];
//     }
//     if (isset($productObj['asin'])) {
//         $asin = $productObj['asin'];
//         $sku = $productObj['asin'];
//     }
//     if (isset($productObj['url'])) {
//         $url = $productObj['url'];
//     }
//     if (isset($productObj['currency'])) {
//         $currency = $productObj['currency'];
//     }
//     if ($currency == "") {
//         $currency = verifyCurrency($producturl);
//     }
//     if (isset($productObj['price'])) {
//         $price = $productObj['price'];
//         $price = getAmount($price);
//     }
//     if ($price < 1) {
//         addlog("Price 0", "INFO");
//         $conn->query("insert into failed_productimports(url, reason, type, user_id) values('Bulk Import', 'Something Wrong: Unable to import', 'Database', '" . mysqli_real_escape_string($conn, $user_id) . "')");
//         return false;
//     }
//     if (isset($productObj['list_price'])) {
//         $list_price = $productObj['list_price'];
//         $list_price = getAmount($list_price);
//     } else {
//         $list_price = $price;
//     }
//     if (isset($productObj['in_stock___out_of_stock']) && $productObj['in_stock___out_of_stock'] == 'In stock.') {
//         $quantity = 1;
//     }
//     if (isset($productObj['high_resolution_image_urls'])) {
//         $high_resolution_image_urls = $productObj['high_resolution_image_urls'];
//         $images = explode("|", $high_resolution_image_urls);
//         $images = array_map("trim", $images);

//     }

//     if (isset($productObj['bullet_points'])) {
//         $bullet_points = $productObj['bullet_points'];
//         //$tempArr = explode("|", $bullet_points);
//         //$tempArr = array_map("trim", $tempArr);
//         $tempArr = $bullet_points;
//         $feature1 = isset($tempArr[0]) ? $tempArr[0] : "";
//         $feature2 = isset($tempArr[1]) ? $tempArr[1] : "";
//         $feature3 = isset($tempArr[2]) ? $tempArr[2] : "";
//         $feature4 = isset($tempArr[3]) ? $tempArr[3] : "";
//         $feature5 = isset($tempArr[4]) ? $tempArr[4] : "";
//     }
//     $query = "INSERT INTO products(title, description, brand, product_type, status, user_id) values ('" . mysqli_real_escape_string($conn, $title) . "', '" . mysqli_real_escape_string($conn, $description) . "', '" . mysqli_real_escape_string($conn, $brand) . "', '" . mysqli_real_escape_string($conn, $product_type) . "', 'Import in progress', " . $user_id . ")";
//     $conn->query($query);
//     $product_id = $conn->insert_id;
//     $query = "INSERT INTO product_variants(product_id, sku, asin, price, saleprice, currency, detail_page_url, user_id) values (" . $product_id . ", '" . mysqli_real_escape_string($conn, $sku) . "', '" . mysqli_real_escape_string($conn, $asin) . "', '" . mysqli_real_escape_string($conn, $price) . "', '" . mysqli_real_escape_string($conn, $list_price) . "', '" . mysqli_real_escape_string($conn, $currency) . "', '" . mysqli_real_escape_string($conn, $producturl) . "', " . $user_id . ")";
//     $conn->query($query);
//     $variant_id = $conn->insert_id;
//     foreach ($images as $imageUrl) {
//         if ($imageUrl != "") {
//             $query = "INSERT INTO product_images(variant_id, asin, imgurl, user_id) VALUES ('" . mysqli_real_escape_string($conn, $variant_id) . "', '" . mysqli_real_escape_string($conn, $asin) . "', '" . mysqli_real_escape_string($conn, $imageUrl) . "', " . $user_id . ")";
//             $conn->query($query);
//         }
//     }
//     //return "success";

//     $res = insertToShopify($user_id, $shopurl, $token, $productObj, $product_id);
//     if ($res) {
//         return "success";
//     } else {
//         $conn->query("insert into failed_productimports(url, reason, type, user_id) values('Bulk Import', 'Something Wrong: Unable to import', 'Database', '" . mysqli_real_escape_string($conn, $user_id) . "')");
//         return false;
//     }
// }

function verifyCurrency($producturl)
{
    $domain = parse_url($producturl, PHP_URL_HOST);
    if ($domain == "amazon.com" || $domain == "www.amazon.com") {
        return "USD";
    }
    if ($domain == "amazon.ca" || $domain == "www.amazon.ca") {
        return "CAD";
    }
    if ($domain == "amazon.in" || $domain == "www.amazon.in") {
        return "INR";
    }
    if ($domain == "amazon.co.uk" || $domain == "www.amazon.co.uk") {
        return "GBP";
    }
    if ($domain == "amazon.com.br" || $domain == "www.amazon.com.br") {
        return "BRL";
    }
    if ($domain == "amazon.com.mx" || $domain == "www.amazon.com.mx") {
        return "MXN";
    }
    if ($domain == "amazon.de" || $domain == "www.amazon.de") {
        return "EUR";
    }
    if ($domain == "amazon.es" || $domain == "www.amazon.es") {
        return "EUR	";
    }
    if ($domain == "amazon.fr" || $domain == "www.amazon.fr") {
        return "EUR";
    }
    if ($domain == "amazon.it" || $domain == "www.amazon.it") {
        return "EUR";
    }
    if ($domain == "amazon.co.jp" || $domain == "www.amazon.co.jp") {
        return "JPY";
    }
    if ($domain == "amazon.cn" || $domain == "www.amazon.cn") {
        return "CNY";
    }
    if ($domain == "amazon.com.au" || $domain == "www.amazon.com.au") {
        return "AUD";
    }
    if ($domain == "amazon.ae" || $domain == "www.amazon.ae") {
        return "AED";
    }
    return false;
}

function verifyAmazonDomain($producturl)
{
    $domain = parse_url($producturl, PHP_URL_HOST);
    if ($domain == "amazon.com" || $domain == "www.amazon.com") {
        return true;
    }
    if ($domain == "amazon.ca" || $domain == "www.amazon.ca") {
        return true;
    }
    if ($domain == "amazon.in" || $domain == "www.amazon.in") {
        return true;
    }
    if ($domain == "amazon.co.uk" || $domain == "www.amazon.co.uk") {
        return true;
    }
    if ($domain == "amazon.com.br" || $domain == "www.amazon.com.br") {
        return true;
    }
    if ($domain == "amazon.com.mx" || $domain == "www.amazon.com.mx") {
        return true;
    }
    if ($domain == "amazon.de" || $domain == "www.amazon.de") {
        return true;
    }
    if ($domain == "amazon.es" || $domain == "www.amazon.es") {
        return true;
    }
    if ($domain == "amazon.fr" || $domain == "www.amazon.fr") {
        return true;
    }
    if ($domain == "amazon.it" || $domain == "www.amazon.it") {
        return true;
    }
    if ($domain == "amazon.co.jp" || $domain == "www.amazon.co.jp") {
        return true;
    }
    if ($domain == "amazon.cn" || $domain == "www.amazon.cn") {
        return true;
    }
    if ($domain == "amazon.com.au" || $domain == "www.amazon.com.au") {
        return true;
    }
    return false;
}

function getAmount($money)
{
    $temp = substr($money, 1);
    if (strpos($temp, '$') !== false) {
        $price_arr = explode("$", $temp);
        $money = trim($price_arr[0]);
    }
    $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
    $onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);
    $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;
    $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
    $removedThousendSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '', $stringWithCommaOrDot);
    return (float) str_replace(',', '.', $removedThousendSeparator);
}

function getSettings($user_id, $conn)
{
    $settingsResult = $conn->query("select * from setting where user_id = " . $user_id);
    if ($settingsResult->num_rows > 0) {
        $settingsRow = $settingsResult->fetch_assoc();
        return $settingsRow;
    }
    return array();
}

function insertToShopify($user_id, $shopurl, $token, $productObject, $product_id)
{
    echo "anshu 123";
    // echo "<pre>";
    // print_r($productObject);
    // echo "</pre>";

    $productArray = json_decode($productObject, true);

    $title_sh = $productArray['Title'];
    $description_sh = $productArray['description'];
    $brand_sh = $productArray['brand'];
    // $product_type_sh = $productArray['product_type'];
    if (isset($phpArray['product_type'])) {
    $product_type_sh = $phpArray['product_type'];
    } else {
    // Handle the case where the key is missing (e.g., assign a default value)
    $product_type_sh = null; // Or some other appropriate value
    }
    $bullet_points_sh = $productArray['bullet_points'];
    $price_sh = $productArray['price']; 
    // $in_stock___out_of_stock_sh = $productArray['in_stock___out_of_stock'];
    if (isset($phpArray['in_stock___out_of_stock'])) {
        $in_stock___out_of_stock_sh = $phpArray['in_stock___out_of_stock'];
    } else {
    // Handle the case where the key is missing (e.g., assign a default value)
        $in_stock___out_of_stock_sh = null; // Or some other appropriate value
    }
    $highResolutionImageUrls = $productArray['high_resolution_image_urls'];
    $imageUrlsArray = explode('|', $highResolutionImageUrls);
    // print_r($imageUrlsArray);
    // $images_sh = $productArray['high_resolution_image_urls'];
    $category_sh = $productArray['category'];

    // echo "anshu 12345";
    // echo "<pre>";
    // print_r($productArray);
    // echo "</pre>";



    global $conn;
    $settingObject = getSettings($user_id, $conn);
    $published = false;
    $tags = array();
    $vendor = "";
    $product_type = "";
    $inventory_policy = null;
    $defquantity = 1;
    $markupenabled = 0;
    $currency = '';
    $markuptype = 'FIXED';
    $markupval = 0;
    $markupround = 0;
    $location_id = "";
    if ($settingObject) {
        $tags = $settingObject['tags'];
        if (strlen($tags) > 0) {
            $tags = explode(",", $tags);
        } else {
            $tags = array();
        }
        if ($settingObject['published'] == 1) {
            $published = true;
        }
        if (strlen($settingObject['vendor']) > 0) {
            $vendor = $settingObject['vendor'];
        }
        if (strlen($settingObject['product_type']) > 0) {
            $product_type = $settingObject['product_type'];
        }
        if ($settingObject['inventory_policy'] != "NO") {
            $inventory_policy = $settingObject['inventory_policy'];
        }
        if (isset($settingObject['defquantity'])) {
            $defquantity = $settingObject['defquantity'];
        }
        if (isset($settingObject['markupenabled']) && $settingObject['markupenabled'] == 1) {
            $markupenabled = true;
        }
        if (isset($settingObject['markuptype']) && strlen($settingObject['markuptype']) > 0) {
            $markuptype = $settingObject['markuptype'];
        }
        if (isset($settingObject['markupval'])) {
            $markupval = $settingObject['markupval'];
        }
        if (isset($settingObject['markupround']) && $settingObject['markupround'] == 1) {
            $markupround = true;
        }
        if (isset($settingObject['shopifylocationid'])) {
            $location_id = $settingObject['shopifylocationid'];
        }
    }

  
 

    $title = $title_sh;
    $description = $description_sh;
    $brand = $brand_sh;
    if ($vendor != '') {
        $brand = $vendor;
    }
    $productType = $category_sh;
    if ($product_type != '') {
        $productType = $product_type;
    }
    if (isset($bullet_points_sh)) {
        $bullet_points = $bullet_points_sh;
        //$tempArr = explode("|", $bullet_points);
        //$tempArr = array_map("trim", $tempArr);
        $tempArr = $bullet_points;
        $feature1 = isset($tempArr[0]) ? $tempArr[0] : "";
        $feature2 = isset($tempArr[1]) ? $tempArr[1] : "";
        $feature3 = isset($tempArr[2]) ? $tempArr[2] : "";
        $feature4 = isset($tempArr[3]) ? $tempArr[3] : "";
        $feature5 = isset($tempArr[4]) ? $tempArr[4] : "";
        $featureStr = "";
        if (strlen($feature1) > 0) {
            $featureStr .= '<li>' . $feature1 . '</li>';
        }
        if (strlen($feature2) > 0) {
            $featureStr .= '<li>' . $feature2 . '</li>';
        }
        if (strlen($feature3) > 0) {
            $featureStr .= '<li>' . $feature3 . '</li>';
        }
        if (strlen($feature4) > 0) {
            $featureStr .= '<li>' . $feature4 . '</li>';
        }
        if (strlen($feature5) > 0) {
            $featureStr .= '<li>' . $feature5 . '</li>';
        }
        if (strlen($featureStr) > 0) {
            $featureStr = '<br /><ul>' . $featureStr . '</ul>';
        }
        $description = $description . $featureStr;
    }
    //echo "select * from product_variants where product_id = " . $product_id . " and user_id = " . $user_id . " and block = 0 and duplicate = 0 and shopifyvariantid = ''----";
    $variantResult = $conn->query("select * from product_variants where product_id = " . $product_id . " and user_id = " . $user_id . " and block = 0 and duplicate = 0 and shopifyvariantid = ''");
    $noOfVariants = $variantResult->num_rows;
    $vCount = $variantResult->num_rows;
    if ($vCount == 1) {
        $variantObject = $variantResult->fetch_assoc();
        $sku = $variantObject['sku'];
        $weight = $variantObject['weight'];
        $weight_unit = $variantObject['weight_unit'];
        $productid = $variantObject['product_id'];
        $price = $variantObject['price'];
        $saleprice = $variantObject['saleprice'];
        $currency = $variantObject['currency'];
        $variant_id = $variantObject['id'];
        if ($markupenabled == true) {
            $price = applyPriceMarkup($price, $markuptype, $markupval, $markupround);
            $saleprice = applyPriceMarkup($saleprice, $markuptype, $markupval, $markupround);
        }
        $UserResult = $conn->query("select * from users where id = " . $user_id);
        addlog("select * from users where id = " . $user_id, "INFO");
        $userCount = $UserResult->num_rows;
        addlog(json_encode($userCount), "INFO");
        if ($userCount == 1) {
            $UserObject = $UserResult->fetch_assoc();
            $shopcurrency = $UserObject['shopcurrency'];
            $autoCurrencyConversion = $UserObject['autoCurrencyConversion'];
        }

        if ($currency != '' && $autoCurrencyConversion == 1) {
            $fromStrQuery = $conn->query("select * from currencies where currency_code = '" . $currency . "'");
            addlog("select * from currencies where currency_code = '" . $currency . "'", "INFO");
            $fromStr = $fromStrQuery->num_rows;

            $toStrQuery = $conn->query("select * from currencies where currency_code = '" . $shopcurrency . "'");
            addlog("select * from currencies where currency_code = '" . $shopcurrency . "'", "INFO");
            $toStr = $fromStrQuery->num_rows;

            if (sizeof($fromStr) > 0 && sizeof($toStr) > 0) {
                $fromStrObject = $fromStrQuery->fetch_assoc();
                $toStrObject = $toStrQuery->fetch_assoc();

                $from = floatval($fromStrObject['conversionrates']);
                $to = floatval($toStrObject['conversionrates']);
                $amount = floatval($saleprice);

                $conversion_rate = $from / $to;
                $saleprice = round($amount / $conversion_rate, 2);
            }
            $currency = $shopcurrency;
        }

        $detail_page_url = $variantObject['detail_page_url'];
        $imageResult = $conn->query("select imgurl from product_images where variant_id = " . $variantObject['id']);
        $imagesArr = array();
        if ($imageResult->num_rows > 0) {
            while ($row = mysqli_fetch_array($imageResult)) {
                $imagesArr[] = $row['imgurl'];
            }
        } else {
            addlog("no data Found for Images", "ERROR");
        }
        $images = array();
        $position = 1;
        foreach ($imagesArr as $imageObject) {
            $imgUrl = $imageObject;
            if (!($strpos = stripos($imgUrl, "no-image"))) {
                $images[] = array("src" => trim($imgUrl), "position" => $position++);
            }
        }
        $productMetafields = array(array("key" => "isavailable", "value" => 1, "type" => "number_integer", "namespace" => "isaac"));
        $variantMetafields = array(array("key" => "buynowurl", "value" => $detail_page_url, "type" => "single_line_text_field", "namespace" => "isaac"));
        $data = array(
            "product" => array(
                "title" => $title,
                "body_html" => $description,
                "vendor" => $brand,
                "product_type" => $productType,
                "published" => $published,
                "tags" => $tags,
                "published_scope" => "global",
                "images" => $images,
                "metafields" => $productMetafields,
                "variants" => array(
                    array(
                        "sku" => $sku,
                        "position" => 1,
                        "price" => number_format($saleprice, 2, '.', ''),
                        "inventory_policy" => "deny",
                        "fulfillment_service" => "manual",
                        "inventory_management" => $inventory_policy,
                        "taxable" => true,
                        "weight" => $weight,
                        "weight_unit" => $weight_unit,
                        "barcode" => '',
                        "requires_shipping" => true,
                        "metafields" => $variantMetafields,
                    ),
                ),
            ),
        );
        $response = addShopifyProduct($token, $shopurl, $data);
        addLog(json_encode($response), "INFO");
        if ($response) {
            $shopifyproductid = $response["id"];
            $shopifyvariantid = $response["variants"][0]["id"];
            $shopifyinventoryid = $response["variants"][0]["inventory_item_id"];
            if ($location_id == "") {
                $location_id = getLocationId($token, $shopurl, $shopifyinventoryid);
                if (!$conn->query("UPDATE `setting` SET `shopifylocationid` = '" . $location_id . "' WHERE `user_id` = '" . $user_id . "'")) {
                    addlog("Error In Udating shopifylocationid in setting ", "ERROR");
                }
            }

            $conn->query("UPDATE product_variants SET handle = '" . mysqli_real_escape_string($conn, $response['handle']) . "', shopifylocationid = '" . mysqli_real_escape_string($conn, $location_id) . "', shopifyproductid = '" . mysqli_real_escape_string($conn, $shopifyproductid) . "', shopifyvariantid  = '" . mysqli_real_escape_string($conn, $shopifyvariantid) . "', shopifyinventoryid = '" . mysqli_real_escape_string($conn, $shopifyinventoryid) . "' WHERE product_id = " . $product_id . " AND user_id = " . $user_id);
            $conn->query("UPDATE products SET shopifyproductid = '" . mysqli_real_escape_string($conn, $shopifyproductid) . "', status = 'Imported' WHERE  product_id = " . $product_id . " AND user_id = " . $user_id);
            if ($inventory_policy == "shopify" && $location_id != "") {
                addlog("<h3>updating shopify inventory</h3><br/>", "INFO");
                addlog("quantity" . $defquantity, "INFO");
                $res = updateShopifyInventory($token, $shopurl, $shopifyinventoryid, $location_id, $defquantity);
                if (!$res) {
                    $n_shopifyinventoryid = getInventoryId($user_id, $token, $shopurl, $shopifyvariantid);
                    if ($n_shopifyinventoryid != "" && $n_shopifyinventoryid != $shopifyinventoryid) {
                        $res = updateShopifyInventory($token, $shopurl, $n_shopifyinventoryid, $location_id, $quantity);
                        if ($res) {
                            $conn->query("update product_variants set shopifyinventoryid = '" . mysqli_real_escape_string($conn, $n_shopifyinventoryid) . "' where user_id = '" . $user_id . "' and id = " . $variant_id);
                        }
                    }
                }
            } else {

            }
            return $shopifyproductid;
        } else {
            $conn->query("insert into failed_productimports(url, reason, type, user_id) values('Bulk Import', 'Something Wrong: Unable to import', 'Shopify', '" . mysqli_real_escape_string($conn, $user_id) . "')");
        }
    } else {
        addlog($vCount, "ERROR");
    }
    return "";
}

function updateShopifyInventory($token, $shopurl, $inventory_item_id, $location_id, $quantity)
{
    global $conn;
    $data = array("location_id" => $location_id, "inventory_item_id" => $inventory_item_id, "available" => $quantity);

    $url = "https://" . $shopurl . "/admin/api/" . SHOPIFY_API_VERSION . "/inventory_levels/set.json";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:' . $token, 'Content-Type: application/json; charset=utf-8'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_VERBOSE, 0);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($curl);
    curl_close($curl);
    $response_arr = explode("\n", $response);
    $climit = -1;
    foreach ($response_arr as $obj) {
        if (strpos($obj, 'X-Shopify-Shop-Api-Call-Limit') !== false) {
            $tempArr = explode(":", $obj);
            $climit = substr(trim(end($tempArr)), 0, -3);
        }
    }
    if (intval($climit) > 35) {
        sleep(5);
    }
    if ((strstr(($response_arr[0]), "200")) || (strstr(($response_arr[1]), "200")) || (strstr(($response_arr[2]), "200"))) {
        return true;
    }
    //@mail("khariwal.rohit@gmail.com", "EPI - Inventory error in import", $shopurl.'-'.json_encode($response_arr));
    return false;
}

function getInventoryId($user_id, $token, $shopurl, $shopifyvariantid)
{
    global $conn;
    $apiurl = "https://" . $shopurl . "/admin/api/" . SHOPIFY_API_VERSION . "/variants/" . $shopifyvariantid . ".json";
    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $apiurl);
    curl_setopt($session, CURLOPT_HTTPGET, 1);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:' . $token));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($session);
    if ($response) {
        $resObj = json_decode($response, true);
        if (isset($resObj['variant']) && isset($resObj['variant']['inventory_item_id'])) {
            return trim($resObj['variant']['inventory_item_id']);
        } else {
            $conn->query("insert into failed_productimports(url, reason, type, user_id) values('Bulk Import', 'Unable to get inventory id', 'Shopify', '" . mysqli_real_escape_string($conn, $user_id) . "')");
        }
    }
    sleep(1);
    return "";
}

function getLocationId($token, $shopurl, $inventory_item_id)
{
    addlog("getLocation called", "INFO");
    $apiurl = "https://" . $shopurl . "/admin/api/" . SHOPIFY_API_VERSION . "/inventory_levels.json?inventory_item_ids=" . $inventory_item_id;
    addlog("getLocation called" . $apiurl, "INFO");
    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $apiurl);
    curl_setopt($session, CURLOPT_HTTPGET, 1);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:' . $token));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($session);
    addlog($response, "INFO");
    curl_close($session);
    if ($response) {
        $resObj = json_decode($response, true);
        if (isset($resObj['inventory_levels']) && isset($resObj['inventory_levels'][0]['location_id'])) {
            addlog(json_encode($resObj), "INFO");
            return trim($resObj['inventory_levels'][0]['location_id']);
        } else {
            $conn->query("insert into failed_productimports(url, reason, type, user_id) values('Bulk Import', 'Unable to get location id', 'Shopify', '" . mysqli_real_escape_string($conn, $user_id) . "')");
        }
    }
    addlog("not proper Response for getLocationId", "ERROR");
    return false;
}

function applyPriceMarkup($price, $markuptype, $markupval, $markupround)
{
    $newprice = $price;
    if ($markuptype == "FIXED") {
        $newprice = $price + $markupval;
    } else {
        $newprice = $price + $price * $markupval / 100;
    }
    if ($markupround) {
        $newprice = round($newprice);
    }
    return $newprice;
}

function addShopifyProduct($token, $shopurl, $data)
{
    $url = "https://" . $shopurl . "/admin/api/" . SHOPIFY_API_VERSION . "/products.json";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:' . $token, 'Content-Type: application/json; charset=utf-8'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_VERBOSE, 0);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($curl);
    curl_close($curl);
    $response_arr = explode("\n", $response);

    if ((strstr(($response_arr[0]), "201")) || (strstr(($response_arr[1]), "201")) || (strstr(($response_arr[2]), "201"))) {
        $product_json = end($response_arr);
        $product_arr = json_decode($product_json, true);
        $product_arr = $product_arr["product"];
        return $product_arr;
    } else {
        // print_r($data);
        // print_r($response_arr);
        addlog("Error adding product with SKU - " . $product["product"]["variants"]["sku"] . ", Err Details: " . serialize($response_arr), "ERROR");
    }
    return null;
}

function fetchProductDataWithRetry($url)
{
    $temp = fetchProductData($url);
    if ($temp == "ERROR" || strpos($temp, "Error") || $temp == []) {
        sleep(2);
        $temp = fetchProductData($url);
        if ($temp == "ERROR" || strpos($temp, "Error")) {
            sleep(4);
            $temp = fetchProductData($url);
            if ($temp == "ERROR" || strpos($temp, "Error")) {
                // sleep(7);
                //  $failedAsin = $asin; /*getDataFromDoc(fetchProductData($url))*/;
                return $temp;
            } else {
                $data[$asin] = $temp;
            }
        } else {
            $data[$asin] = $temp;
        }
    } else {
        $data[$asin] = $temp;
    }
    return $temp;

}

function GenCode($size = 6)
{
    global $conn;
    $code = '';
    $validchars = 'abcdefghijkmnopqrstuvwxyz23456789';
    mt_srand((double) microtime() * 1000000);
    for ($i = 0; $i < $size; $i++) {
        $index = mt_rand(0, strlen($validchars));
        if (isset($validchars[$index])) {$code .= $validchars[$index];}
    }
    return $code;
}

function fetchProductData($url)
{
    sleep(1);
    global $conn;
    $randomCode = GenCode(6);
    $url = str_replace("\r", '', $url);
    $proxy_port = "22225"; //60099";lum-customer-hl_c27ea444-zone-static
    $proxy_ip = "zproxy.lum-superproxy.io"; //172.84.122.33";lum-customer-hl_c27ea444-zone-static
    $loginpassw = "lum-customer-hl_c27ea444-zone-static-session-" . $randomCode . ":0w29dqxs53i7";

    $user_agentObj = $conn->query("SELECT * FROM `user_agents` WHERE id=" . rand(0, 220));
    if ($user_agentObj->num_rows > 0) {
        $user_agents = $user_agentObj->fetch_assoc();
        $user_agent = $user_agents['ua_string'];
    } else {
        $user_agent = "Mozilla/6.0 (Macintosh; I; Intel Mac OS X 11_7_9; de-LI; rv:1.9b4) Gecko/2012010317 Firefox/10.0a4";
    }

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
    curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');
    curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //"Postman-Token: 47dd397c-06d7-461f-9873-b317e948d580",
        "cache-control: no-cache",
        "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
        "Connection: close",
        "X-Forwarded-For: " . $proxy_ip,
        "Content-Length: 0",
        "Cookie: timezone=Asia/Kolkata;",
        "Accept-Language: en-US,en;q=0.9",
        "Accept-Encoding: gzip, deflate, br",
        //"Host: www.amazon.com",
    ));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, "UTF8");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //addlog("http code is ".json_encode(curl_getinfo($ch)),"INFO");

    $response = curl_exec($ch);
    //print_r($response);
    $err = curl_error($ch);
    curl_close($ch);
    // addlog("data crawled via luminiato ".json_encode($response),"INFO");
    if ($err) {
        addlog("data crawled via luminiato erroe " . $err, "INFO");
        return "ERROR";
    } else {
        return $response;
    }
}

function addlog($message, $type)
{
    global $logfile;
    $txt = date("Y-m-d H:i:s") . " [" . $type . "]: " . $message . "\n"; 
    fwrite($logfile, $txt);
}
