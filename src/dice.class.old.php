<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');


class dice {
	var $domain = "http://dice.tripleawarclub.org";

	var $database = "REDACTED";
	var $host = "REDACTED";
	var $user = "REDACTED";
	var $password = "REDACTED";


	var $dbconn;
	var $db = null;
	var $enc = array();

	// constructor
    function __construct() {

    }

	// destructor
	function __destruct() {
		if(! is_null($this->db))
			$this->disconnectDatabase();
	}

////////////////////////////////
//        database            //
////////////////////////////////
    function connectDatabase() {
    	if(! is_null($this->db))
    		return;


    	$this->dbconn = mysql_connect($this->host, $this->user, $this->password);
    	if (!$this->dbconn) {
    		exit("fatal error: could not connect to database!<br>" . mysql_error() . "!");
    	}
    	$this->db = mysql_select_db($this->database, $this->dbconn);
    }

	function updateStats($numdice) {
		$this->connectDatabase();

		$sql = "UPDATE stats SET requests=requests+1, dice_rolled=dice_rolled+$numdice";
		$result = mysql_query($sql) or exit("fatal error: data connection lost @updateStats!");

	}

	function getStats() {
		$this->connectDatabase();

		$sql = "SELECT * FROM stats";
		$result = mysql_query($sql) or exit("fatal error: data connection lost @getStats!");
		$stats = mysql_fetch_array($result);


	//	print_r($stats);

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
		$result = mysql_query($sql) or exit("fatal error: data connection lost @checkIfMailsAreRegistered!");
		$registered_mails = mysql_fetch_array($result);
		$num_emails = mysql_num_rows($result);
		if($num_emails == count($emails))
			return true;	// all emails are registered

		if($registered_mails == false)
			throw new exception("fatal error: none of the emails is registered. Please register emails at ".$this->domain."/register.php !");

		foreach($emails as $email) {
			if(! in_array($email, $registered_mails))
				throw new exception("fatal error: email $email is not registered. Please register email at ".$this->domain."/register.php !");
		}
		throw new exception("fatal error: unknown error with email adresses!");
		return false;
	}

	/**
	 * Checks if a specific email is already registered
	 * @return bool Is the email registered
	 */
	function checkIfMailIsRegistered($email) {
		$this->connectDatabase();

		$sql = "SELECT registered_email FROM dice_emails WHERE registered_email = '$email'";
		$result = mysql_query($sql) or exit("fatal error: data connection error " . mysql_error() . "!");
		$num_emails = mysql_num_rows($result);

		if($num_emails == 1)
			return true;

		return false;
	}

	/**
	 * runs any SQL query on the database
	 * @param string $sql
	 * @return mixed mysqlressource
	 */
	function runQuery($sql) {
		$this->connectDatabase();

		$result = mysql_query($sql) or exit("fatal error: " . mysql_error() . "!");
		return $result;
	}

    function disconnectDatabase() {
    	mysql_close($this->dbconn);
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
		}
		// get current key
		else {
			$keyfile = fopen("key.dat", "r");
		}


		if ($keyfile) {
			$data = fread($keyfile, 8192);

			$this->enc = unserialize($data);
			fclose($keyfile);
		//	echo "<br>unserialized data:" . $this->enc . "<br>";
		//	print_r($this->enc);

			return $this->enc;
		}
		else {
			exit("fatal error: Wrong date!");
		}
	}

	function setEncryptionKey() {
		$old = $this->getEncryptionKey();
		$enc['key'] = $this->keygen();
		$enc['date'] = $this->getDate();


		if(copy("key.dat","./keys/" . $old['date'] . ".dat")) {
			$file = fopen("key.dat", "w");
			$output = serialize($enc);
			fputs($file, $output);
			fclose($file);
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
//		echo "<br>encrypted data:". $out['data'] . ", iv:" . $out['iv'] ."<br>";

		return $out;
	}

	function decrypt_data($date, $iv, $encrypted_data) {
		$iv = base64_decode(rawurldecode($iv));
		$encrypted_data = base64_decode(rawurldecode($encrypted_data));

		if ($date != $this->getDate()) {
			$encrypt_key = $this->getEncryptionKey($date);
		}
		else {
			$encrypt_key = $this->getEncryptionKey();
		}

		$td = mcrypt_module_open('rijndael-256', '', 'cfb', '');
    	mcrypt_generic_init($td, $encrypt_key['key'], $iv);
    	$decrypted_data = mdecrypt_generic($td, $encrypted_data);
    	mcrypt_generic_deinit($td);
    	mcrypt_module_close($td);

//		echo "<br>decrypted data: $decrypted_data <br>";
		$output = unserialize($decrypted_data);
//		print_r($output);


		return $output;
	}

	function checkNewKeyNeeded() {
		$now = $this->getDate();
		$current_key = $this->getEncryptionKey();
		if ($now != $current_key['date']) {
			return true;
		}
		else {
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

    /**
     * check if email adress is in a valid format
     * @return bool
     */
	static function checkEmail($email) {
//		if (!ereg("^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+\.([a-zA-Z0-9-]{2,3})$",$email)) {
		$regex = "/^[_a-zA-Z0-9]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]{2,}(\.[_a-zA-Z0-9-]+)?\.([a-zA-Z0-9-]{2,3})$/";
			/*	legal examples: name@domain.com, my.name@domain.net, name@subdomain.domain.de, my.name@subdomain.domain.org
			 *
			 * 	/^[_a-zA-Z0-9-]+		begins with letter or number
			 * 	(\.[_a-zA-Z0-9-]+)*		none or multiple letters or numbers which begins with a .
			 * 	@						@
			 * 	[a-zA-Z0-9-]{2,}		at least two more letters or numbers
			 * 	(\.[_a-zA-Z0-9-]+)?		optional: at least one more char or num which begins with a .
			 * 	\.						.
			 * 	([a-zA-Z0-9-]{2,3})$/	2-3 chars or numbers and end of expression
			 */
		if (!preg_match($regex,$email)) {
			echo "fatal error: email $email has wrong format!";
			return false;
		}

		//echo "email $email is ok<br>";
		return true;
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

		$ehead= "From: MARTI<marti@tripleawarclub.org>\r\n";
		 $ehead .= "List-Unsubscribe:<http://dice.tripleawarclub.org/unsubscribe.php>\r\n";


		$subj = "$subject";

		$mailsend= @mail("$to","$subj","$message","$ehead");

    /*$fd = popen("/usr/sbin/sendmail -t","w") or die("Couldn't Open Sendmail");
    fputs($fd, "To: ".$to." \n");
    fputs($fd, "From: \"MARTI\" <marti@tripleawarclub.org> \n");
    fputs($fd, "Subject: ".$subject." \n");
    fputs($fd, "X-Mailer: PHP3 \n\n");
    fputs($fd, $message);
    pclose($fd);*/

		if ($mailsend) {
				echo("<p>Dice results were sent via email!</p> <br> <a href=\"http://dice.tripleawarclub.org/MARTI_verify.php?date=$date&iv=$iv&enc=$encrypted_data\">click here to verify the roll</a><br>");
			}
		else {
		   echo("<p>Email delivery failed...</p> Dice results were not sent. <br> Please try it later again.");
		   exit("<p>fatal error: email delivery failed!");
		}

	}


}
?>