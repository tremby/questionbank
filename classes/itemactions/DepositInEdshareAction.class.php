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

if (DIEA_AVAILABLE && !defined("DIEA_EDSHARE_HOST"))
	die("DIEA_AVALIABLE is true but DIEA_EDSHARE_HOST has not been configured");

class DepositInEdshareAction extends ItemAction {
	private $ai;
	private $errors = array();

	public function name() {
		return "deposit in Edshare";
	}

	public function description() {
		return "Deposit this item as a content package in Edshare";
	}

	public function beforeLogic() {
		$this->ai = QTIAssessmentItem::fromQTIID($_REQUEST["qtiid"]);
	}

	public function getLogic() {
		$GLOBALS["title"] = "Deposit item in Edshare";
		include "htmlheader.php";
		?>
		<h2>Deposit item "<?php echo htmlspecialchars($this->ai->data("title")); ?>" in Edshare</h2>
		<?php if (!empty($this->errors)) showmessages($this->errors, "Error" . plural($this->errors), "error"); ?>
		<p>Before the item can be deposited you need to provide your <a 
		href="http://<?php echo DIEA_EDSHARE_HOST; ?>">Edshare</a> 
		username and password. This information is not stored.</p>
		<form action="<?php echo $this->actionURL($this->ai); ?>" method="post">
			<dl>
				<dt><label for="diea_username">Username</label></dt>
				<dd><input type="text" size="64" name="diea_username" id="diea_username"<?php if (isset($_POST["diea_username"])) { ?> value="<?php echo htmlspecialchars($_POST["diea_username"]); ?>"<?php } ?>></dd>

				<dt><label for="diea_password">Password</label></dt>
				<dd><input type="password" size="64" name="diea_password" id="diea_password"></dd>

				<dt></dt>
				<dd><input type="submit" name="deposit" value="Deposit"></dd>
			</dl>
		</form>
		<?php
		include "htmlfooter.php";
	}

	public function postLogic() {
		// check a username and password were given
		if (!isset($_POST["diea_username"]) || empty($_POST["diea_username"]) || !isset($_POST["diea_password"]) || empty($_POST["diea_password"])) {
			$this->errors[] = "Edshare username or password not given";
			$this->getLogic();
			return;
		}

		// get content package
		$zipcontents = $this->ai->getContentPackage();

		// build EP3 XML
		$ep3 = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?>
			<eprints>
				<eprint xmlns="http://eprints.org/ep2/data/2.0"/>
			</eprints>
		');
		$ep3->eprint->addChild("type", "share");
		$ep3->eprint->addChild("metadata_visibility", "show");
		$ep3->eprint->addChild("title", $this->ai->data("title"));
		$ep3->eprint->addChild("abstract", $this->ai->data("description"));
		$keywords = $this->ai->getKeywords();
		if (!empty($keywords)) {
			$kw = $ep3->eprint->addChild("keywords");
			foreach ($keywords as $keyword)
				$kw->addChild("item", $keyword);
		}
		$doc = $ep3->eprint->addChild("documents")->addChild("document");
		$doc->addChild("format", "application/zip");
		$doc->addChild("security", "public");
		$doc->addChild("main", $this->ai->data("title"));
		$file = $doc->addChild("files")->addChild("file");
		$file->addChild("filename", $this->ai->getTitleFS() . ".zip");
		$file->addChild("filesize", strlen($zipcontents));
		$file->addChild("data", base64_encode($zipcontents))->addAttribute("encoding", "base64");

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL				=>	"http://" . DIEA_EDSHARE_HOST . "/sword-app/deposit/inbox",
			CURLOPT_POST			=>	true,
			CURLOPT_HEADER			=>	true,
			CURLOPT_USERPWD			=>	$_POST["diea_username"] . ":" . $_POST["diea_password"],
			CURLOPT_RETURNTRANSFER	=>	true,
			CURLOPT_POSTFIELDS		=>	simplexml_indented_string($ep3),
			CURLOPT_HTTPHEADER		=>	array(
				"Content-Type: application/xml", //this may need to be text/xml
				"X-Packaging: http://eprints.org/ep2/data/2.0",
				"Expect: ",
			),
		));
		$response = curl_exec($curl);
		$responseinfo = curl_getinfo($curl);
		$headers = response_headers($response);
		$body = response_body($response);

		switch ($responseinfo["http_code"]) {
			case 401:
				$this->errors[] = "There was an authorization problem" . (isset($headers["X-Error-Code"]) ? ": " . $headers["X-Error-Code"] : "");
				$this->getLogic();
				return;
			case 201:
				// parse XML response
				$xml = simplexml_load_string($body);
				$eprintid = (integer) $xml->id;
				$treatment = null;
				foreach ($xml->children("http://purl.org/net/sword/") as $child) {
					if ($child->getName() == "treatment") {
						$treatment = (string) $child;
						break;
					}
				}

				$GLOBALS["title"] = "Item deposited in Edshare";
				include "htmlheader.php";
				?>
				<h2>Item deposited in Edshare</h2>
				<?php if (!is_null($treatment)) { ?>
					<p>Edshare returned the following information:</p>
					<p><blockquote><?php echo htmlspecialchars($treatment); ?></blockquote></p>
				<?php } ?>
				<p>You can now <a href="http://<?php echo DIEA_EDSHARE_HOST; ?>/cgi/users/home?screen=EPrint::Summary&amp;eprintid=<?php echo $eprintid; ?>">view the item in Edshare</a></p>
				<?php
				include "htmlfooter.php";

				break;
			default:
				$this->errors[] = "Unexpected response: " . $responseinfo["http_code"] . ". Response headers follow:";
				foreach ($headers as $k => $v)
					$this->errors[] = "$k: $v";
				$this->getLogic();
				return;
				break;
		}
	}

	public function available(QTIAssessmentItem $ai) {
		return DIEA_AVAILABLE && $ai->getQTI() && !count($ai->getErrors());
	}
}

?>
