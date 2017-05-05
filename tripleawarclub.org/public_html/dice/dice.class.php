<?php
class dice {
	var $domain;
	var $dbconn;
	var $db = null;
	var $enc = [];

	// constructor
    function __construct() {
		$this->domain = self::getBaseUri();
		$this->connectDatabase();
    }

	// destructor
	function __destruct() {
		if(! is_null($this->db)){
			$this->disconnectDatabase();
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

	////////////////////////////////
	//        database            //
	////////////////////////////////
    function connectDatabase() {
    	if(! is_null($this->db)){
			return;
		}

    	$host = getenv("MARTI_DB_HOST");
    	$user = getenv("MARTI_DB_USERNAME");
    	$password = getenv("MARTI_DB_PASSWORD");
    	$database = getenv("MARTI_DB_NAME");
    	$this->dbconn = new mysqli($host, $user, $password, $database);
    	if ($this->dbconn->connect_errno > 0) {
    		exit("fatal error: could not connect to database!<br>" . mysqli_connect_error() . "!");
    	}
    	$this->db = mysqli_select_db($this->dbconn, $database);
    }

	function updateStats($numdice) {
		$this->connectDatabase();

		$sql = "UPDATE stats SET requests=requests+1, dice_rolled=dice_rolled+$numdice";
		$result = $this->dbconn->query($sql) or exit("fatal error: data connection lost @updateStats!");

	}

	function getStats() {
		$this->connectDatabase();

		$sql = "SELECT * FROM stats";
		$result = $this->dbconn->query($sql) or exit("fatal error: data connection lost @getStats!");
		$stats = mysqli_fetch_array($result);
		return $stats;
	}

	/**
	 * Checks if all receiving email adresses are registered
	 * @returns bool
	 */
	function checkIfMailsAreRegistered(array $emails) {
		$this->connectDatabase();
		$emails_string = "'" . implode("', '", $emails) . "'";
		$sql = "SELECT registered_email FROM dice_emails WHERE registered_email IN ($emails_string)";
		$result = $this->dbconn->query($sql) or exit("fatal error: data connection lost @checkIfMailsAreRegistered!");
		$registered_mails = mysqli_fetch_array($result);
		$num_emails = $result->num_rows;
		if($num_emails == count($emails)){
			return true;	// all emails are registered
		}
		if($registered_mails == false){
			throw new exception("fatal error: none of the emails is registered. Please register emails at ".$this->domain."/register.php !");
		}

		foreach($emails as $email) {
			if(! in_array($email, $registered_mails)){
				throw new exception("fatal error: email $email is not registered. Please register email at ".$this->domain."/register.php !");
			}
		}
		throw new exception("fatal error: unknown error with email adresses!");
	}

	/**
	 * Checks if a specific email is already registered
	 * @return bool Is the email registered
	 */
	function checkIfMailIsRegistered($email) {
		$this->connectDatabase();

		$sql = "SELECT registered_email FROM dice_emails WHERE registered_email = '$email'";
		$result = $this->dbconn->query($sql) or exit("fatal error: data connection error " . $this->dbconn->error . "!");
		$num_emails = $result->num_rows;

		return ($num_emails == 1);
	}

	/**
	 * runs any SQL query on the database
	 * @param string $sql
	 * @return mixed mysqlressource
	 */
	function runQuery($sql) {
		$this->connectDatabase();

		$result = $this->dbconn->query($sql) or exit("fatal error: " . $this->dbconn->error . "!");
		return $result;
	}

	function runStatement($sth){
		$this->connectDatabase();
		$result = $sth->execute();
		return $result;
	}
    function disconnectDatabase() {
    	mysqli_close($this->dbconn);
    }

	////////////////////////////////
	//        Encryption          //
	////////////////////////////////
	/**
	 * returns the date and key
	 * if no date is specified the latest key in key.dat will be returned
	 */
	function getEncryptionKey($date = null) {
		// get old key
		if($date) {
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

		if ($date != $this->getDate()) {
			$encrypt_key = $this->getEncryptionKey($date);
		} else {
			$encrypt_key = $this->getEncryptionKey();
		}

		$td = mcrypt_module_open('rijndael-256', '', 'cfb', '');
    	mcrypt_generic_init($td, $encrypt_key['key'], $iv);
    	$decrypted_data = mdecrypt_generic($td, $encrypted_data);
    	mcrypt_generic_deinit($td);
    	mcrypt_module_close($td);

		$output = unserialize($decrypted_data);

		return $output;
	}

	function checkNewKeyNeeded() {
		$now = $this->getDate();
		$current_key = $this->getEncryptionKey();
		if ($now != $current_key['date']) {
			return true;
		} else {
			return false;
		}
	}

	////////////////////////////////
	//       dice and mail        //
	////////////////////////////////
	function createdice($numdice, $numsides) {
		$i = 0;
		while ($i <= $numdice-1) {
			$dice[$i] = mt_rand(1,$numsides);
			$i++;
		}
		$dicestring = implode(",", $dice);

		return $dicestring;
	}

	function getDate() {
		return date("Y-m");
	}

	function sendEmail($emails, $subject, $dice, $iv, $encrypted_data) {
		$to  = implode (", ", $emails);
		$date = $this->getDate();

		// send email to member
		$message = "Your dice are: $dice \n";
		$message .= "Have a nice day. \n\n";
		$message .= "----------------------------------\n";
		$message .= "This is an automatically created email of the TripleA Ladder. Please don't reply to it. \n\n";
		$message .= "Verification Info: Follow this link to check if your dice are authentic \n";
		$message .= $this->domain."/MARTI_verify.php?date=$date&iv=$iv&enc=$encrypted_data \n\n";
		$message .= "*** $date *** \n";
		$message .= "$iv \n";
		$message .= "............. \n";
		$message .= "$encrypted_data \n";
		$message .= "************* \n\n\n";
 		$message .= "----------------------------------\n";
 		$message .= "This Email is not SPAM.\nYou receive this email because of your registration at ".$this->domain."\n";
		$message .= "To unsubscribe from this service go to ".$this->domain."/unsubscribe.php";

		$ehead= "From: MARTI<marti@tripleawarclub.org>";
		$subj = "$subject";

		$mailsend= @mail($to,$subj,$message,$ehead);
		
		if ($mailsend) {
				echo("<p>Dice results were sent via email!</p> <br> <a href='".$this->domain."/MARTI_verify.php?date=$date&iv=$iv&enc=$encrypted_data'>click here to verify the roll</a><br>");
		} else {
		   echo("<p>Email delivery failed...</p> Dice results were not sent. <br> Please try it later again.");
		   exit("<p>fatal error: email delivery failed!");
		}
	}
}
?>
