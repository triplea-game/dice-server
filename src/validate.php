<!DOCTYPE html>
<html>
	<head>
		<title>Validation site for MARTI dice services</title>
	</head>
	<body>
		<?php
		if (!isset($_GET["email"]) || !isset($_GET["val"])) {
			exit("error: You used an invalid link!");
		}

		$email = $_GET["email"];
		$validation = $_GET["val"];
		require_once("dice.class.php");

		$dice = new dice();

		$sth = $dice->dbconn->prepare("SELECT email FROM pending_validations WHERE email=? AND validation_key=?");
		$sth->bind_param('ss', $email, $validation);
		$sth->execute() or trigger_error($mysqli->error);
		if ($sth->num_rows === 0) {
			exit("Could not verify the data. Please check the link you have received in your email");
		}
		$sth->close();

		$sth = $dice->dbconn->prepare("INSERT INTO dice_emails (registered_email) VALUES (?)");
		$sth->bind_param('s', $email);
		$sth->execute() or trigger_error($mysqli->error);
		$sth->close();

		$sth = $dice->dbconn->prepare("DELETE FROM pending_validations WHERE email=?");
		$sth->bind_param('s', $email);
		$sth->execute() or trigger_error($mysqli->error);
		$sth->close();

		echo "Registration was successfull. You can now use the MARTI dice server.";
		?>
	</body>
</html>
