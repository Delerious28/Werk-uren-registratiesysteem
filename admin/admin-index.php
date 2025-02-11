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
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = date('Y');

if ($filter === 'maand') {
    $sql = "
        SELECT u.user_id, u.name, COALESCE(SUM(h.hours), 0) AS totaal
        FROM users u
        LEFT JOIN hours h ON u.user_id = h.user_id AND MONTH(h.date) = :month AND YEAR(h.date) = :year
        WHERE u.role = 'user'
        GROUP BY u.user_id, u.name
        ORDER BY u.name ASC
    ";
    $params = [':month' => $month, ':year' => $year];
} elseif ($filter === 'vandaag') {
    $sql = "
        SELECT u.user_id, u.name, COALESCE(SUM(h.hours), 0) AS totaal
        FROM users u
        LEFT JOIN hours h ON u.user_id = h.user_id AND DATE(h.date) = CURDATE()
        WHERE u.role = 'user'
        GROUP BY u.user_id, u.name
        ORDER BY u.name ASC
    ";
    $params = [];
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
    $params = [];
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
            <?php if ($filter === 'week'): ?>
            <div class="name">Week activiteiten</div>
            <?php elseif ($filter === 'maand'): ?>
                <div class="name">Maand activiteiten</div>
            <?php else: ?>
                <div class="name">Vandaag activiteiten</div>
            <?php endif; ?>
            <form method="GET" action="" class="filter-form">
                <select name="filter" id="filter" onchange="this.form.submit()">
                    <option value="vandaag" <?= $filter === 'vandaag' ? 'selected' : '' ?>>Vandaag</option>
                    <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Week</option>
                    <option value="maand" <?= $filter === 'maand' ? 'selected' : '' ?>>Maand</option>
                </select>
            </form>
        </div>

        <table>
            <thead>
            <tr class="week-tr">
                <?php if ($filter === 'week'): ?>
                    <th></th><th class="week-th">&#9664; Ma</th><th class="week-th">Di</th><th class="week-th">Wo</th><th class="week-th">Do</th><th class="week-th">Vr &#9654;</th>
                <?php elseif ($filter === 'maand'): ?>
                    <th></th></th><th class="month-th""><a class="arrow-left-month" href="?filter=maand&month=<?= $month-1 ?>">&#9664;</a> <?= date('F', mktime(0, 0, 0, $month, 10)) ?> <a class="arrow-right-month" href="?filter=maand&month=<?= $month+1 ?>">&#9654;</a></th>
                <?php else: ?>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><img src="user-icon.png" alt="icon"> <?= htmlspecialchars($row["name"]) ?></td>
                    <?php if ($filter === 'week'):
                        $total = $row["Ma"] + $row["Di"] + $row["Wo"] + $row["Do"] + $row["Vr"];
                        ?>
                        <td><?= htmlspecialchars($row["Ma"]) ?></td>
                        <td><?= htmlspecialchars($row["Di"]) ?></td>
                        <td><?= htmlspecialchars($row["Wo"]) ?></td>
                        <td><?= htmlspecialchars($row["Do"]) ?></td>
                        <td><?= htmlspecialchars($row["Vr"]) ?></td>
                        <td><strong><?= htmlspecialchars($total) ?> Totaal</strong></td>
                        <td class="action-icons">
                            <button class="edit">✏️</button>
                            <button class="accept">✅</button>
                        </td>
                    <?php else: ?>
                        <td><?= htmlspecialchars($row["totaal"]) ?> Totaal</td>
                        <td class="action-icons">
                            <button class="edit">✏️</button>
                            <button class="accept">✅</button>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
