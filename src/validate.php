<!DOCTYPE html>
<html>
	<head>
		<title>Validation site for MARTI dice services</title>
	</head>
	<body>
		<?php
		if (!isset($_GET["email"])) {
			exit("Email not specified.");
		} elseif (!isset($_GET["val"])) {
			exit("Validation key not specified.");
		}

		$email = $_GET["email"];
		$validation = $_GET["val"];
		require_once("dice.class.php");

		$dice = new dice();

		$sth = $dice->dbconn->prepare("SELECT COUNT(*) FROM pending_validations WHERE email=? AND validation_key=?");
		$sth->bind_param('ss', $email, $validation);
		$sth->execute() or trigger_error($dice->dbconn->error);
		$sth->bind_result($entry_count);
		$sth->fetch();
		if ($entry_count === 0) {
			exit("Could not validate the email. Please check the link you received in your email.");
		}
		$sth->close();

		$sth = $dice->dbconn->prepare("INSERT INTO dice_emails (registered_email) VALUES (?)");
		$sth->bind_param('s', $email);
		$sth->execute() or trigger_error($dice->dbconn->error);
		$sth->close();

		$sth = $dice->dbconn->prepare("DELETE FROM pending_validations WHERE email=?");
		$sth->bind_param('s', $email);
		$sth->execute() or trigger_error($dice->dbconn->error);
		$sth->close();

		echo "Registration was successful. You can now use the MARTI dice server.";
		?>
	</body>
</html>
