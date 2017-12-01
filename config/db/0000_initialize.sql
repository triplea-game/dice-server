START TRANSACTION;

CREATE TABLE `dice_emails` (
  `registered_email` varchar(255) NOT NULL,
  UNIQUE KEY `registered_email` (`registered_email`)
) DEFAULT CHARSET=latin1;

CREATE TABLE `dice_table` (
  `request_ID` int(11) NOT NULL AUTO_INCREMENT,
  `UUID` varchar(36) NOT NULL,
  `dice` varchar(256) NOT NULL,
  `subject` varchar(70) NOT NULL,
  `time_stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_ID`),
  KEY `UUID` (`UUID`)
) DEFAULT CHARSET=latin1;

CREATE TABLE `pending_validations` (
  `email` varchar(255) NOT NULL,
  `validation_key` char(32) NOT NULL,
  `time_stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `IP` int(10) unsigned NOT NULL,
  UNIQUE KEY `email` (`email`)
) DEFAULT CHARSET=latin1;

COMMIT;
