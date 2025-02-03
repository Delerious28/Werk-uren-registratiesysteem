<?php
session_start();
require('../fpdf/fpdf.php'); // Adjust the path if needed
include "../db/conn.php";    // Database Connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/inloggen.php");
    exit();
}

// Get filter from query parameters, if any. Default to 'all'.
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$date_condition = "";

// Apply filter logic to restrict the data by date (optional)
if ($filter === 'vandaag') {
    $date_condition = "WHERE DATE(hours.date) = CURDATE()";
} elseif ($filter === 'week') {
    $date_condition = "WHERE YEARWEEK(hours.date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'maand') {
    $date_condition = "WHERE MONTH(hours.date) = MONTH(CURDATE()) AND YEAR(hours.date) = YEAR(CURDATE())";
}

// Query to fetch total approved hours per user
// We join the `hours` and `users` tables and group by user_id.
$sql = "
    SELECT 
        users.name, 
        SUM(hours.hours) AS total_hours
    FROM hours
    JOIN users ON hours.user_id = users.user_id
    $date_condition
    AND hours.accord = 'Approved'
    GROUP BY users.user_id
    ORDER BY users.name ASC
";

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error retrieving data: " . $e->getMessage());
}

// Create PDF using FPDF
$pdf = new FPDF();
$pdf->AddPage();

// Title
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Werkuren Rapport', 0, 1, 'C');
$pdf->Ln(10);

// Table Header
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(100, 10, 'Medewerker', 1);
$pdf->Cell(40, 10, 'Totale Uren', 1);
$pdf->Ln();

// Table Data
$pdf->SetFont('Arial', '', 12);
foreach ($rows as $row) {
    // If a user has no approved hours, show 0 (using the null coalescing operator)
    $pdf->Cell(100, 10, $row['name'], 1);
    $pdf->Cell(40, 10, $row['total_hours'] ?? 0, 1);
    $pdf->Ln();
}

// Output the PDF and force download
$pdf->Output('D', 'werkuren_rapport.pdf');
exit();
?>
