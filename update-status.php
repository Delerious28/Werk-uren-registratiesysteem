<?php
session_start();
require 'db/conn.php';

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header('Location: inloggen.php');
    exit();
}

$accordMessage = '';
$status = 'success'; // Standaardstatus is success

// Maandnamen array
$monthNames = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maart', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Augustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'December'
];

// Controleer of het een POST-aanroep is en of de maandparameter aanwezig is
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['month'])) {
    $month = $_POST['month']; // Verkrijg de maandparameter (bijv. "2025-03")

    // Extract de maand (bijv. "03") van de datum
    $monthNumber = substr($month, 5, 2);

    // Verkrijg de maandnaam uit de array
    $monthName = $monthNames[$monthNumber];

    try {
        // Bereid een SQL-query voor om alleen uren met de status 'Pending' voor de opgegeven maand goed te keuren
        $stmt = $pdo->prepare("UPDATE hours SET accord = 'Approved' WHERE DATE_FORMAT(date, '%Y-%m') = :month AND accord = 'Pending'");
        $stmt->bindParam(':month', $month);
        $stmt->execute();

        // Controleer of er uren zijn goedgekeurd
        if ($stmt->rowCount() > 0) {
            $accordMessage = "Alle uren voor $monthName zijn geaccordeerd!"; // Gebruik maandnaam
        } else {
            $accordMessage = "Geen afwachting uren voor $monthName gevonden!"; // Gebruik maandnaam
            $status = 'error'; // Foutstatus als er geen uren met status 'Pending' zijn
        }
    } catch (PDOException $e) {
        $accordMessage = "Fout bij goedkeuren: " . $e->getMessage();
        $status = 'error'; // Foutstatus bij een databasefout
    }

    // Stuur de bericht als JSON terug naar de frontend met een status
    echo json_encode(['message' => $accordMessage, 'status' => $status]);
} else {
    echo json_encode(['message' => "Ongeldig verzoek. Maandparameter ontbreekt.", 'status' => 'error']);
}
?>
