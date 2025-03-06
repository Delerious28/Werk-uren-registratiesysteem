<?php
session_start();
include 'db/conn.php';

// foutmeldingen
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Controleer of de gebruiker ingelogd is
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Niet geautoriseerd"]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Controleer of de POST-gegevens aanwezig zijn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hours_id'], $_POST['accord'])) {
    // Log de ontvangen gegevens
    error_log('POST-gegevens: ' . print_r($_POST, true));

    $hours_id = $_POST['hours_id'];
    $accord = $_POST['accord'];

    // Controleer of de gebruiker toegang heeft tot het project dat bij dit urenrecord hoort
    try {
        $checkSql = "
        SELECT p.klant_id 
        FROM hours h 
        JOIN project p ON h.project_id = p.project_id 
        JOIN klant k ON p.klant_id = k.klant_id 
        WHERE h.hours_id = :hours_id AND p.user_id = :user_id";

        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['hours_id' => $hours_id, 'user_id' => $user_id]);

        // Als de gebruiker toegang heeft, werk dan de status bij
        if ($checkStmt->rowCount() > 0) {
            try {
                // Update de status van het urenrecord
                $updateSql = "UPDATE hours SET accord = :accord WHERE hours_id = :hours_id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute(['accord' => $accord, 'hours_id' => $hours_id]);

                // Stuur een succesbericht terug
                echo json_encode(["status" => "success", "message" => "Status bijgewerkt"]);
            } catch (PDOException $e) {
                // Als er een fout optreedt bij de update
                echo json_encode(["status" => "error", "message" => "Er is een fout opgetreden bij het bijwerken van de status: " . $e->getMessage()]);
            }
        } else {
            // Als de gebruiker geen toegang heeft tot het project
            echo json_encode(["status" => "error", "message" => "Geen toegang tot dit project"]);
        }
    } catch (PDOException $e) {
        // Foutmelding als de toegangsscontrole niet werkt
        echo json_encode(["status" => "error", "message" => "Fout bij toegang tot het project: " . $e->getMessage()]);
    }
} else {
    // Als de vereiste POST-gegevens ontbreken
    echo json_encode(["status" => "error", "message" => "Ongeldige aanvraag"]);
}
