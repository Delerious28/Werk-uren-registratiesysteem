<?php
include 'db/conn.php';

$filter = $_GET['filter'] ?? 'day';
$selectedAchternaam = $_GET['achternaam'] ?? '';

$today = date('Y-m-d');
$start_date = $today;
$end_date = $today;

switch ($filter) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
}

$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); 
$offset = ($page - 1) * $limit;

$sql = "
    SELECT h.hours_id, h.date, u.name, u.achternaam, h.hours, h.accord, h.start_hours, h.eind_hours
    FROM hours h
    JOIN users u ON h.user_id = u.user_id
    WHERE h.date BETWEEN :start_date AND :end_date
";
if (!empty($selectedAchternaam)) {
    $sql .= " AND u.achternaam = :achternaam";
}
$sql .= " ORDER BY h.date ASC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$params = ['start_date' => $start_date, 'end_date' => $end_date];
if (!empty($selectedAchternaam)) {
    $params['achternaam'] = $selectedAchternaam;
}
$stmt->execute($params);
$hoursData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sqlTotal = "
    SELECT COUNT(*) FROM hours h
    JOIN users u ON h.user_id = u.user_id
    WHERE h.date BETWEEN :start_date AND :end_date
";
if (!empty($selectedAchternaam)) {
    $sqlTotal .= " AND u.achternaam = :achternaam";
}
$stmtTotal = $pdo->prepare($sqlTotal);
$stmtTotal->execute($params);
$totalRecords = $stmtTotal->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$sqlLastNames = "SELECT DISTINCT achternaam FROM users ORDER BY achternaam ASC";
$stmtLastNames = $pdo->prepare($sqlLastNames);
$stmtLastNames->execute();
$lastNames = $stmtLastNames->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urenregistratie</title>
    <link rel="stylesheet" href="css/gebruikers_uren.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

    <div class="container">
        <h1>Urenregistratie</h1>

        <div class="filter-links">
            <a href="?filter=day&achternaam=<?php echo urlencode($selectedAchternaam); ?>" class="<?php echo $filter === 'day' ? 'active' : ''; ?>">Per Dag</a>
            <a href="?filter=week&achternaam=<?php echo urlencode($selectedAchternaam); ?>" class="<?php echo $filter === 'week' ? 'active' : ''; ?>">Per Week</a>
            <a href="?filter=month&achternaam=<?php echo urlencode($selectedAchternaam); ?>" class="<?php echo $filter === 'month' ? 'active' : ''; ?>">Per Maand</a>
        </div>

        <div class="filters">
    <select id="achternaamFilter" onchange="updateAchternaamFilter()">
        <option value="">Alle Gebruikers</option>
        <?php
        foreach ($lastNames as $lastName) {
            $selected = ($lastName === $selectedAchternaam) ? 'selected' : '';
            echo "<option value='$lastName' $selected>$lastName</option>";
        }
        ?>
    </select>
</div>

        <table id="urenTabel">
            <thead>
                <tr>
                    <th>Records</th>
                    <th>Datum</th>
                    <th>Medewerker</th>
                    <th>Uren</th>
                    <th>Status</th>
                    <th>Tijd</th>
                </tr>
            </thead>
            <tbody>
                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?>&filter=<?php echo urlencode($filter); ?>&achternaam=<?php echo urlencode($selectedAchternaam); ?>" class="prev <?php echo ($page <= 1) ? 'disabled' : ''; ?>" <?php echo ($page <= 1) ? 'aria-disabled="true"' : ''; ?>>&#8592;</a>
                    <a href="?page=<?php echo min($totalPages, $page + 1); ?>&filter=<?php echo urlencode($filter); ?>&achternaam=<?php echo urlencode($selectedAchternaam); ?>" class="next <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>" <?php echo ($page >= $totalPages) ? 'aria-disabled="true"' : ''; ?>>&#8594;</a>
                </div>

                <?php
                if (!empty($hoursData)) {
                    foreach ($hoursData as $row) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['hours_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . " " . htmlspecialchars($row['achternaam']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['hours']) . "</td>";
                        echo "<td class='status " . strtolower(htmlspecialchars($row['accord'])) . "'>" . htmlspecialchars($row['accord']) . "</td>";
                        echo "<td>" . date('H:i', strtotime($row['start_hours'])) . " - " . date('H:i', strtotime($row['eind_hours'])) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>Geen gegevens gevonden voor deze periode</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function updateAchternaamFilter() {
            let achternaam = document.getElementById("achternaamFilter").value;
            let urlParams = new URLSearchParams(window.location.search);
            urlParams.set("achternaam", achternaam);
            window.location.search = urlParams.toString();
        }
    </script>
</body>
</html>
