<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "werk-uren-registratiesysteem";

// Verbinding maken
$conn = new mysqli($servername, $username, $password, $dbname);

// Verbinding checken
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

