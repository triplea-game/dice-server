<?php
class dice {
	var $domain;
	var $dbconn = null;
	var $enc = [];

	function __construct() {
		$this->domain = self::getBaseUri();
		$this->connectDatabase();
	}

	function __destruct() {
		if (!is_null($this->dbconn)) {
			$this->dbconn->close();
		}
	}

	/**
	 * Returns the base URI of the active request.  The returned URI WILL NOT have a trailing slash.
	 */
	static function getBaseUri() {
		$path = $_SERVER['DOCUMENT_URI'];
		$pathLastSegment = "/" . basename($path);
		$pathWithoutLastSegment = substr($path, 0, -strlen($pathLastSegment));
		return $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . $pathWithoutLastSegment;
	}

	function connectDatabase() {
		if (!is_null($this->dbconn)) {
			return;
		}

		$host = getenv("MARTI_DB_HOST");
		$user = getenv("MARTI_DB_USERNAME");
		$password = getenv("MARTI_DB_PASSWORD");
		$database = getenv("MARTI_DB_NAME");
		$this->dbconn = new mysqli($host, $user, $password, $database);
		if ($this->dbconn->connect_errno > 0) {
			exit("fatal error: could not connect to database!<br>" . $this->dbconn->connect_error . "!");
		}
	}

	/**
	 * Checks if all receiving email adresses are registered
	 * @returns bool
	 */
	function getUnregisteredMails(array $emails) {
		$this->connectDatabase();
		$emails = array_unique($emails);
		$emailCount = count($emails);
		$sql = "SELECT email FROM ("
				. implode(" UNION ALL ", array_fill(0, $emailCount, "SELECT ? email"))
				. ") emails WHERE email NOT IN (SELECT registered_email FROM dice_emails)";
		$statement = $this->dbconn->prepare($sql);
		$statement->bind_param(str_repeat("s", $emailCount), ...$emails);
		$statement->execute() or exit("fatal error: data connection lost @getUnregisteredMails!");
		$statement->bind_result($missingEmail);
		$missingEmails = [];
		while ($statement->fetch()) {
			$missingEmails[] = $missingEmail;
		}
		return $missingEmails;
	}

	/**
	 * Checks if a specific email is already registered
	 * @return bool Is the email registered
	 */
	function isMailRegistered($email) {
		return empty($this->getUnregisteredMails([$email]));
	}

	/**
	 * returns the date and key
	 * if no date is specified the latest key in key.dat will be returned
	 */
	function getEncryptionKey() {
		// get current key
		if (!file_exists("key.dat")) {
			$this->setEncryptionKey();
		}
		$keyfile = fopen("key.dat", "r");

		if ($keyfile) {
			$data = fread($keyfile, 8192);
			$this->enc = unserialize($data);
			fclose($keyfile);
			return $this->enc;
		} else {
			exit("fatal error: No key file found");
		}
	}

	function setEncryptionKey() {
		$file = fopen("key.dat", "w");
		$enc['key'] = base64_encode(random_bytes(24));
		$output = serialize($enc);
		fputs($file, $output);
		fclose($file);
		chmod("key.dat", 0600);
	}

	function encrypt_data($input) {
		$this->getEncryptionKey();
		$string = serialize($input);

		$td = mcrypt_module_open('rijndael-256', '', 'cfb', '');
		$out['iv'] = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $this->enc['key'], $out['iv']);
		$encrypted_data = mcrypt_generic($td, $string);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		$out['data'] = rawurlencode(base64_encode($encrypted_data));
		$out['iv'] = rawurlencode(base64_encode($out['iv']));
		return $out;
	}

	function decrypt_data($iv, $encrypted_data) {
		$iv = base64_decode(rawurldecode($iv));
		$encrypted_data = base64_decode(rawurldecode($encrypted_data));

		$encrypt_key = $this->getEncryptionKey();

		$td = mcrypt_module_open('rijndael-256', '', 'cfb', '');
		mcrypt_generic_init($td, $encrypt_key['key'], $iv);
		$decrypted_data = mdecrypt_generic($td, $encrypted_data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return unserialize($decrypted_data);
	}

	function createdice($numdice, $numsides) {
		$dice = [];
		for ($i = 0; $i < $numdice; $i++) {
			$dice[$i] = mt_rand(1, $numsides);
		}
		return implode(",", $dice);
	}

	function sendEmail($emails, $subject, $dice, $iv, $encrypted_data) {
		$to = implode (", ", $emails);

		// send email to member
		$message = "Your dice are: $dice \n";
		$message .= "Have a nice day. \n\n";
		$message .= "----------------------------------\n";
		$message .= "This is an automatically created email of the TripleA Ladder. Please don't reply to it. \n\n";
		$message .= "Verification Info: Follow this link to check if your dice are authentic \n";
		$message .= "{$this->domain}/MARTI_verify.php?iv=$iv&enc=$encrypted_data \n\n";
		$message .= "************* \n";
		$message .= "$iv \n";
		$message .= "............. \n";
		$message .= "$encrypted_data \n";
		$message .= "************* \n\n\n";
		$message .= "----------------------------------\n";
		$message .= "This Email is not SPAM.\nYou receive this email because of your registration at {$this->domain}\n";
		$message .= "To unsubscribe from this service go to {$this->domain}/unsubscribe.php";

		$ehead = "From: MARTI<marti@tripleawarclub.org>";
		$subj = "$subject";

		$mailsend = @mail($to, $subj, $message, $ehead);

		if ($mailsend) {
			echo "<p>Dice results were sent via email!</p> <br> <a href='{$this->domain}/MARTI_verify.php?iv=$iv&enc=$encrypted_data'>click here to verify the roll</a><br>";
		} else {
			echo "<p>Email delivery failed...</p> Dice results were not sent. <br> Please try it later again.";
			exit("<p>fatal error: email delivery failed!");
		}
	}
}
?>
