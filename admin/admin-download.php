<?php
session_start();  // Start een nieuwe sessie of hervat de bestaande sessie
require('../fpdf/fpdf.php'); // Laadt de FPDF-bibliotheek voor het genereren van PDF-bestanden
include "../db/conn.php"; // Verbind met de database

// Controleer of de gebruiker is ingelogd, anders wordt de gebruiker doorgestuurd naar de loginpagina
if (!isset($_SESSION['user_id'])) {
    header("Location: ../inloggen.php");  // Doorverwijzen naar de loginpagina als de gebruiker niet ingelogd is
    exit();  // Stop het uitvoeren van de script
}

// Haal gebruikersgegevens op uit de sessie
$user_id = intval($_SESSION['user_id']);  // Haal het user_id op uit de sessie en zet het om naar een integer
$user_name = htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8');  // Haal de gebruikersnaam op en beveilig deze

// Functie om de PDF te genereren
function generatePDF($pdo, $user_id, $user_name) {
    // SQL-query om goedgekeurde uren van de huidige maand op te halen
    $sql = "SELECT hours_id, date, hours FROM hours 
            WHERE user_id = :user_id 
            AND accord = 'Approved'  -- Alleen goedgekeurde uren
            AND MONTH(date) = MONTH(CURDATE()) 
            AND YEAR(date) = YEAR(CURDATE()) 
            ORDER BY date ASC";  // Sorteren op datum
    $stmt = $pdo->prepare($sql);  // Bereid de SQL-query voor
    $stmt->execute([':user_id' => $user_id]);  // Voer de query uit met de user_id als parameter
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);  // Haal alle resultaten op

    // Maandnamen in het Nederlands
    $month_names = [
        1 => 'januari', 2 => 'februari', 3 => 'maart', 4 => 'april', 5 => 'mei', 6 => 'juni',
        7 => 'juli', 8 => 'augustus', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december'
    ];
    $month_number = (int)date('m');  // Haal het maandnummer op van de huidige maand
    $month_name = $month_names[$month_number];  // Haal de maandnaam uit de array

    // Stel de locale in op Nederlands om de datum en maand in het juiste formaat te krijgen
    setlocale(LC_TIME, 'nl_NL.UTF-8', 'Dutch_Netherlands.UTF-8');

    // Haal de huidige maandnaam op in het Nederlands
    $month_name = strftime('%B');  // Maandnaam in het Nederlands

    // Maak een nieuwe PDF
    $pdf = new FPDF();
    $pdf->AddPage();  // Voeg een nieuwe pagina toe

    // Voeg het logo toe in het midden bovenaan
    $pdf->Image('../img/logo.png', 75, 6, 50);

    // Zet het lettertype voor de titel
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetXY(10, 30);  // Zet de positie van de titel
    $pdf->Cell(0, 10, 'Maandelijkse Uren (Goedgekeurd)', 0, 1, 'L');  // Voeg de titel toe

    // Zet het lettertype voor de overige tekst
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetXY(10, 40);  // Zet de positie van de tekst
    $pdf->Cell(0, 10, 'Gegevens', 0, 1, 'L');  // Voeg de sectie "Gegevens" toe
    $pdf->SetXY(10, 50);  // Zet de positie voor de naam
    $pdf->Cell(0, 10, 'Naam: ' . $user_name, 0, 1, 'L');  // Voeg de naam van de gebruiker toe

    // Tabelkoppen voor de uren
    $pdf->SetFillColor(109, 15, 16);  // Stel de achtergrondkleur in voor de kop
    $pdf->SetTextColor(255, 255, 255);  // Zet de tekstkleur naar wit
    $pdf->SetXY(10, 60);  // Zet de positie voor de tabelkop
    $pdf->Cell(60, 10, 'Datum', 1, 0, 'C', true);  // Voeg de kolom "Datum" toe
    $pdf->Cell(60, 10, 'Gewerkte uren', 1, 1, 'C', true);  // Voeg de kolom "Gewerkte uren" toe

    // Reset de tekstkleur naar zwart voor de rest van de tekst
    $pdf->SetTextColor(0, 0, 0);

    // Variabele om het totaal aantal uren bij te houden
    $total_hours = 0;
    foreach ($rows as $row) {
        // Voeg elke rij met datum en uren toe aan de PDF
        $pdf->Cell(60, 10, (new DateTime($row['date']))->format('d-m-Y'), 1);  // Datum in dd-mm-jjjj formaat
        $pdf->Cell(60, 10, $row['hours'], 1);  // Voeg het aantal gewerkte uren toe
        $pdf->Ln();  // Voeg een nieuwe regel toe
        $total_hours += $row['hours'];  // Voeg de gewerkte uren toe aan het totaal
    }

    // Controleer of er uren zijn, anders geef een melding
    if ($total_hours == 0) {
        $pdf->Cell(120, 10, 'Geen goedgekeurde uren gevonden voor deze maand.', 1, 1, 'C');  // Geen uren gevonden
    } else {
        $pdf->Ln();  // Voeg een nieuwe regel toe
        $pdf->SetFont('Arial', 'B', 12);  // Zet het lettertype voor de totalen
        $pdf->Cell(60, 10, 'Totaal Uren:', 1);  // Voeg de tekst "Totaal Uren" toe
        $pdf->Cell(60, 10, $total_hours, 1);  // Voeg het totaal aantal uren toe
    }

    // Genereer de naam van het PDF-bestand op basis van de naam van de gebruiker en de maand
    $file_name = $user_name . '_uren_' . ucfirst($month_name) . '.pdf';  // Het bestand krijgt de naam: naam_uren_maand.pdf

    // Download de PDF
    $pdf->Output('D', $file_name);  // Output de PDF voor download
}

