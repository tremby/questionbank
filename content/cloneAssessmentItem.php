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

if (!isset($_REQUEST["qtiid"])) badrequest("No QTI ID specified");
if (!isset($_SESSION["items"][$_REQUEST["qtiid"]])) badrequest("No QTI found in session data for specified QTI ID");

// clone the item
$ai = clone $_SESSION["items"][$_REQUEST["qtiid"]];

// call its constructor to updated the modified time and set new identifiers
$ai->__construct();

// put it in session data
$_SESSION["items"][$ai->getQTIID()] = $ai;

// take the user to the main menu with the cloned item highlighted
redirect(SITEROOT_WEB . "#item_" . $ai->getQTIID());

?>
