<!DOCTYPE html>
<html>
	<head>
		<title>Validation site for MARTI dice services</title>
	</head>
	<body>
	<?php
	if(!isset($_GET["email"]) || !isset($_GET["val"]))
		exit("error: You used an invalid link!");

	$email = filter_input( INPUT_GET, "email", FILTER_SANITIZE_EMAIL );
	$validation = filter_input( INPUT_GET, "val", FILTER_SANITIZE_STRING );
	require_once("dice.class.php");

	$sql = "SELECT email AS CNT FROM pending_validations WHERE email=? and validation_key=?";
	$dice = new dice;
	$rows = [];

			if( $sth = $dice->dbconn->prepare( $sql ) ){
					$sth->bind_param('ss',$email,$validation);
					$sth->execute() or trigger_error($mysqli->error);
					$sth->bind_result($r_email,$r_validation);
					while($sth->fetch()){
							$rows[] = array( $r_email, $r_validation );
					}
			} else {
					echo "A DB error has occured, please contact an admin. (1-"; var_dump( $dice->dbconn->errno ); echo ")";exit;
			}

	if(empty($rows))
		exit("Could not verify the data. Please check the link you have received in your email");

	$sql = "INSERT INTO dice_emails (registered_email) VALUES (?)";

	if( $sth = $dice->dbconn->prepare( $sql ) ){
	  $sth->bind_param('s',$email);
	  $sth->execute() or trigger_error($mysqli->error);
	} else {
	  echo "A DB error has occured, please contact an admin. (2-"; var_dump( $dice->dbconn->errno ); echo ")";exit;
	}

	$sql = "DELETE FROM pending_validations WHERE email=?";

	if( $sth = $dice->dbconn->prepare( $sql ) ){
	  $sth->bind_param('s',$email);
	  $sth->execute() or trigger_error($mysqli->error);
	} else {
	  echo "A DB error has occured, please contact an admin. (3-"; var_dump( $dice->dbconn->errno ); echo ")";exit;
	}

	echo "Registration was successfull. You can now use the MARTI dice server.";

	?>

	</body>
</html>
