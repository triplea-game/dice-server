<!DOCTYPE html>
<html>
	<head>
		<title>Register your email for MARTI services</title>
	</head>
	<body>
		<?php
		if (! isset($_POST["email"]) || empty($_POST["email"])) {
		// template for first load
		?>
		<form method="post" action="register.php">
			Enter your email here to register for using the MARTI dice server:
			<br/>
			<input type="text" name="email" width="40" />
			<br/>
			<input type="submit" value="register">
		</form>
		<?php
		}	else {
		// template for handling postbacks
			$email = filter_input( INPUT_POST, "email", FILTER_SANITIZE_EMAIL );
			require_once("dice.class.php");
			$dice = new dice();

			// check if the email already exists
			if ($dice->checkIfMailIsRegistered($email))
				exit("This email is already registered");

			// collecting information for validation
			$time = time(); // in unix format
			$validation = md5($email . $time . rand());
			// ignore IP for now due to ip2long() not being able to handle IPv6 addresses
			// TODO: remove this column from the database
			$IP = 0;

			$sql = "SELECT email FROM pending_validations WHERE email=?";
			$rows = [];
			if ( $sth = $dice->dbconn->prepare( $sql )) {
				$sth->bind_param('s',$email);
				$sth->execute() or trigger_error($mysqli->error);
				$sth->bind_result($emailColumn);
				while($sth->fetch()) {
					$rows[] = $emailColumn;
				}
			} else {
				echo "A DB error has occured, please contact an admin. (";
				var_dump( $dice->dbconn->errno );
				echo ")";
				exit;
			}

			// insert or update pending validation
			if (empty($rows)) {
				$sql = "INSERT INTO pending_validations (email, validation_key, time_stamp, IP) VALUES (?, ?, FROM_UNIXTIME(?), ?)";
				$sth = $dice->dbconn->prepare( $sql );
				$sth->bind_param('ssss', $email, $validation, $time, $IP );
				$sth->execute();
			}	else {
				$sql = "UPDATE pending_validations SET validation_key=?, time_stamp= FROM_UNIXTIME(?), IP=? WHERE email=?";
				$sth = $dice->dbconn->prepare( $sql );
        $sth->bind_param('ssss', $validation, $time, $IP, $email );
        $sth->execute();
			}
			// sending email
			$to = $email;
			$email_enc = urlencode($email);
			$subj = "Registration for MARTI dice server";
			$from = "marti@tripleawarclub.org";
			$ehead= "From: MARTI<".$from.">\r\n";
			$ehead .= "List-Unsubscribe:<$dice->domain/unsubscribe.php?email=$email_enc>\r\n";
			$message = "To validate your email click this link: $dice->domain/validate.php?email=$email_enc&val=$validation";
			$message .= "\r\n\r\nTo unsubscribe from this service go to $dice->domain/unsubscribe.php?email=$email_enc";
			$mailsend= @mail($to,$subj,$message,$ehead,"-f $from -r no-reply@tripleawarclub.org");

			if ($mailsend) {
				echo("<p>You should receive an email in your postbox with a validation link soon.</p>After validating your email you can use the MARTI dice server");
			}	else {
			   echo("<p>Email delivery failed...</p>Please try it later again.");
			}
		}
		?>
	</body>
</html>
