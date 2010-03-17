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
	private $repoidxml = null;
	private $reponame = null;
	private $repocontent = null;
	private $repometadatapolicy = null;
	private $repodatapolicy = null;
	private $reposubmissionpolicy = null;

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
		// get login details if we don't already have them
		if (!$this->haveLogin()) {
			$this->getLogin();
			return;
		}

		// get list of collections we can deposit to
		$servicedocxml = simplexml_load_string($_SESSION["diea_servicedocxml"]);
		$collections = array();
		foreach ($servicedocxml->workspace->collection as $collection) {
			// skip if the collection doesn't accept zip files
			$acceptzip = false;
			foreach ($collection->accept as $accept) {
				if ((string) $accept == "application/zip") {
					$acceptzip = true;
					break;
				}
			}
			if (!$acceptzip)
				continue;

			// pull out collection details (the namespaces make this awkward)
			$coll = array("href" => (string) $collection["href"]);
			foreach ($collection->children("http://www.w3.org/2005/Atom") as $child) {
				if ($child->getName() == "title") {
					$coll["title"] = (string) $child;
					break;
				}
			}
			foreach ($collection->children("http://purl.org/net/sword/") as $child) {
				if ($child->getName() == "collectionPolicy")
					$coll["collectionPolicy"] = (string) $child;
				else if ($child->getName() == "treatment")
					$coll["treatment"] = (string) $child;
			}
			foreach ($collection->children("http://purl.org/dc/terms/") as $child) {
				if ($child->getName() == "abstract") {
					$coll["abstract"] = (string) $child;
					break;
				}
			}

			$collections[] = $coll;
		}

		// show the deposit form
		$GLOBALS["title"] = "Deposit item in Edshare";
		include "htmlheader.php";
		?>
		<h2>Deposit item "<?php echo htmlspecialchars($this->ai->data("title")); ?>" in Edshare</h2>

		<?php if (!empty($this->errors)) showmessages($this->errors, "Error" . plural($this->errors), "error"); ?>

		<form action="<?php echo $this->actionURL($this->ai); ?>" method="post">
			<dl>
				<dt>Collection</dt>
				<dd>
					<dl>
						<?php foreach($collections as $collection) { ?>
							<dt><label>
							<input type="radio" name="collection" value="<?php echo $collection["href"]; ?>"<?php if (isset($_POST["collection"]) && $_POST["collection"] == $collection["href"]) { ?> checked="checked"<?php } ?>>
								<?php echo htmlspecialchars($collection["title"]); ?>
							</label></dt>
							<dd>
								<?php if (isset($collection["abstract"])) { ?><div class="abstract"><?php echo htmlspecialchars($collection["abstract"]); ?></div><?php } ?>
								<?php if (isset($collection["collectionPolicy"])) { ?><div class="collectionpolicy"><?php echo htmlspecialchars($collection["collectionPolicy"]); ?></div><?php } ?>
								<?php if (isset($collection["treatment"])) { ?><div class="treatment"><?php echo htmlspecialchars($collection["treatment"]); ?></div><?php } ?>
							</dd>
						<?php } ?>
					</dl>
				</dd>

				<dt></dt>
				<dd><input type="submit" name="deposit" value="Deposit"></dd>
			</dl>
		</form>
		<?php
		echo $this->repoInfo();
		include "htmlfooter.php";
	}

	public function postLogic() {
		// get login details if we don't already have them
		if (!$this->haveLogin()) {
			$this->postLogin();
			return;
		}

		// clear errors
		$this->errors = array();

		// check input
		if (!isset($_POST["collection"])) {
			$this->errors[] = "No collection specified";
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
		$doc->addChild("main", $this->ai->getTitleFS() . ".zip");
		$file = $doc->addChild("files")->addChild("file");
		$file->addChild("filename", $this->ai->getTitleFS() . ".zip");
		$file->addChild("filesize", strlen($zipcontents));
		$file->addChild("data", base64_encode($zipcontents))->addAttribute("encoding", "base64");

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL				=>	$_POST["collection"],
			CURLOPT_POST			=>	true,
			CURLOPT_HEADER			=>	true,
			CURLOPT_USERPWD			=>	$_SESSION["diea_username"] . ":" . $_SESSION["diea_password"],
			CURLOPT_RETURNTRANSFER	=>	true,
			CURLOPT_POSTFIELDS		=>	simplexml_indented_string($ep3),
			CURLOPT_HTTPHEADER		=>	array(
				"Content-Type: application/xml",
				"X-Packaging: http://eprints.org/ep2/data/2.0",
				"Expect: ",
			),
		));
		$response = curl_exec($curl);
		$responseinfo = curl_getinfo($curl);
		$code = $responseinfo["http_code"];
		$headers = response_headers($response);
		$body = response_body($response);

		switch ($code) {
			case 401:
				unset($_SESSION["diea_username"], $_SESSION["diea_password"]);
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

				// show "deposited" message and give link to the share
				$GLOBALS["title"] = "Item deposited in Edshare";
				include "htmlheader.php";
				?>
				<h2>Item deposited in Edshare</h2>
				<?php if (!is_null($treatment)) { ?>
					<p>Edshare returned the following information:</p>
					<p><blockquote><?php echo htmlspecialchars($treatment); ?></blockquote></p>
				<?php } ?>
				<?php if (strpos($_POST["collection"], "/sword-app/deposit/archive") === false) { ?>
					<p>
						The item is not yet live in the repository. As the owner 
						you can still
						<a href="http://<?php echo DIEA_EDSHARE_HOST; ?>/cgi/users/home?screen=EPrint::Summary&amp;eprintid=<?php echo $eprintid; ?>">view it</a>
						or edit it (including setting it live) in Edshare.
					</p>
					<p>
						Once the item is live it'll be visible to the world at the following address.
						<br>
						<tt>http://<?php echo DIEA_EDSHARE_HOST; ?>/<?php echo $eprintid; ?></tt>
					</p>
				<?php } else { ?>
					<p>
						The item is now live in Edshare and you and others can 
						view it at the following address.
						<br>
						<tt><a href="http://<?php echo DIEA_EDSHARE_HOST; ?>/<?php echo $eprintid; ?>">http://<?php echo DIEA_EDSHARE_HOST; ?>/<?php echo $eprintid; ?></a></tt>
					</p>
				<?php } ?>
				<?php
				include "htmlfooter.php";

				break;
			default:
				$this->errors[] = "Unexpected response: " . $responseinfo["http_code"] . ". Response headers follow:";
				foreach ($headers as $k => $v)
					$this->errors[] = "$k: $v";
				$this->getLogic();
				return;
		}
	}

	public function available(QTIAssessmentItem $ai) {
		return DIEA_AVAILABLE && $ai->getQTI() && !count($ai->getErrors());
	}

	private function repoIDXML() {
		// get identification from Edshare host if we haven't already
		if (is_null($this->repoidxml))
			$this->repoidxml = simplexml_load_file("http://" . DIEA_EDSHARE_HOST . "/cgi/oai2?verb=Identify");

		return $this->repoidxml;
	}

	// populate properties we can get without logging in
	private function populatePublicRepoDetails() {
		if (!is_null($this->repocontent))
			return;

		foreach ($this->repoIDXML()->Identify->description as $description) {
			if (!count($description->eprints))
				continue;

			$this->reponame = (string) $this->repoIDXML()->Identify->repositoryName;
			$this->repocontent = (string) $description->eprints->content->text;
			$this->repometadatapolicy = (string) $description->eprints->metadataPolicy->text;
			$this->repodatapolicy = (string) $description->eprints->dataPolicy->text;
			$this->reposubmissionpolicy = (string) $description->eprints->submissionPolicy->text;
			return;
		}
	}

	// get those properties
	private function repoName() {
		$this->populatePublicRepoDetails();
		return $this->reponame;
	}
	private function repoContent() {
		$this->populatePublicRepoDetails();
		return $this->repocontent;
	}
	private function repoMetadataPolicy() {
		$this->populatePublicRepoDetails();
		return $this->repometadatapolicy;
	}
	private function repoDataPolicy() {
		$this->populatePublicRepoDetails();
		return $this->repodatapolicy;
	}
	private function repoSubmissionPolicy() {
		$this->populatePublicRepoDetails();
		return $this->reposubmissionpolicy;
	}

	// return an HTML list of repo info we can get without logging in
	private function repoInfo() {
		ob_start();
		?>
		<h2>Repository information</h2>
		<dl>
			<dt>Name</dt>
			<dd><?php echo htmlspecialchars($this->repoName()); ?></dd>

			<dt>URL</dt>
			<dd><a href="http://<?php echo DIEA_EDSHARE_HOST; ?>">http://<?php echo DIEA_EDSHARE_HOST; ?></a></dd>

			<dt>Description</dt>
			<dd><?php echo htmlspecialchars($this->repoContent()); ?></dd>

			<dt>Metadata policy</dt>
			<dd><?php echo htmlspecialchars($this->repoMetadataPolicy()); ?></dd>

			<dt>Data policy</dt>
			<dd><?php echo htmlspecialchars($this->repoDataPolicy()); ?></dd>

			<dt>Submission policy</dt>
			<dd><?php echo htmlspecialchars($this->repoSubmissionPolicy()); ?></dd>
		<?php
		return ob_get_clean();
	}

	// return true if we have good login details
	private function haveLogin() {
		return isset($_SESSION["diea_username"]);
	}

	// give an HTML form to collect login details
	private function getLogin() {
		$GLOBALS["title"] = "Deposit item in Edshare – login details required";
		include "htmlheader.php";
		?>
		<h2>Deposit item "<?php echo htmlspecialchars($this->ai->data("title")); ?>" in Edshare</h2>

		<?php if (!empty($this->errors)) showmessages($this->errors, "Error" . plural($this->errors), "error"); ?>

		<p>Before the item can be deposited you need to provide your username 
		and password for the repository. This information is stored only for 
		this session.</p>
		<form action="<?php echo $this->actionURL($this->ai); ?>" method="post">
			<dl>
				<dt><label for="diea_username">Username</label></dt>
				<dd><input type="text" size="64" name="diea_username" id="diea_username"<?php if (isset($_POST["diea_username"])) { ?> value="<?php echo htmlspecialchars($_POST["diea_username"]); ?>"<?php } ?>></dd>

				<dt><label for="diea_password">Password</label></dt>
				<dd><input type="password" size="64" name="diea_password" id="diea_password"></dd>

				<dt></dt>
				<dd><input type="submit" name="edsharelogin" value="Log in"></dd>
			</dl>
		</form>
		<?php
		echo $this->repoInfo();
		include "htmlfooter.php";
	}

	// handle the login details form being posted
	private function postLogin() {
		// clear errors
		$this->errors = array();

		// check a username and password were given
		if (!isset($_POST["diea_username"]) || empty($_POST["diea_username"]) || !isset($_POST["diea_password"]) || empty($_POST["diea_password"])) {
			$this->errors[] = "Edshare username or password not given";
			$this->getLogin();
			return;
		}

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL				=>	"http://" . DIEA_EDSHARE_HOST . "/sword-app/servicedocument",
			CURLOPT_POST			=>	false,
			CURLOPT_HEADER			=>	true,
			CURLOPT_USERPWD			=>	$_POST["diea_username"] . ":" . $_POST["diea_password"],
			CURLOPT_RETURNTRANSFER	=>	true,
			CURLOPT_HTTPHEADER		=>	array(
				"Expect: ",
			),
		));
		$response = curl_exec($curl);
		$responseinfo = curl_getinfo($curl);
		$code = $responseinfo["http_code"];
		$headers = response_headers($response);
		$body = response_body($response);

		switch ($code) {
			case 401:
				// bad username/password
				unset($_SESSION["diea_username"], $_SESSION["diea_password"], $_SESSION["diea_servicedocxml"]);
				$this->errors[] = "There was an authorization problem" . (isset($headers["X-Error-Code"]) ? ": " . $headers["X-Error-Code"] : "");
				$this->getLogic();
				return;
			case 200:
				// parse XML response
				$_SESSION["diea_username"] = $_POST["diea_username"];
				$_SESSION["diea_password"] = $_POST["diea_password"];
				$_SESSION["diea_servicedocxml"] = $body;

				$this->getLogic();
				return;
			default:
				// unexpected response
				$this->errors[] = "Unexpected response: " . $responseinfo["http_code"] . ". Response headers follow:";
				foreach ($headers as $k => $v)
					$this->errors[] = "$k: $v";
				$this->getLogic();
				return;
		}
	}
}

?>
