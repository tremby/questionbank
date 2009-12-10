<?php

if (!isset($_SESSION["qti"])) die("No QTI in session data");

// upload the QTI to QTIEngine
// Doing this manually rather than using curl because until some PHP after 5.2.6 
// but before 5.2.10 there is a bug which breaks the feature needed to submit 
// the uploaded file's mimetype. PHP <5.2.10 is still common at the time of 
// writing so we can't use curl.

// boundary
$boundary = "----------------------------b3539e0ac209";

// request
$request =	"--$boundary\r\n";
$request .=	"Content-Disposition: form-data; name=\"uploadedContent\"; filename=\"qti.xml\"\r\n";
$request .=	"Content-Type: application/xml\r\n";
$request .=	"\r\n";
$request .= $_SESSION["qti"];
$request .= "\r\n--$boundary--\r\n\r\n";

// headers
$reqheader = array(
	"User-Agent"		=>	"curl/7.18.2 (i486-pc-linux-gnu) libcurl/7.18.2 OpenSSL/0.9.8g zlib/1.2.3.3 libidn/1.10",
	"Host"				=>	QTIENGINE_HOST,
	"Accept"			=>	"*/*",
	"Content-Length"	=>	strlen($request),
	"Content-Type"		=>	"multipart/form-data; boundary=$boundary",
);
$reqaction =	"POST /application/upload HTTP/1.1";

// make requests until we're redirected to the preview page
$error = null;
while (true) {
	// open socket
	$sock = fsockopen(QTIENGINE_HOST, 80, $errno, $errstr, 30);
	if (!$sock)
		die("Couldn't connect to QTIEngine (" . QTIENGINE_HOST . ")");

	// send data
	$reqheaderstrings = array();
	foreach ($reqheader as $key => $value)
		$reqheaderstrings[] = "$key: $value";
	fputs($sock, $reqaction . "\r\n" . implode("\r\n", $reqheaderstrings) . "\r\n\r\n" . $request);
	fflush($sock);

	// receive headers
	$header = array();
	$httpcode = null;
	while (!feof($sock) && ($line = fgets($sock)) != "\r\n") {
		if (is_null($httpcode) && preg_match('%^HTTP/[^\s]*\s+\d+%', $line))
			$httpcode = preg_replace('%^HTTP/[^\s]*\s+(\d+).*$%', '\1', $line);
		else {
			$parts = explode(":", $line, 2);
			$header[trim($parts[0])] = trim($parts[1]);
		}
	}

	// close the socket
	fclose($sock);

	// check HTTP response code is a redirection
	if ($httpcode != 301 && $httpcode != 302 || !array_key_exists("Location", $header)) {
		$error = "Didn't get a redirection to the QTIEngine preview page. Last page was " . curl_getinfo($curl, CURLINFO_EFFECTIVE_URL) . ", content:$data";
		break;
	}

	// check its URL is valid
	$urlparts = parse_url($header["Location"]);
	if (!isset($urlparts)) {
		$error = "Hit a malformed Location header pointing to '$url'";
		break;
	}

	// stop if we've got to the preview page
	if (preg_match('%^/item/play/0;%', $urlparts["path"]))
		break;

	// redirect
	$reqaction = "GET " . $urlparts["path"] . " HTTP/1.1";

	// delete POST related headers
	if (isset($reqheader["Content-Length"])) {
		unset($reqheader["Content-Length"]);
		unset($reqheader["Content-Type"]);
	}

	// clear the request data
	$request = "";

	// loop...
}
if (!is_null($error))
	die($error);

redirect($header["Location"]);

?>
