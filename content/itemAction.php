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

if (!isset($_REQUEST["action"]) || empty($_REQUEST["action"]))
	badrequest("no action specified");

$classname = ucfirst($_REQUEST["action"]) . "Action";
if (!@class_exists($classname) || !is_subclass_of($classname, "ItemAction"))
	badrequest("Item action doesn't exist or not implemented");

$action = new $classname;

if (!isset($_REQUEST["qtiid"]))
	badrequest("No QTI ID specified");

if (!isset($_SESSION["items"][$_REQUEST["qtiid"]]))
	badrequest("No QTI found in session data for specified QTI ID");

if (!$action->available($_SESSION["items"][$_REQUEST["qtiid"]]))
	badrequest(ucfirst($action->name()) . " action is not currently available for the specified QTI item");

$GLOBALS["title"] = $action->description();

$action->beforeLogic();
if (isset($_POST) && !empty($_POST))
	$action->postLogic();
else
	$action->getLogic();
$action->afterLogic();

?>
