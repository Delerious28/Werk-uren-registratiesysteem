<?php
session_start();
require('../fpdf/fpdf.php'); // Adjust the path if needed
include "../db/conn.php"; // Database Connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/inloggen.php");
    exit();
}

// Sanitize session data
$user_id = htmlspecialchars($_SESSION['user_id'], ENT_QUOTES, 'UTF-8');
$user_name = htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8');

// Handle filter selection
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // Default to 'all'
$date_condition = "";
if ($filter === 'vandaag') {
    $date_condition = "WHERE DATE(hours.date) = CURDATE()";
} elseif ($filter === 'week') {
    $date_condition = "WHERE YEARWEEK(hours.date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'maand') {
    $date_condition = "WHERE MONTH(hours.date) = MONTH(CURDATE()) AND YEAR(hours.date) = YEAR(CURDATE())";
}

// SQL query with filter condition
$sql = "
    SELECT 
        hours.hours_id,
        hours.user_id, 
        users.name, 
        hours.hours, 
        hours.date, 
        hours.accord 
    FROM hours
    JOIN users ON hours.user_id = users.user_id
    $date_condition
    ORDER BY hours.date ASC
";

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error retrieving data: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_hours'])) {
        $new_hours = htmlspecialchars($_POST['new_hours'], ENT_QUOTES, 'UTF-8');
        $hours_id = intval($_POST['hours_id']);
        $update_sql = "UPDATE hours SET hours = :hours WHERE hours_id = :hours_id";
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([':hours' => $new_hours, ':hours_id' => $hours_id]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?filter=" . urlencode($filter));
        exit();
    }
    if (isset($_POST['complete_status'])) {
        $hours_id = intval($_POST['hours_id']);
        $update_sql = "UPDATE hours SET accord = 'Approved' WHERE hours_id = :hours_id";
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([':hours_id' => $hours_id]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?filter=" . urlencode($filter));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
</head>
<body>
<div class="container dashboard">
    <div class="container Y-navbar">
        <h1>Admin Dashboard</h1>
    </div>

    <form class="container filter" method="GET" action="">
        <label for="filter">Filter op:</label>
        <select name="filter" id="filter" onchange="this.form.submit()">
            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Alles</option>
            <option value="vandaag" <?= $filter === 'vandaag' ? 'selected' : '' ?>>Vandaag</option>
            <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Week</option>
            <option value="maand" <?= $filter === 'maand' ? 'selected' : '' ?>>Maand</option>
        </select>
    </form>

    <?php if (count($rows) > 0): ?>
        <?php foreach ($rows as $row): ?>
            <div class="container user-row">
                <div class="div username"><strong>Name:</strong> <?= htmlspecialchars($row["name"]) ?></div>
                <div class="div hours"><strong>Hours Worked:</strong> <?= htmlspecialchars($row["hours"]) ?></div>
                <div class="div hour input">
                    <form method="POST" action="">
                        <input type="hidden" name="hours_id" value="<?= htmlspecialchars($row['hours_id']) ?>">
                        <input type="number" name="new_hours" value="<?= htmlspecialchars($row['hours']) ?>" min="0" max="24" required>
                        <button type="submit" name="edit_hours">Edit</button>
                    </form>
                </div>
                <div class="div approve">
                    <form method="POST" action="">
                        <input type="hidden" name="hours_id" value="<?= htmlspecialchars($row['hours_id']) ?>">
                        <button type="submit" name="complete_status">Approved</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No records found.</p>
    <?php endif; ?>

    <!-- Download PDF Button -->
    <div style="margin:20px 0;">
        <a href="downloadPDF.php?filter=<?= urlencode($filter) ?>" class="download-button">
            Download PDF Rapport
        </a>
    </div>

</body>
</html>
