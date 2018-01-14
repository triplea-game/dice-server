<?php
include_once('dice.class.php');

if (!isset($_GET["iv"])) {
	exit("Initialization vector not specified.");
} elseif (!isset($_GET["enc"])) {
	exit("Encrypted dice not specified.");
}

$iv = $_GET["iv"];
$enc = $_GET["enc"];

$dice = new dice();

$outputArray = $dice->decrypt_data($iv, $enc);

echo "Dice were authentic: " . $outputArray['dice'];
?>
