<html>
	<head>
		<title>Unsubscribe from MARTI dice services</title>
	</head>
	<body>
		<?php
		$email = filter_input( INPUT_POST, "email", FILTER_SANITIZE_EMAIL );
		if (is_null( $email ) || $email===false) {
			$email = filter_input( INPUT_GET, "email", FILTER_SANITIZE_EMAIL );
		}
		if (is_null( $email ) || $email===false) {
		// template for first load
		?>
		<form method="post" action="unsubscribe.php">
			Enter your email here to unsubscribe your email from MARTI dice services:
			<br/>
			<input type="text" name="email" width="40" />
			<br/>
			<input type="submit" value="unsubscribe">
		</form>
		<?php
		}
		else {
		// template for handling postbacks
			require_once("dice.class.php");
			$dice = new dice();

			if (!$dice->checkIfMailIsRegistered($email)) {
				exit("This email is not registered, or was never validated.");
			}
			$sql = "DELETE FROM dice_emails WHERE registered_email=?";

			if ($sth = $dice->dbconn->prepare( $sql )) {
			  $sth->bind_param('s',$email);
			  $sth->execute() or trigger_error($mysqli->error);
			} else {
			  echo "A DB error has occured, please contact an admin. (2-";
				var_dump( $dice->dbconn->errno );
				echo ")";
				exit;
			}
			echo "Your email was successfully removed. You will no longer receive dice emails.";
		}
		?>
	</body>
</html>
