CREATE TABLE `dice_emails` (
  `registered_email` varchar(255) NOT NULL,
  UNIQUE KEY `registered_email` (`registered_email`)
) DEFAULT CHARSET=latin1;

CREATE TABLE `pending_validations` (
  `email` varchar(255) NOT NULL,
  `validation_key` char(32) NOT NULL,
  `time_stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `IP` int(10) unsigned NOT NULL,
  UNIQUE KEY `email` (`email`)
) DEFAULT CHARSET=latin1;
