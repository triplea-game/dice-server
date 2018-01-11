<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
?>

<!DOCTYPE html>
<html>
	<head>
		<title>	M.A.R.T.I. Server --> "more accurate rolls than irony"- server</title>
	</head>
	<body>
		<?php
		$output = [];

		$numsides = $_POST['numsides'];
		$numdice = $_POST['numdice'];
		$output['subject'] = $_POST['subject'];
		$email1 = $_POST['roller'];
		$email2 = $_POST['gm'];

		//check posted data
		if (!is_numeric($numsides) || !is_numeric($numdice) || empty($output['subject']) || empty($email1)) {
			exit("fatal error: wrong input!");
		}
		if (empty($email2)) {
			exit("fatal error: no second email found. Please enter an email address into the CC-field!");
		}
		//format multiple emails in one line
		$emails1 = explode(" ", $email1);
		$emails2 = explode(" ", $email2);

		$output['emails'] = array_merge($emails1, $emails2);
		// get exact server time
		$output['time'] = date("Y-m-d H:i:s");
		include_once('dice.class.php');

		$dice = new dice();

		//check if all emails are registered.
		// This method exits if one of them is not
		$missingEmails = $dice->getUnregisteredMails($output['emails']);
		if (!empty($missingEmails)) {
			exit("fatal error: emails " . implode(", ", $missingEmails) . " are not registered. Please register those emails at {$dice->domain}/register.php !");
		}

		//create dice
		$output['dice'] = $dice->createdice($numdice, $numsides);

		//encrypt the output array
		$enc_array = $dice->encrypt_data($output);

		//send email; if delivery fails the script is aborted!
		$dice->sendEmail($output['emails'], $output['subject'], $output['dice'], $enc_array['iv'], $enc_array['data']);

		//show dice
		echo "your dice are: {$output['dice']}<p><p>";
		?>
	</body>
</html>
