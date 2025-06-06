<?php
$host = "localhost";
$user = "root"; // Change if needed
$password = ""; // Change if you have a password
$database = "user_auth";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>