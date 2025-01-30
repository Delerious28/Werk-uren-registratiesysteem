<?php
$servername = "localhost";
$username = "bit_acadmey";
$password = "bit_acadmey";
$dbname = "werk_uren_registratiesysteem";

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>


