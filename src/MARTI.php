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
error_reporting(E_ALL);
ini_set('display_errors', '1');
$output = array();

$send = $_POST['send'];
$numsides = $_POST['numsides'];
$numdice = $_POST['numdice'];
$output['subject'] = $_POST['subject'];
$email1 = $_POST['roller'];
$email2 = $_POST['gm'];

//check posted data
if(!is_numeric($numsides) || !is_numeric($numdice) || empty($output['subject']) || empty($email1)) {
	exit("fatal error: wrong input!");

}
if(empty($email2)) {
	exit("fatal error: no second email found. Please enter an email address into the Cc-field!");

}
//format multiple emails in one line
$emails1 = explode(" ", $email1);
$emails2 = explode(" ", $email2);

$output['emails'] = array_merge($emails1, $emails2);
// get exact server time
$output['time'] = date("Y-m-d H:i:s");


include_once('dice.class.php');

$dice = new dice;

//validate emails and exit if email is wrong
foreach($output['emails'] as $value) {
	if(! dice::checkEmail($value)) {
		exit("fatal error: at least one email is spelled wrong. check: \"$value\" !");
	}
}

// check if all emails are registered
try{	
$dice->checkIfMailsAreRegistered($output['emails']); 
}
catch(exception $e) {
	exit($e->getMessage());
}

//create dice
$output['dice'] = $dice->createdice($numdice, $numsides);

//update the stats in the database: number of requests and dice rolled
//$dice->updateStats($numdice);

//encrypt the output array
$enc_array = $dice->encrypt_data($output);

//send email; if delivery fails the script is aborted!
$dice->sendEmail($output['emails'], $output['subject'], $output['dice'], $enc_array['iv'], $enc_array['data']);

//show dice
echo "your dice are: " . $output['dice'] . "<p><p>";

?>
	</body>
</html>
