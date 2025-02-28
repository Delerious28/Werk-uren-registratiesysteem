<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "werk_uren_registratiesysteem2";
// ""
// Creëer PDO connectie
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Stel de PDO-foutmodus in op uitzondering
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>


