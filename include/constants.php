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

// version
define("VERSION", "0.1-git");

// filesystem path to the eqiat root directory -- one level above this file, 
// ending in a trailing slash
define("SITEROOT_LOCAL", dirname(dirname(__FILE__)) . "/");

// query path to the eqiat root directory ending in a trailing slash -- makes an 
// absolute URL to the main page
define("SITEROOT_WEB", "/" . substr(SITEROOT_LOCAL, strlen($_SERVER["DOCUMENT_ROOT"])));

// TODO: Ensure the above two give the expected results on different server 
// types (Windows? Lighttpd?). Expected values:
// SITEROOT_LOCAL (should this have backslashes if Windows?)
// 	/var/www/eqiat/ (or /var/www/ if not running in a subdirectory)
// SITEROOT_WEB
// 	/eqiat/ (or / if not running in a subdirectory)

// namespaces
define("NS_IMSQTI", "http://www.imsglobal.org/xsd/imsqti_v2p1");
define("NS_IMSMD", "http://www.imsglobal.org/xsd/imsmd_v1p2");

// host of QTIEngine
define("QTIENGINE_HOST", "qtiengine.qtitools.org");

// site title
define("SITE_TITLE", "Eqiat");

?>