// Verwerk de PDF-download aanvraag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_pdf'])) {
    $download_user_id = $_POST['user_id'];  // Haal het user_id op van de aanvraag
    $download_user_name = $_POST['user_name'];  // Haal de gebruikersnaam op
    generatePDF($pdo, $download_user_id, $download_user_name);  // Genereer de PDF voor de opgegeven gebruiker
    exit();  // Stop het uitvoeren van de script
}


// Fetch alle gebruikers uit de database
$sql = "SELECT DISTINCT users.user_id, users.name, users.role FROM users 
        LEFT JOIN hours ON users.user_id = hours.user_id 
        WHERE users.role = 'user'";  // Alleen de gebruikers met de rol 'user'
$stmt = $pdo->prepare($sql);  // Bereid de SQL-query voor
$stmt->execute();  // Voer de query uit
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);  // Haal alle resultaten op
?>




<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maandelijkse Uren Download</title>
</head>
<body>
<div class="container">

    <div class="content">
        <div class="download-header-div">
            <div class="download-header-text1">Maandelijkse Uren (PDF)</div>
            <div class="download-header-text2">Download</div>
        </div>

        <table>
            <?php foreach ($users as $user): ?>
                <?php
                // Check of de gebruiker uren heeft voor de huidige maand
                $hour_check_sql = "SELECT COUNT(*) FROM hours WHERE user_id = :user_id AND hours_id IS NOT NULL AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
                $hour_check_stmt = $pdo->prepare($hour_check_sql);
                $hour_check_stmt->execute([':user_id' => $user['user_id']]);
                $hour_check = $hour_check_stmt->fetchColumn();
                ?>
                <tr class="download-pagina-tr">
                    <td class="naamNuser-icon">
                        <img src="../img/user-icon.png" alt="icon" class="user-icon"><?= htmlspecialchars($user['name']) ?>
                    </td>
                    <td class="n-v-t-td">
                        <?php if ($hour_check > 0): ?>
                            <form method="POST" class="pdf-form">
                                <input type="hidden" name="download_pdf" value="1">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <input type="hidden" name="user_name" value="<?= htmlspecialchars($user['name']) ?>">
                                <button type="submit"><img src="../img/pdf.png" alt="PDF" class="pdf-icon"></button>
                            </form>
                        <?php else: ?>
                            <span>n.v.t</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
