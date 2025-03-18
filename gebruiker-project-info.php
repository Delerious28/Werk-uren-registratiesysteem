<?php

session_start();
include 'db/conn.php';

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header("Location: inloggen.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Haal user_id uit sessie

// Haal alle projecten op die aan de gebruiker zijn gekoppeld
$query = "SELECT p.project_id, p.project_naam, p.beschrijving, p.contract_uren, 
                 k.voornaam AS klant_voornaam, k.achternaam AS klant_achternaam
          FROM project p
          JOIN klant k ON p.klant_id = k.klant_id
          JOIN project_users pu ON p.project_id = pu.project_id
          WHERE pu.user_id = :user_id"; // Haal projecten voor deze specifieke gebruiker op

$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($projects) {
    echo json_encode(['status' => 'success', 'projects' => $projects]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geen gekoppelde projecten gevonden.']);
}

?>
