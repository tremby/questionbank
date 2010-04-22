<?php

/*
 * Bart's HTTP Request body multipart class
 * 0.1
 * bart@tremby.net
 *
 * So far this doesn't escape anything for the headers properly
 */

class HttpRequestBodyMultipart {
	private $parts = array();
	private $boundary = null;

	public function __construct() {
	}

	public function addpart() {
		$args = func_get_args();
		switch (count($args)) {
			case 1:
				if (get_class($args[0]) != "HttpRequestBodyPart")
					throw new Exception("Single argument must be an HttpRequestBodyPart object");
				$this->parts[] = $part;
				break;
			case 2:
				$this->parts[] = new HttpRequestBodyPart($args[0], $args[1]);
				break;
			case 3:
				$this->parts[] = new HttpRequestBodyPart($args[0], $args[1], $args[2]);
				break;
			case 4:
				$this->parts[] = new HttpRequestBodyPart($args[0], $args[1], $args[2], $args[3]);
				break;
			default:
				throw new Exception("Expected between one and four arguments");
		}
		$this->boundary = null;
		return $this;
	}

	// this takes an array like $_POST -- key->value, key->array(val, val), etc. 
	// no mimetypes.
	public function addfromarray($parts) {
		foreach ($parts as $k => $v) {
			if (is_array($v))
				foreach ($v as $vv)
					$this->addpart($k, $vv);
			else
				$this->addpart($k, $v);
		}
	}

	// see http://www.w3.org/Protocols/rfc1341/7_2_Multipart.html
	public function boundary() {
		if (isset($this->boundary))
			return $this->boundary;

		$partsstring = "";
		foreach ($this->parts as $part)
			$partsstring .= $part->requeststring();

		while (true) {
			if (strpos($partsstring, $boundary = uniqid()) === false) {
				$this->boundary = $boundary;
				return $boundary;
			}
		}
	}

	public function requeststring() {
		$out = "";
		foreach ($this->parts as $part)
			$out .= $part->requeststring($this->boundary());
		$out .= "--" . $this->boundary() . "--\r\n\r\n";

		return $out;
	}
}
