<!DOCTYPE html>
<html>
	<head>
		<title>Register for MARTI dice services</title>
	</head>
	<body>
		<?php
		if (!isset($_POST["email"]) || empty($_POST["email"])) {
		// template for first load
		?>
		<form method="post" action="register.php">
			Enter your email to register for MARTI dice services:
			<br/>
			<input type="text" name="email" width="40" />
			<br/>
			<input type="submit" value="register">
		</form>
		<?php
		} else {
		// template for handling postbacks
			$email = $_POST["email"];
			require_once("dice.class.php");
			$dice = new dice();

			// check if the email already exists
			if ($dice->isMailRegistered($email)) {
				exit("The email is already registered.");
			}

			// generating a random token for registration validation
			// the DB table allows entries up to 32 chars
			$validation = base64_encode(random_bytes(24));

			// ignore IP for now due to ip2long() not being able to handle IPv6 addresses
			// TODO: remove this column from the database, remove time_stamp as well
			// because it is ignored as well if the timestamp doesn't provide much value
			$sql = "REPLACE INTO pending_validations (email, validation_key, IP) VALUES (?, ?, 0)";
			$sth = $dice->dbconn->prepare($sql);
			$sth->bind_param('ss', $email, $validation);
			$sth->execute() or trigger_error($dice->dbconn->error);
			$sth->close();

			// sending email
			$to = $email;
			$email_enc = urlencode($email);
			$subj = "Registration for MARTI dice server";
			$from = "marti@tripleawarclub.org";
			$ehead= "From: MARTI<$from>\n";
			$ehead .= "List-Unsubscribe:<$dice->domain/unsubscribe.php?email=$email_enc>\n";
			$message = "To validate your email click this link: $dice->domain/validate.php?email=$email_enc&val=" . urlencode($validation);
			$message .= "\n\nTo unsubscribe from this service go to $dice->domain/unsubscribe.php?email=$email_enc";
			$mailsend= @mail($to, $subj, $message, $ehead, "-f $from -r no-reply@tripleawarclub.org");

			if ($mailsend) {
				echo("<p>You should receive an email with a validation link soon.</p>After validating your email you can use the MARTI dice server.");
			} else {
				echo("<p>Email delivery failed...</p>Validation email was not sent.<br>Please try again later.");
			}
		}
		?>
	</body>
</html>
