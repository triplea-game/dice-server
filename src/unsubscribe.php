<!DOCTYPE html>
<html>
	<head>
		<title>Unsubscribe from MARTI dice services</title>
	</head>
	<body>
		<?php
		$email = $_POST["email"] ?? ($_GET["email"] ?? null);

		if (is_null($email)) {
		// template for first load
		?>
		<form method="post" action="unsubscribe.php">
			Enter your email to unsubscribe from MARTI dice services:
			<br/>
			<input type="text" name="email" width="40" />
			<br/>
			<input type="submit" value="unsubscribe">
		</form>
		<?php
		} else {
		// template for handling postbacks
			require_once("dice.class.php");
			$dice = new dice();

			if (!$dice->isMailRegistered($email)) {
				exit("The email is not registered or was never validated.");
			}

			$sth = $dice->dbconn->prepare("DELETE FROM dice_emails WHERE registered_email=?");
			$sth->bind_param('s',$email);
			$sth->execute() or exit($dice->dbconn->error);

			echo "Your email was successfully removed. You will no longer receive dice emails.";
		}
		?>
	</body>
</html>
