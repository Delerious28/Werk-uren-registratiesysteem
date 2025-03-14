<?php
// Start de sessie om toegang te krijgen tot $_SESSION
session_start();

// Verbind met de database
include('db/conn.php');

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    echo "Je moet ingelogd zijn om deze informatie te bekijken.";
    exit();
}

// Haal de datum op uit de URL
$date = isset($_GET['date']) ? $_GET['date'] : null;

if ($date) {
    // Valideer de datum
    $timestamp = strtotime($date);
    if (!$timestamp) {
        echo "<p>Ongeldige datum opgegeven.</p>";
        exit();
    }

    // Haal de gegevens van deze datum op inclusief start- en eindtijd
    $query = "
    SELECT hours.*, klant.voornaam AS klant_voornaam, klant.achternaam AS klant_achternaam, 
           project.project_naam AS project_naam
    FROM hours
    LEFT JOIN project ON hours.project_id = project.project_id
    LEFT JOIN klant ON project.klant_id = klant.klant_id
    WHERE hours.user_id = ? AND hours.date = ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $date]);

    // Controleer of er resultaten zijn
    if ($stmt->rowCount() > 0) {
        echo "<h2>Ingediende Uren voor " . date('d-m-Y', strtotime($date)) . "</h2>";

        while ($row = $stmt->fetch()) {
            echo "<p><strong>Uren:</strong> " . htmlspecialchars($row['hours']) . "</p>";
            echo "<p><strong>Status:</strong> " . htmlspecialchars($row['accord']) . "</p>";

            // Toon klantgegevens
            $klant_voornaam = !empty($row['klant_voornaam']) ? htmlspecialchars($row['klant_voornaam']) : 'Onbekend';
            $klant_achternaam = !empty($row['klant_achternaam']) ? htmlspecialchars($row['klant_achternaam']) : 'Onbekend';
            echo "<p><strong>Klant:</strong> " . $klant_voornaam . " " . $klant_achternaam . "</p>";

            // Toon projectgegevens
            $project_naam = !empty($row['project_naam']) ? htmlspecialchars($row['project_naam']) : 'Onbekend';
            echo "<p><strong>Project:</strong> " . $project_naam . "</p>";

            // ✅ Starttijd en eindtijd tonen
            $starttijd = !empty($row['start_hours']) ? htmlspecialchars($row['start_hours']) : 'Niet opgegeven';
            $eindtijd = !empty($row['eind_hours']) ? htmlspecialchars($row['eind_hours']) : 'Niet opgegeven';
            echo "<p><strong>Starttijd:</strong> " . $starttijd . "</p>";
            echo "<p><strong>Eindtijd:</strong> " . $eindtijd . "</p>";

            // Toon beschrijving
            $beschrijving = !empty($row['beschrijving']) ? htmlspecialchars($row['beschrijving']) : 'Geen beschrijving beschikbaar';
            echo "<p><strong>Beschrijving:</strong> " . $beschrijving . "</p>";

            echo "<hr>"; // Scheidingslijn tussen records
        }
    } else {
        echo "<p>Geen uren gevonden voor deze datum.</p>";
    }
} else {
    echo "<p>Geen datum opgegeven.</p>";
}
?>
