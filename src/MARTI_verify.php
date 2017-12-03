<?php
include_once('dice.class.php');

if(!isset($_GET["iv"]) || !isset($_GET["enc"]))
	exit("error: You used an invalid link!");

$iv = $_GET["iv"];
$enc = $_GET["enc"];

$dice = new dice();

$outputArray = $dice->decrypt_data(null, $iv, $enc);

echo "Dice were authentic: " . $outputArray['dice'];
?>