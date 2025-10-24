<?php

$host = "localhost";
$user = "root";
$pass = "";
$db = " barangay_webs";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set MySQL timezone to Philippines (UTC+8)
$conn->query("SET time_zone = '+08:00'");
?>