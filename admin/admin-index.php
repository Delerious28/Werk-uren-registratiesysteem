<?php
session_start();
require('../fpdf/fpdf.php');
include "../db/conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../inloggen.php");
    exit();
}

$user_id = htmlspecialchars($_SESSION['user_id'], ENT_QUOTES, 'UTF-8');
$user_name = htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8');

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'week';

if ($filter === 'maand') {
    $sql = "
        SELECT u.user_id, u.name, COALESCE(SUM(h.hours), 0) AS totaal
        FROM users u
        LEFT JOIN hours h ON u.user_id = h.user_id AND MONTH(h.date) = MONTH(CURDATE()) AND YEAR(h.date) = YEAR(CURDATE())
        WHERE u.role = 'user'
        GROUP BY u.user_id, u.name
        ORDER BY u.name ASC
    ";
} elseif ($filter === 'vandaag') {
    $sql = "
        SELECT u.user_id, u.name, COALESCE(SUM(h.hours), 0) AS totaal
        FROM users u
        LEFT JOIN hours h ON u.user_id = h.user_id AND DATE(h.date) = CURDATE()
        WHERE u.role = 'user'
        GROUP BY u.user_id, u.name
        ORDER BY u.name ASC
    ";
} else {
    $sql = "
        SELECT u.user_id, u.name,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 2 THEN h.hours ELSE 0 END), 0) AS Ma,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 3 THEN h.hours ELSE 0 END), 0) AS Di,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 4 THEN h.hours ELSE 0 END), 0) AS Wo,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 5 THEN h.hours ELSE 0 END), 0) AS Do,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 6 THEN h.hours ELSE 0 END), 0) AS Vr
        FROM users u
        LEFT JOIN hours h ON u.user_id = h.user_id AND YEARWEEK(h.date, 1) = YEARWEEK(CURDATE(), 1)
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
        <div class="header">
            <div class="name">Activiteiten Overzicht</div>
            <form method="GET" action="" class="filter-form">
                <label for="filter">Filter op:</label>
                <select name="filter" id="filter" onchange="this.form.submit()">
                    <option value="vandaag" <?= $filter === 'vandaag' ? 'selected' : '' ?>>Vandaag</option>
                    <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Week</option>
                    <option value="maand" <?= $filter === 'maand' ? 'selected' : '' ?>>Maand</option>
                </select>
            </form>
        </div>

        <table>
            <thead>
            <tr>
                <th>Naam</th>
                <?php if ($filter === 'week'): ?>
                    <th><span class="prev">&#9664;</span> Ma</th>
                    <th>Di</th>
                    <th>Wo</th>
                    <th>Do</th>
                    <th>Vr <span class="next">&#9654;</span></th>
                    <th>Totaal</th>
                <?php else: ?>
                    <th><span class="prev">&#9664;</span> Totaal <span class="next">&#9654;</span></th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row["name"]) ?></td>
                    <?php if ($filter === 'week'):
                        $total = $row["Ma"] + $row["Di"] + $row["Wo"] + $row["Do"] + $row["Vr"];
                        ?>
                        <td><?= htmlspecialchars($row["Ma"]) ?></td>
                        <td><?= htmlspecialchars($row["Di"]) ?></td>
                        <td><?= htmlspecialchars($row["Wo"]) ?></td>
                        <td><?= htmlspecialchars($row["Do"]) ?></td>
                        <td><?= htmlspecialchars($row["Vr"]) ?></td>
                        <td><strong><?= htmlspecialchars($total) ?> Totaal</strong></td>
                    <?php else: ?>
                        <td><?= htmlspecialchars($row["totaal"]) ?> totaal</td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
