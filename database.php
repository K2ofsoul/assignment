<?php

$hostName = "localhost";
$dbUser = "root";
$dbPassword = "";
$dbName = "login_data";
$conn = mysqli_connect($hostName, $dbUser, $dbPassword, $dbName);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>