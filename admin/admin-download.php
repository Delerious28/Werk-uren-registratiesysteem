<?php
session_start();
require('../fpdf/fpdf.php'); // Zorg dat het pad correct is
include "../db/conn.php"; // Verbind met de database

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header("Location: ../inloggen.php");
    exit();
}

// Haal gebruikersgegevens op
$user_id = intval($_SESSION['user_id']);
$user_name = htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8');

// Functie om PDF te genereren voor de geselecteerde gebruiker
function generatePDF($pdo, $user_id, $user_name) {
    $sql = "SELECT hours_id, date, hours FROM hours 
            WHERE user_id = :user_id 
            AND hours_id IS NOT NULL 
            AND MONTH(date) = MONTH(CURDATE()) 
            AND YEAR(date) = YEAR(CURDATE()) 
            ORDER BY date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Maak een nieuwe PDF
    $pdf = new FPDF();
    $pdf->AddPage();

    // Voeg logo toe
    $pdf->Image('../img/logo.png', 10, 6, 30); // Zorg dat het logo correct wordt weergegeven

    // Titel
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Maandelijkse Urenoverzicht', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Werknemer: ' . $user_name, 0, 1);

    // Tabelkoppen
    $pdf->Cell(60, 10, 'Datum', 1);  // Maak de breedte van de datumkolom 60
    $pdf->Cell(60, 10, 'Gewerkte uren', 1);   // Maak de breedte van de uren kolom ook 60
    $pdf->Ln();

    $total_hours = 0;
    foreach ($rows as $row) {
        $pdf->Cell(60, 10, $row['date'], 1);
        $pdf->Cell(60, 10, $row['hours'], 1);
        $pdf->Ln();
        $total_hours += $row['hours'];
    }

    // Totaal uren toevoegen
    $pdf->Ln();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(60, 10, 'Totaal Uren:', 1);  // Maak de breedte van deze cel gelijk aan de datumkolom
    $pdf->Cell(60, 10, $total_hours, 1);    // Zorg dat de uren ook gelijk is in breedte

    // Download de PDF
    $pdf->Output('D', 'Maandelijkse_Urenoverzicht.pdf');
}


// Verwerk de PDF-download aanvraag (gebruiker_id wordt hier doorgegeven)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_pdf'])) {
    $download_user_id = $_POST['user_id']; // Dit is de gebruiker_id van degene die de download aanvraagt
    $download_user_name = $_POST['user_name']; // Naam van de gebruiker
    generatePDF($pdo, $download_user_id, $download_user_name);
    exit();
}

// Fetch alle gebruikers
$sql = "SELECT DISTINCT users.user_id, users.name, users.role FROM users 
        LEFT JOIN hours ON users.user_id = hours.user_id 
        WHERE users.role = 'user'";  // Filter alleen de gebruikers
$stmt = $pdo->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maandelijkse Uren Download</title>
    <link rel="stylesheet" href="../css/admin-index.css">
</head>
<body>
<div class="container">

    <?php include 'admin-header.php' ?>

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
