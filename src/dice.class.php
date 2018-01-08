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
	function requireMailsAreRegistered(array $emails) {
		$this->connectDatabase();
		$emails = array_unique($emails);
		$emailCount = count($emails);
		$placeholder = implode(", ", array_fill(0, $emailCount, "?"));
		$sql = "SELECT registered_email FROM dice_emails WHERE registered_email IN ($placeholder)";
		$statement = $this->dbconn->prepare($sql);
		$statement->bind_param(str_repeat("s", $emailCount), ...$emails);
		$statement->execute() or exit("fatal error: data connection lost @requireMailsAreRegistered!");
		$statement->bind_result($email);
		$result = [];
		while ($statement->fetch()) {
			$result[] = $email;
		}
		echo $statement->num_rows;
		echo $emailCount;
		if ($statement->num_rows === $emailCount) {
			return;	// all emails are registered
		} else if ($statement->num_rows === 0) {
			exit("fatal error: none of the emails is registered. Please register emails at {$this->domain}/register.php !");
		}
		$missingEmails = array_diff($emails, $result);
		if (!empty($missingEmails)) {
			exit("fatal error: emails " . implode(", ", $missingEmails) . " are not registered. Please register those emails at {$this->domain}/register.php !");
		}
		exit("fatal error: unknown error with email adresses!");
	}

	/**
	 * Checks if a specific email is already registered
	 * @return bool Is the email registered
	 */
	function isMailRegistered($email) {
		$this->connectDatabase();
		$statement = $this->dbconn->prepare("SELECT COUNT(*) FROM dice_emails WHERE registered_email=?");
		$statement->bind_param("s", $email);
		$statement->execute() or exit("fatal error: data connection error {$this->dbconn->error}!");
		$statement->bind_result($email_count);
		$statement->fetch();
		return $email_count === 1;
	}

	/**
	 * returns the date and key
	 * if no date is specified the latest key in key.dat will be returned
	 */
	function getEncryptionKey($date = null) {
		// get old key
		if ($date) {
			$dir = dirname(__FILE__);
			$keyfile = fopen("$dir/keys/$date.dat", "r");
		} else {
			// get current key
			if (!file_exists("key.dat")) {
				$this->setEncryptionKey();
			}
			$keyfile = fopen("key.dat", "r");
		}

		if ($keyfile) {
			$data = fread($keyfile, 8192);

			$this->enc = unserialize($data);
			fclose($keyfile);
			return $this->enc;
		} else {
			exit("fatal error: Wrong date!");
		}
	}

	function setEncryptionKey() {
		$shouldWriteKey = true;
		if (file_exists("key.dat")) {
			$old = $this->getEncryptionKey();
			$shouldWriteKey = copy("key.dat", "./keys/" . $old['date'] . ".dat");
		}

		if ($shouldWriteKey) {
			$file = fopen("key.dat", "w");
			$enc['key'] = $this->keygen();
			$enc['date'] = $this->getDate();
			$output = serialize($enc);
			fputs($file, $output);
			fclose($file);
			chmod("key.dat", 0600);
		}
	}

	function keygen() {
		$tempstring = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		for($length = 1; $length < 24; $length++) {
			$temp = str_shuffle($tempstring);
			$char = mt_rand(0, strlen($temp));
			$pass .= $temp[$char];
		}
		return $pass;
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

	function decrypt_data($date, $iv, $encrypted_data) {
		$iv = base64_decode(rawurldecode($iv));
		$encrypted_data = base64_decode(rawurldecode($encrypted_data));

		$encrypt_key = ($date != $this->getDate())
			? $this->getEncryptionKey($date)
			: $this->getEncryptionKey();

		$td = mcrypt_module_open('rijndael-256', '', 'cfb', '');
		mcrypt_generic_init($td, $encrypt_key['key'], $iv);
		$decrypted_data = mdecrypt_generic($td, $encrypted_data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return unserialize($decrypted_data);
	}

	function checkNewKeyNeeded() {
		$now = $this->getDate();
		$current_key = $this->getEncryptionKey();
		return $now != $current_key['date'];
	}

	function createdice($numdice, $numsides) {
		$i = 0;
		while ($i < $numdice) {
			$dice[$i] = mt_rand(1,$numsides);
			$i++;
		}
		return implode(",", $dice);
	}

	function getDate() {
		return date("Y-m");
	}

	function sendEmail($emails, $subject, $dice, $iv, $encrypted_data) {
		$to = implode (", ", $emails);
		$date = $this->getDate();

		// send email to member
		$message = "Your dice are: $dice \n";
		$message .= "Have a nice day. \n\n";
		$message .= "----------------------------------\n";
		$message .= "This is an automatically created email of the TripleA Ladder. Please don't reply to it. \n\n";
		$message .= "Verification Info: Follow this link to check if your dice are authentic \n";
		$message .= "{$this->domain}/MARTI_verify.php?date=$date&iv=$iv&enc=$encrypted_data \n\n";
		$message .= "*** $date *** \n";
		$message .= "$iv \n";
		$message .= "............. \n";
		$message .= "$encrypted_data \n";
		$message .= "************* \n\n\n";
		$message .= "----------------------------------\n";
		$message .= "This Email is not SPAM.\nYou receive this email because of your registration at {$this->domain}\n";
		$message .= "To unsubscribe from this service go to {$this->domain}/unsubscribe.php";

		$ehead= "From: MARTI<marti@tripleawarclub.org>";
		$subj = "$subject";

		$mailsend= @mail($to,$subj,$message,$ehead);

		if ($mailsend) {
			echo "<p>Dice results were sent via email!</p> <br> <a href='{$this->domain}/MARTI_verify.php?date=$date&iv=$iv&enc=$encrypted_data'>click here to verify the roll</a><br>";
		} else {
			echo "<p>Email delivery failed...</p> Dice results were not sent. <br> Please try it later again.";
			exit("<p>fatal error: email delivery failed!");
		}
	}
}
?>
