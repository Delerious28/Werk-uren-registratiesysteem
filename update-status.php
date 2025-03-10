<?php
session_start();
require 'db/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hours_id'], $_POST['status'])) {
    $hours_id = $_POST['hours_id'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE hours SET status = :status WHERE hours_id = :hours_id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':hours_id', $hours_id);
        $stmt->execute();

        echo "Status succesvol bijgewerkt.";
    } catch (PDOException $e) {
        echo "Fout bij bijwerken: " . $e->getMessage();
    }
} else {
    echo "Ongeldig verzoek.";
}
?>
