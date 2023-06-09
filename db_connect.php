<?php

$host="cray.cc.gettysburg.edu";
$dbase="s23_kb";
$user="beatwi03";
$pass="beatwi03";

try {
    $db = new PDO("mysql:host=$host;dbname=$dbase", $user, $pass);
}
catch(PDOException $e) {
    // . : string concatenation (similar to Java's + operator)
	// -> instead of . for remote access
    die("ERROR connecting to mysql server " . $e->getMessage());
}

?>
