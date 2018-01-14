<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
?>

<!DOCTYPE html>
<html>
	<head>
		<title>M.A.R.T.I. Server -- "more accurate rolls than irony"</title>
	</head>
	<body>
		<?php
		$output = [];

		$numsides = $_POST['numsides'];
		$numdice = $_POST['numdice'];
		$output['subject'] = $_POST['subject'];
		$email1 = $_POST['roller'];
		$email2 = $_POST['gm'];

		// NB: "fatal error:" prefix and "!" suffix on error messages in this
		// module are required by the TripleA parser

		//check posted data
		if (!is_numeric($numsides)) {
			exit("fatal error: Dice sides is not numeric.!");
		} elseif (!is_numeric($numdice)) {
			exit("fatal error: Dice count is not numeric.!");
		} elseif (empty($output['subject'])) {
			exit("fatal error: Email subject not specified.!");
		} elseif (empty($email1)) {
			exit("fatal error: No first email specified. Please enter an email in the To field.!");
		} elseif (empty($email2)) {
			exit("fatal error: No second email specified. Please enter an email in the CC field.!");
		}
		//format multiple emails in one line
		$emails1 = preg_split("/\s+/", $email1, -1, PREG_SPLIT_NO_EMPTY);
		$emails2 = preg_split("/\s+/", $email2, -1, PREG_SPLIT_NO_EMPTY);

		$output['emails'] = array_merge($emails1, $emails2);
		// get exact server time
		$output['time'] = date("Y-m-d H:i:s");
		include_once('dice.class.php');

		$dice = new dice();

		//check if all emails are registered.
		// This method exits if one of them is not
		$missingEmails = $dice->getUnregisteredMails($output['emails']);
		if (!empty($missingEmails)) {
			exit("fatal error: Emails [" . implode(", ", $missingEmails) . "] are not registered. Please register them at {$dice->domain}/register.php .!");
		}

		//create dice
		$output['dice'] = $dice->createdice($numdice, $numsides);

		//encrypt the output array
		$enc_array = $dice->encrypt_data($output);

		//send email; if delivery fails the script is aborted!
		$dice->sendEmail($output['emails'], $output['subject'], $output['dice'], $enc_array['iv'], $enc_array['data']);

		//show dice
		// NB: "your dice are: " prefix and "<p>" suffix are required by the TripleA parser
		echo "your dice are: {$output['dice']}<p><p>";
		?>
	</body>
</html>
