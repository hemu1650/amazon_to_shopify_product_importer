<?php
function getsource($producturl) {

require_once('src/crawling-api.php');
require_once('src/scraper-api.php');
require_once('src/leads-api.php');

$normalToken = 'A8zfXIDXwsj2o5A_1upnJg';
$javascriptToken = 'K1anx0wf_DOHRoyr9cOCTA';

$normalAPI = new ProxyCrawl\CrawlingAPI(['token' => $normalToken]);
$result = $normalAPI->get($producturl);
	if ($result->statusCode === 200) {
    	return $result->body;
  	} else {
    	return $result;
  	}
}
