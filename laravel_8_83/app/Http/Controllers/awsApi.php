<?php
function getawsdata($aws_access_id,$aws_secret_key,$country,$payload,$path1,$path2) {
	$serviceName="ProductAdvertisingAPI";
	if($country == "com"){$region="us-east-1";
	}else if($country == "ca"){$region="us-east-1";
	}else if($country == "co.uk"){$region="eu-west-1";
	}else if($country == "in"){$region="eu-west-1";
	}else if($country == "com.br"){$region="us-east-1";
	}else if($country == "com.mx"){$region="us-east-1";
	}else if($country == "de"){$region="eu-west-1";
	}else if($country == "es"){$region="eu-west-1";
	}else if($country == "fr"){$region="eu-west-1";
	}else if($country == "it"){$region="eu-west-1";
	}else if($country == "co.jp"){$region="us-west-2";
	}else{$region="eu-west-1";}
	$accessKey=$aws_access_id;
	$secretKey=$aws_secret_key;
	$host="webservices.amazon.".$country;
	$uriPath="/paapi5/".$path1;
	$awsv4 = new AwsV4 ($accessKey, $secretKey);
	$awsv4->setRegionName($region);
	$awsv4->setServiceName($serviceName);
	$awsv4->setPath ($uriPath);
	$awsv4->setPayload ($payload);
	$awsv4->setRequestMethod ("POST");
	$awsv4->addHeader ('content-encoding', 'amz-1.0');
	$awsv4->addHeader ('content-type', 'application/json; charset=utf-8');
	$awsv4->addHeader ('host', $host);
	$awsv4->addHeader ('x-amz-target', 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.'.$path2);
	$headers = $awsv4->getHeaders ();
	$headerString = "";
	foreach ( $headers as $key => $value ) {
		$headerString .= $key . ': ' . $value . "\r\n";
	}
	$params = array (
			'http' => array (
				'header' => $headerString,
				'method' => 'POST',
				'content' => $payload
			)
		);
	$stream = stream_context_create ( $params );
	
	$fp = @fopen ( 'https://'.$host.$uriPath, 'rb', false, $stream );
	
	if (! $fp) {
		throw new Exception ( "Exception Occured" );
	}
	$response = @stream_get_contents ( $fp );
	if ($response === false) {
		throw new Exception ( "Exception Occured" );
	}
	return $response;	
}
class AwsV4 {
	
		private $accessKey = null;
		private $secretKey = null;
		private $path = null;
		private $regionName = null;
		private $serviceName = null;
		private $httpMethodName = null;
		private $queryParametes = array ();
		private $awsHeaders = array ();
		private $payload = "";
	
		private $HMACAlgorithm = "AWS4-HMAC-SHA256";
		private $aws4Request = "aws4_request";
		private $strSignedHeader = null;
		private $xAmzDate = null;
		private $currentDate = null;
	
		public function __construct($accessKey, $secretKey) {
			$this->accessKey = $accessKey;
			$this->secretKey = $secretKey;
			$this->xAmzDate = $this->getTimeStamp ();
			$this->currentDate = $this->getDate ();
		}
	
		function setPath($path) {
			$this->path = $path;
		}
	
		function setServiceName($serviceName) {
			$this->serviceName = $serviceName;
		}
	
		function setRegionName($regionName) {
			$this->regionName = $regionName;
		}
	
		function setPayload($payload) {
			$this->payload = $payload;
		}
	
		function setRequestMethod($method) {
			$this->httpMethodName = $method;
		}
	
		function addHeader($headerName, $headerValue) {
			$this->awsHeaders [$headerName] = $headerValue;
		}
	
		private function prepareCanonicalRequest() {
			$canonicalURL = "";
			$canonicalURL .= $this->httpMethodName . "\n";
			$canonicalURL .= $this->path . "\n" . "\n";
			$signedHeaders = '';
			foreach ( $this->awsHeaders as $key => $value ) {
				$signedHeaders .= $key . ";";
				$canonicalURL .= $key . ":" . $value . "\n";
			}
			$canonicalURL .= "\n";
			$this->strSignedHeader = substr ( $signedHeaders, 0, - 1 );
			$canonicalURL .= $this->strSignedHeader . "\n";
			$canonicalURL .= $this->generateHex ( $this->payload );
			return $canonicalURL;
		}
	
		private function prepareStringToSign($canonicalURL) {
			$stringToSign = '';
			$stringToSign .= $this->HMACAlgorithm . "\n";
			$stringToSign .= $this->xAmzDate . "\n";
			$stringToSign .= $this->currentDate . "/" . $this->regionName . "/" . $this->serviceName . "/" . $this->aws4Request . "\n";
			$stringToSign .= $this->generateHex ( $canonicalURL );
			return $stringToSign;
		}
	
		private function calculateSignature($stringToSign) {
			$signatureKey = $this->getSignatureKey ( $this->secretKey, $this->currentDate, $this->regionName, $this->serviceName );
			$signature = hash_hmac ( "sha256", $stringToSign, $signatureKey, true );
			$strHexSignature = strtolower ( bin2hex ( $signature ) );
			return $strHexSignature;
		}
	
		public function getHeaders() {
			$this->awsHeaders ['x-amz-date'] = $this->xAmzDate;
			ksort ( $this->awsHeaders );
	
			// Step 1: CREATE A CANONICAL REQUEST
			$canonicalURL = $this->prepareCanonicalRequest ();
	
			// Step 2: CREATE THE STRING TO SIGN
			$stringToSign = $this->prepareStringToSign ( $canonicalURL );
	
			// Step 3: CALCULATE THE SIGNATURE
			$signature = $this->calculateSignature ( $stringToSign );
	
			// Step 4: CALCULATE AUTHORIZATION HEADER
			if ($signature) {
				$this->awsHeaders ['Authorization'] = $this->buildAuthorizationString ( $signature );
				return $this->awsHeaders;
			}
		}
	
		private function buildAuthorizationString($strSignature) {
			return $this->HMACAlgorithm . " " . "Credential=" . $this->accessKey . "/" . $this->getDate () . "/" . $this->regionName . "/" . $this->serviceName . "/" . $this->aws4Request . "," . "SignedHeaders=" . $this->strSignedHeader . "," . "Signature=" . $strSignature;
		}
	
		private function generateHex($data) {
			return strtolower ( bin2hex ( hash ( "sha256", $data, true ) ) );
		}
	
		private function getSignatureKey($key, $date, $regionName, $serviceName) {
			$kSecret = "AWS4" . $key;
			$kDate = hash_hmac ( "sha256", $date, $kSecret, true );
			$kRegion = hash_hmac ( "sha256", $regionName, $kDate, true );
			$kService = hash_hmac ( "sha256", $serviceName, $kRegion, true );
			$kSigning = hash_hmac ( "sha256", $this->aws4Request, $kService, true );
	
			return $kSigning;
		}
	
		private function getTimeStamp() {
			return gmdate ( "Ymd\THis\Z" );
		}
	
		private function getDate() {
			return gmdate ( "Ymd" );
		}
	}
?>