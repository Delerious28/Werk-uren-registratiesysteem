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
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$week = isset($_GET['week']) ? (int)$_GET['week'] : date('W');

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
        LEFT JOIN hours h ON u.user_id = h.user_id AND YEARWEEK(h.date, 1) = YEARWEEK(STR_TO_DATE(CONCAT(:year, '-', :week, ' Monday'), '%X-%V %W'), 1)
        WHERE u.role = 'user'
        GROUP BY u.user_id, u.name
        ORDER BY u.name ASC
    ";
    $params = [':year' => $year, ':week' => $week];
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
    <link rel="stylesheet" href="../css/admin-index.css">
</head>
<body>
<div class="container">

<?php include 'admin-header.php' ?>

    <div class="content">
        <div class="dateNfilter-header <?= 'filter-' . $filter ?>">
            <?php if ($filter === 'week'): ?>
                <div class="name">Week activiteiten</div>
                <div class="huidige-week-weergave">Week <?= $week ?></div>
            <?php elseif ($filter === 'maand'): ?>
                <div class="name">Maand activiteiten</div>

                <div class="admin-maand-navigatie">
                    <div class="month-th"></div>
                    <div class="month-th">
                        <a class="arrow-left-month" href="?filter=maand&month=<?= $month-1 ?>">
                            <img src="../img/links-pijl.png" alt="links" class="linkere-pijl-maanden">
                        </a>
                        <div class="maand-text">
                        <?= date('F', mktime(0, 0, 0, $month, 10)) ?>
                        </div>
                        <a class="arrow-right-month" href="?filter=maand&month=<?= $month+1 ?>">
                            <img src="../img/rechts-pijl.png" alt="links" class="rechtere-pijl-maanden">
                        </a>
                    </div>
                    <div class="month-th"></div>
                </div>
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

        <div>
        <?php if ($filter === 'week'): ?>
            <div class="week-tr">
                <?php
                $prev_week = $week - 1;
                $next_week = $week + 1;
                $prev_year = $year;
                $next_year = $year;

                if ($prev_week < 1) {
                    $prev_week = 52;
                    $prev_year--;
                }
                if ($next_week > 52) {
                    $next_week = 1;
                    $next_year++;
                }
                ?>
                <div class="linkere-pijl-th">
                    <a href="?filter=week&year=<?= $prev_year ?>&week=<?= $prev_week ?>">
                        <img src="../img/links-pijl.png">
                    </a>
                </div>
                <div class="week-th">Ma</div>
                <div class="week-th">Di</div>
                <div class="week-th">Wo</div>
                <div class="week-th">Do</div>
                <div class="week-th">Vr</div>
                <div class="rechtere-pijl-th">
                    <a href="?filter=week&year=<?= $next_year ?>&week=<?= $next_week ?>">
                        <img src="../img/rechts-pijl.png">
                    </a>
                </div>
            </div>

        <?php elseif ($filter === 'maand'): ?><?php endif; ?>
        </div>

        <table class="tabel-content" data-filter="<?= $filter ?>">
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="naamNuser-icon"><img src="../img/user-icon.png" alt="icon" class="user-icon"> <?= htmlspecialchars($row["name"]) ?></td>
                    <?php if ($filter === 'week'):
                        $total = $row["Ma"] + $row["Di"] + $row["Wo"] + $row["Do"] + $row["Vr"];
                        ?>
                        <td class="uren-row"><?= htmlspecialchars($row["Ma"]) ?></td>
                        <td class="uren-row"><?= htmlspecialchars($row["Di"]) ?></td>
                        <td class="uren-row"><?= htmlspecialchars($row["Wo"]) ?></td>
                        <td class="uren-row"><?= htmlspecialchars($row["Do"]) ?></td>
                        <td class="uren-row"><?= htmlspecialchars($row["Vr"]) ?></td>
                        <td class="totaal-week-end"><strong><?= htmlspecialchars($total) ?> Totaal</strong></td>
                        <td class="action-icons">
                            <button title="Wijzigen">✏️</button>
                            <button><img class="action-pngs" src="../img/checkmark.png" title="Accorderen"></button>
                        </td>
                    <?php else: ?>
                        <td><?= htmlspecialchars($row["totaal"]) ?> Totaal</td>
                        <td class="action-icons">
                            <button title="Wijzigen">✏️</button>
                            <button><img class="action-pngs" src="../img/checkmark.png" title="Accorderen"> </button>
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

