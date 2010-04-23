<?php

/*
 * Question Bank
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

// program
define("PROGRAMNAME", "Question Bank");
define("VERSION", "0.1~git");

// filesystem path to the questionbank root directory -- one level above this 
// file, ending in a trailing slash
define("SITEROOT_LOCAL", dirname(dirname(__FILE__)) . "/");

// add the trailing slash to document_root if it doesn't already have it
$document_root = $_SERVER["DOCUMENT_ROOT"];
if ($document_root[strlen($document_root) - 1] != "/")
	$document_root .= "/";

// query path to the eqiat root directory ending in a trailing slash -- makes an 
// absolute URL to the main page
define("SITEROOT_WEB", "/" . substr(SITEROOT_LOCAL, strlen($document_root)));

// TODO: Ensure the above two give the expected results on different server 
// types. Expected values:
// SITEROOT_LOCAL (should this have backslashes if Windows?)
// 	/var/www/eqiat/ (or /var/www/ if not running in a subdirectory)
// SITEROOT_WEB
// 	/eqiat/ (or / if not running in a subdirectory)

// namespaces
define("NS_IMSQTI", "http://www.imsglobal.org/xsd/imsqti_v2p1");
define("NS_IMSMD", "http://www.imsglobal.org/xsd/imsmd_v1p2");

// this needs to be the same as Eqiat's session variable name prefix
define("SESSION_PREFIX", "qb_eqiat_");

// configuration----------------------------------------------------------------

// QTIEngine host -- these should fit together like this to form a working URL:
// "http://" . QTIENGINE_HOST . ":" . QTIENGINE_PORT . QTIENGINE_PATH
define("QTIENGINE_HOST", "qtiengine.qtitools.org");
define("QTIENGINE_PORT", 80);
define("QTIENGINE_PATH", "/");

// site title
define("SITE_TITLE", PROGRAMNAME);

?>
