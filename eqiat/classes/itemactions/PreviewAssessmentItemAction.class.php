<?php

/*
 * Eqiat
 * Easy QTI Item Authoring Tool
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

class PreviewAssessmentItemAction extends ItemAction {
	public function name() {
		return "preview";
	}

	public function description() {
		return "Preview the assessment item in QTIEngine";
	}

	public function getLogic() {
		$ai = QTIAssessmentItem::fromQTIID($_REQUEST["qtiid"]);

		// upload the QTI to QTIEngine
		// Doing this manually rather than using curl because until some PHP 
		// after 5.2.6 but before 5.2.10 there is a bug which breaks the 
		// feature needed to submit the uploaded file's mimetype. PHP <5.2.10 
		// is still common at the time of writing so we can't use curl.

		// boundary -- see http://www.w3.org/Protocols/rfc1341/7_2_Multipart.html
		while (true) {
			$boundary = "----------------------------" . uniqid();
			if (strpos($ai->getQTIIndentedString(), $boundary) === false)
				break;
		}

		// request
		$request =	"--$boundary\r\n";
		$request .=	"Content-Disposition: form-data; name=\"uploadedContent\"; filename=\"{$ai->getTitleFS()}.xml\"\r\n";
		$request .=	"Content-Type: application/xml\r\n";
		$request .=	"\r\n";
		$request .=	$ai->getQTIIndentedString();
		$request .=	"\r\n--$boundary--\r\n\r\n";

		// headers
		$reqheader = array(
			"Host"				=>	QTIENGINE_HOST,
			"Accept"			=>	"*/*",
			"Content-Length"	=>	strlen($request),
			"Content-Type"		=>	"multipart/form-data; boundary=$boundary",
		);
		$url = "/application/upload";
		$reqaction =	"POST $url HTTP/1.1";

		// make requests until we're redirected to the preview page
		$error = null;
		while (true) {
			// open socket
			$sock = fsockopen(QTIENGINE_HOST, 80, $errno, $errstr, 30);
			if (!$sock)
				servererror("Couldn't connect to QTIEngine (" . QTIENGINE_HOST . ")");

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
				$error = "Didn't get a redirection to the QTIEngine preview page. Last page was $url";
				break;
			}

			// check its URL is valid
			$urlparts = parse_url($header["Location"]);
			if (!isset($urlparts)) {
				$error = "Hit a malformed Location header pointing to '" . $header["Location"] . "'";
				break;
			}

			// stop if we've got to the preview page
			if (preg_match('%^/item/play/0;%', $urlparts["path"]))
				break;

			// redirect
			$url = $urlparts["path"];
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

		redirect($header["Location"]);
	}

	public function available(QTIAssessmentItem $ai) {
		return $ai->getQTI() && !count($ai->getErrors());
	}
}

?>
