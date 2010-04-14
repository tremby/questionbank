<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

if (!isset($_REQUEST["qtiid"]))
	badrequest("no QTI ID was specified");

$item = getitem($_REQUEST["qtiid"]);

if (!$item)
	badrequest("no item with the given QTI ID exists in the database");

$actionurl = "http://www.example.com";

// get a new QTIEngine session ID
$_SESSION["qtiengine_session"] = file_get_contents("http://" . QTIENGINE_HOST . ":" . QTIENGINE_PORT . QTIENGINE_PATH . "/rest/newSession");

// upload the QTI to QTIEngine
// Doing this manually rather than using curl because until PHP 5.2.7 (SVN 
// r269951 to be specific) there is a bug (http://bugs.php.net/bug.php?id=46696) 
// which breaks the feature needed to submit the uploaded file's mimetype. PHP 
// 5.2.4 is still common at the time of writing (it's in Ubuntu 8.04 LTS) so we 
// can't use curl here.

// boundary -- see http://www.w3.org/Protocols/rfc1341/7_2_Multipart.html
while (true) {
	$boundary = "----------------------------" . uniqid();
	if (strpos($item["xml"], $boundary) === false)
		break;
}

// request
$request =	"--$boundary\r\n";
$request .=	"Content-Disposition: form-data; name=\"jsession\"\r\n";
$request .=	"\r\n";
$request .=	$_SESSION["qtiengine_session"] . "\r\n";
$request .=	"--$boundary\r\n";
$request .=	"Content-Disposition: form-data; name=\"actionUrl\"\r\n";
$request .=	"\r\n";
$request .=	"$actionurl\r\n";
$request .=	"--$boundary\r\n";
$request .=	"Content-Disposition: form-data; name=\"uploadedContent\"; filename=\"qb_" . $item["identifier"] . ".xml\"\r\n";
$request .=	"Content-Type: application/xml\r\n";
$request .=	"\r\n";
$request .=	$item["xml"];
$request .=	"\r\n--$boundary--\r\n\r\n";

// headers
$reqheader = array(
	"Host"				=>	QTIENGINE_HOST,
	"Accept"			=>	"*/*",
	"Content-Length"	=>	strlen($request),
	"Content-Type"		=>	"multipart/form-data; boundary=$boundary",
	"User-Agent"		=>	PROGRAMNAME . "/" . VERSION,
);
$url = "/rest/upload";
$reqaction = "POST $url HTTP/1.1";

header("Content-Type: text/plain");

// make requests and follow location headers
$error = null;
while (true) {
	// open socket
	$sock = fsockopen(QTIENGINE_HOST, QTIENGINE_PORT, $errno, $errstr, 30);
	if (!$sock)
		servererror("Couldn't connect to QTIEngine (" . QTIENGINE_HOST . ":" . QTIENGINE_PORT . ")");

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
			$httpcode = intval(preg_replace('%^HTTP/[^\s]*\s+(\d+).*$%', '\1', $line));
		else {
			$parts = explode(":", $line, 2);
			$header[trim($parts[0])] = trim($parts[1]);
		}
	}

	$response = "";

	// get rest of response and stop if HTTP response code is not a redirection
	if ($httpcode != 301 && $httpcode != 302 || !array_key_exists("Location", $header)) {
		if (isset($header["Transfer-Encoding"]) && $header["Transfer-Encoding"] == "chunked") {
			while (!feof($sock)) {
				// get number of bytes in next chunk
				$bytes = hexdec(preg_replace('%^([0-9a-fA-F]+).*?$%', '\\1', fgets($sock)));
				if ($bytes == 0) // zero-length chunk means it's the end
					break;

				// get data until we have enough bytes
				$chunk = "";
				while (strlen($chunk) < $bytes)
					$chunk .= fgets($sock, $bytes);

				// add data to response
				$response .= $chunk;
			}
		} else
			while (!feof($sock))
				$response .= fgets($sock);
		fclose($sock);
		break;
	}

	// close the socket
	fclose($sock);

	// check its URL is valid
	$urlparts = parse_url($header["Location"]);
	if (!isset($urlparts)) {
		$error = "Hit a malformed Location header pointing to '" . $header["Location"] . "'";
		break;
	}

	// redirect
	$url = $urlparts["path"] . "?" . $urlparts["query"];
	$reqaction = "GET $url HTTP/1.1";

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
	servererror($error);

echo $response;

?>
