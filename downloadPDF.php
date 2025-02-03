<?php
session_start();
require('../fpdf/fpdf.php'); // Adjust the path if needed
include "../db/conn.php";    // Database Connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../inloggen.php");
    exit();
}

// Get filter from URL parameters (default: all)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$date_condition = "WHERE hours.accord = 'Approved'"; // Show only approved records

// Apply filter condition
if ($filter === 'vandaag') {
    $date_condition .= " AND DATE(hours.date) = CURDATE()";
} elseif ($filter === 'week') {
    $date_condition .= " AND YEARWEEK(hours.date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'maand') {
    $date_condition .= " AND MONTH(hours.date) = MONTH(CURDATE()) AND YEAR(hours.date) = YEAR(CURDATE())";
}

// Fetch approved hours grouped by user and date
$sql = "
    SELECT 
        users.name, 
        hours.date, 
        SUM(hours.hours) AS total_hours
    FROM hours
    JOIN users ON hours.user_id = users.user_id
    $date_condition
    GROUP BY users.user_id, hours.date
    ORDER BY hours.date ASC, users.name ASC
";

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error retrieving data: " . $e->getMessage());
}

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Werkuren Rapport', 0, 1, 'C');
$pdf->Ln(10);

// Table Header
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Datum', 1);
$pdf->Cell(70, 10, 'Medewerker', 1);
$pdf->Cell(40, 10, 'Totale Uren', 1);
$pdf->Ln();

// Table Data
$pdf->SetFont('Arial', '', 12);
foreach ($rows as $row) {
    $pdf->Cell(50, 10, date('d-m-Y', strtotime($row['date'])), 1);
    $pdf->Cell(70, 10, $row['name'], 1);
    $pdf->Cell(40, 10, $row['total_hours'] ?? 0, 1);
    $pdf->Ln();
}

// Output the PDF
$pdf->Output('D', 'werkuren_rapport.pdf');
exit();
?>
