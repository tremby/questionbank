<?php

/*
 * Bart's HTTP Request body multipart class
 * 0.1
 * bart@tremby.net
 *
 * So far this doesn't escape anything for the headers properly
 */

class HttpRequestBodyPart {
	private $name;
	private $data;
	private $mimetype = null;
	private $filename = null;

	public function __construct($name, $data, $mimetype = null, $filename = null) {
		$this->name($name);
		$this->data($data);
		if (isset($mimetype))
			$this->mimetype($mimetype);
		if (isset($filename))
			$this->filename($filename);
	}

	public function name() {
		$args = func_get_args();
		if (isset($args[0])) {
			if (!is_string($args[0]))
				throw new Exception("Expected a string");
			$this->name = $args[0];
			return $this;
		}
		return $this->name;
	}

	public function data() {
		$args = func_get_args();
		if (isset($args[0])) {
			if (!is_string($args[0]))
				throw new Exception("Expected a string");
			$this->data = $args[0];
			return $this;
		}
		return $this->data;
	}

	public function mimetype() {
		$args = func_get_args();
		if (isset($args[0])) {
			if (!is_string($args[0]))
				throw new Exception("Expected a string");
			$this->mimetype = $args[0];
			return $this;
		}
		return $this->mimetype;
	}

	public function filename() {
		$args = func_get_args();
		if (isset($args[0])) {
			if (!is_string($args[0]))
				throw new Exception("Expected a string");
			$this->filename = $args[0];
			return $this;
		}
		return $this->filename;
	}

	// see http://www.w3.org/Protocols/rfc1341/7_2_Multipart.html
	public function requeststring($boundary = "") {
		$out = "--$boundary\r\n";
		$out .= 'Content-Disposition: form-data; name="' . $this->name . '"';
		if (isset($this->filename))
			$out .= '; filename="' . $this->filename . '"';
		$out .= "\r\n";
		if (isset($this->mimetype) && $this->mimetype != "text/plain")
			$out .= 'Content-Type: ' . $this->mimetype . "\r\n";
		$out .= "\r\n";
		$out .= $this->data . "\r\n";
		return $out;
	}
}
