<?php
/*
	bart's URI class
	0.4
	bart@tremby.net
*/
class Uri {
	private $path;
	private $vars = array();

	/**	constructor
	*	args are (URI, keepvars) or just (keepvars)
	*	if no URI is passed, the current page's URI is used.
	*/
	public function __construct($arg1 = null, $arg2 = null) {
		if (is_string($arg1))
			$this->seturi($arg1, $arg2);
		else {
			//use current
			if (is_bool($arg2))
				$this->setcurrent($arg2);
			else
				$this->setcurrent($arg1);
		}
		return $this;
	}

	/**	construct
	*	static constructor
	*/
	public static function construct($arg1 = null, $arg2 = null) {
		return new self($arg1, $arg2);
	}

	/**	setcurrent
	*	set object to the current page's URI
	*	keeps non-rewritten URIs
	*	will keep the current page's vars unless the argument is set to false
	*/
	public function setcurrent($keepvars = true) {
		return $this->seturi($_SERVER["REQUEST_URI"], $keepvars);
	}

	/**	seturi
	*	set the path and vars, given a string
	*	will keep the vars unless the second argument is set to false
	*/
	public function seturi($uri, $keepvars = true) {
		$this->path = self::pathof($uri);

		if ($keepvars)
			$this->setvars(self::queryof($uri));
		else
			$this->removevars();
		return $this;
	}

	/**	setvars
	*	replace the vars with given ones
	*	can be passed a string of variables, an array of name-value pairs or a
	*	name and value as separate arguments
	*/
	public function setvars() {
		switch (func_num_args()) {
			case 0:
				$this->removevars();
				break;
			case 1:
				$vars = func_get_arg(0);
				if (is_array($vars))
					$this->vars = $vars;
				else
					$this->vars = self::varsof($vars);
				break;
			case 2:
				$this->vars = array(func_get_arg(0) => func_get_arg(1));
				break;
			default:
				throw new Exception("setvars expects zero, one or two arguments");
				break;
		} 
		return $this;
	}

	/**	removevars
	*	given no arguments remove all vars
	*	else removes the vars given as arguments
	*/
	public function removevars() {
		$args = func_get_args();
		if (count($args) == 0)
			$this->vars = array();
		else foreach ($args as $arg)
			if (isset($this->vars[$arg]))
				unset($this->vars[$arg]);
		return $this;
	}

	/**	addvars
	*	add given vars to the current list
	*	can be passed a string of variables, an array of name-value pairs or a
	*	name and value as separate arguments.
	*	if a variable with the same name already exists, its value will be
	*	replaced.
	*/
	public function addvars() {
		switch (func_num_args()) {
			case 0:
				break;
			case 1:
				$vars = func_get_arg(0);
				if (is_array($vars))
					$this->vars = array_merge($this->vars, $vars);
				else
					$this->vars = array_merge($this->vars, self::varsof($vars));
				break;
			case 2:
				$this->vars[func_get_arg(0)] = func_get_arg(1);
				break;
			default:
				throw new Exception("addvars expects zero, one or two arguments");
				break;
		}
		return $this;
	}

	/**	getvars
	*	return the array of variables
	*/
	public function getvars() {
		return $this->vars;
	}

	/**	v
	*	given two arguments, set a variable
	*	given one argument, retrieve a variable
	*/
	public function v() {
		$args = func_get_args();
		switch (count($args)) {
			case 1:
				return $this->vars[$args[0]];
			case 2:
				return $this->addvars($args[0], $args[1]);
			default:
				throw new Exception("var expects one or two arguments");
		}
	}

	/**	getvarstring
	*	return the variables as a urlencoded string
	*	if passed true, ampersands are escaped (for HTML)
	*/
	public function getvarstring($forhtml = false) {
		$vars = array();
		foreach ($this->getvars() as $name => $value) {
			if (is_array($value)) {
				foreach ($value as $varletname => $varletvalue) {
					if (is_int($varletname))
						$vars[] = urlencode($name) . "[]=" . urlencode($varletvalue);
					else
						$vars[] = urlencode($name) . "[" . urlencode($varletname) . "]=" . urlencode($varletvalue);
				}
			} else
				$vars[] = urlencode($name) . "=" . urlencode($value);
		}
		$glue = $forhtml ? "&amp;" : "&";
		return implode($glue, $vars);
	}

	/**	getpath
	*	return the path
	*/
	public function getpath() {
		return $this->path;
	}

	/**	geturi
	*	return the full url (with query string)
	*	if passed true, ampersands are escaped (for HTML)
	*/
	public function geturi($forhtml = false) {
		if (count($this->getvars()) == 0)
			return $this->getpath();
		return $this->getpath() . "?" . $this->getvarstring($forhtml);
	}

	//static helpers------------------------------------------------------------
	/**	pathof
	*	return the path part of a given URI
	*/
	public static function pathof($url) {
		$queryoffset = strpos($url, "?");
		if ($queryoffset === false)
			return $url;
		return substr($url, 0, $queryoffset);
	}

	/**	queryof
	*	return the query string of a given URI
	*/
	public static function queryof($url) {
		$queryoffset = strpos($url, "?");
		if ($queryoffset === false)
			return "";
		return substr($url, $queryoffset + 1);
	}

	/**	varsof
	*	return an array of name-value pairs for a given query string
	*/
	public static function varsof($querystring) {
		if (strlen($querystring) == 0)
			return array();

		$pairs = explode("&", $querystring);
		$args = array();
		foreach ($pairs as $pair) {
			list($name, $value) = explode("=", $pair);
			if (isset($value))
				$value = urldecode($value);
			else
				$value = "";

			$matches = array();
			if (preg_match('%^(.*?)\[(.*)\]$%', $name, $matches)) { //array
				$name = urldecode($matches[1]);
				$index = $matches[2];

				if (!isset($args[$name]) || !is_array($args[$name]))
					$args[$name] = array();

				if ($index === "")
					$args[$name][] = $value; //indexed
				else
					$args[$name][urldecode($index)] = $value; //associative
			} else
				$args[$name] = $value; //normal var
		}
		return $args;
	}
}
