<?php
session_start();
require('../fpdf/fpdf.php'); // Adjust the path if needed
include "../db/conn.php"; // Database Connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../inloggen.php");
    exit();
}

// Sanitize session data
$user_id = htmlspecialchars($_SESSION['user_id'], ENT_QUOTES, 'UTF-8');
$user_name = htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8');

// Handle filter selection
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'week'; // Default to 'week'

// Depending on the filter, build the query accordingly.
if ($filter === 'maand') {
    // For month, we only want to display the total hours for the current month.
    $sql = "
        SELECT 
            u.user_id,
            u.name,
            COALESCE(SUM(h.hours), 0) AS totaal
        FROM users u
        LEFT JOIN hours h 
            ON u.user_id = h.user_id 
            AND MONTH(h.date) = MONTH(CURDATE()) 
            AND YEAR(h.date) = YEAR(CURDATE())
        WHERE u.role = 'user'
        GROUP BY u.user_id, u.name
        ORDER BY u.name ASC
    ";
} else {
    // For 'vandaag' (today) or 'week' filters, use the daily.
    $on_date_condition = "";
    if ($filter === 'vandaag') {
        $on_date_condition = "AND DATE(h.date) = CURDATE()";
    } elseif ($filter === 'week') {
        $on_date_condition = "AND YEARWEEK(h.date, 1) = YEARWEEK(CURDATE(), 1)";
    }

    $sql = "
        SELECT 
            u.user_id,
            u.name,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 2 THEN h.hours ELSE 0 END), 0) AS Ma,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 3 THEN h.hours ELSE 0 END), 0) AS Di,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 4 THEN h.hours ELSE 0 END), 0) AS Wo,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 5 THEN h.hours ELSE 0 END), 0) AS Do,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 6 THEN h.hours ELSE 0 END), 0) AS Vr
        FROM users u
        LEFT JOIN hours h ON u.user_id = h.user_id $on_date_condition
        WHERE u.role = 'user'
        GROUP BY u.user_id, u.name
        ORDER BY u.name ASC
    ";
}

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error retrieving data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin-index.css">
</head>
<body>
<div class="container">
    <div class="sidebar">
        <div class="menu-item active"><a href="admin-index.php">Dashboard</a></div>
        <div class="menu-item"><a href="admin-download.php">Download</a></div>
        <div class="menu-item"><a href="admin-gebruikers.php">Gebruikers</a></div>
        <div class="menu-item"><a href="../uitloggen.php">Uitloggen</a></div>
    </div>
    <div class="content">
        <h1>Week activiteiten</h1>

        <!-- Filter Form -->
        <form method="GET" action="" class="filter-form">
            <label for="filter">Filter op:</label>
            <select name="filter" id="filter" onchange="this.form.submit()">
                <option value="vandaag" <?= $filter === 'vandaag' ? 'selected' : '' ?>>Vandaag</option>
                <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Week</option>
                <option value="maand" <?= $filter === 'maand' ? 'selected' : '' ?>>Maand</option>
            </select>
        </form>
        <!--  filtering by month, display Name and Total -->
        <?php if ($filter === 'maand'): ?>
            <table>
                <tr>
                    <th>Naam</th>
                    <th>Totaal</th>
                </tr>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["name"]) ?></td>
                        <td><?= htmlspecialchars($row["totaal"]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <!-- When filtering by today or week -->
            <table>
                <tr>
                    <th>Naam</th>
                    <th>Ma</th>
                    <th>Di</th>
                    <th>Wo</th>
                    <th>Do</th>
                    <th>Vr</th>
                    <th>Totaal</th>
                </tr>
                <?php foreach ($rows as $row): ?>
                    <?php
                    // Calculate total hours for the week
                    $total = $row["Ma"] + $row["Di"] + $row["Wo"] + $row["Do"] + $row["Vr"];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row["name"]) ?></td>
                        <td><?= htmlspecialchars($row["Ma"]) ?></td>
                        <td><?= htmlspecialchars($row["Di"]) ?></td>
                        <td><?= htmlspecialchars($row["Wo"]) ?></td>
                        <td><?= htmlspecialchars($row["Do"]) ?></td>
                        <td><?= htmlspecialchars($row["Vr"]) ?></td>
                        <td><strong><?= htmlspecialchars($total) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
