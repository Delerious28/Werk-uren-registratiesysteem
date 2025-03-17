<?php
session_start();
include 'db/conn.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Je moet ingelogd zijn om deze informatie te zien.']);
    exit();
}

if (isset($_GET['project_id'])) {
    $project_id = $_GET['project_id'];

    // Haal de projectinformatie en klantvoornaam op uit de database
    $query = "SELECT p.project_naam, p.beschrijving, p.contract_uren, k.voornaam AS klant_voornaam, k.achternaam AS klant_achternaam
              FROM project p
              JOIN klant k ON p.klant_id = k.klant_id
              WHERE p.project_id = :project_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt->execute();

    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($project) {
        // Stuur JSON terug naar AJAX
        echo json_encode([
            'status' => 'success',
            'project_naam' => htmlspecialchars($project['project_naam']),
            'beschrijving' => htmlspecialchars($project['beschrijving']),
            'contract_uren' => htmlspecialchars($project['contract_uren']),
            'klant_voornaam' => htmlspecialchars($project['klant_voornaam']),
            'klant_achternaam' => htmlspecialchars($project['klant_achternaam'])
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Geen project gevonden.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Project ID ontbreekt.']);
}
?>
