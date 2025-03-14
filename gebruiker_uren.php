<?php
session_start();

// Als de gebruiker niet is ingelogd, doorverwijzen naar inlogpagina
if (!isset($_SESSION['user_id'])) {
    header("Location: inloggen.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Verkrijg de user_id van de ingelogde gebruiker

include 'db/conn.php';

$filter = $_GET['filter'] ?? 'day';
$selectedBedrijfsnaam = $_GET['bedrijfsnaam'] ?? '';  // Veranderde variabele naar bedrijfsnaam

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

// Pas de query aan zodat de gegevens alleen voor de ingelogde gebruiker worden opgehaald
$sql = "
    SELECT h.hours_id, h.date, u.name, u.achternaam, h.hours, h.accord, h.start_hours, h.eind_hours, 
           k.bedrijfnaam AS bedrijfsnaam, p.project_naam AS projectnaam
    FROM hours h
    JOIN users u ON h.user_id = u.user_id
    LEFT JOIN project p ON h.project_id = p.project_id
    LEFT JOIN klant k ON p.klant_id = k.klant_id
    WHERE h.user_id = :user_id AND h.date BETWEEN :start_date AND :end_date
";

if (!empty($selectedBedrijfsnaam)) {  // Filteren op bedrijfsnaam
    $sql .= " AND k.bedrijfnaam = :bedrijfsnaam";  // Gebruik bedrijfsnaam in de WHERE-clausule
}
$sql .= " ORDER BY h.date ASC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$params = ['user_id' => $user_id, 'start_date' => $start_date, 'end_date' => $end_date];
if (!empty($selectedBedrijfsnaam)) {
    $params['bedrijfsnaam'] = $selectedBedrijfsnaam;  // Voeg de geselecteerde bedrijfsnaam toe aan de parameters
}
$stmt->execute($params);
$hoursData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sqlTotal = "
    SELECT COUNT(*) FROM hours h
    JOIN users u ON h.user_id = u.user_id
    LEFT JOIN project p ON h.project_id = p.project_id
    LEFT JOIN klant k ON p.klant_id = k.klant_id
    WHERE h.user_id = :user_id AND h.date BETWEEN :start_date AND :end_date
";
if (!empty($selectedBedrijfsnaam)) {  // Filteren op bedrijfsnaam in de totalen
    $sqlTotal .= " AND k.bedrijfnaam = :bedrijfsnaam";
}
$stmtTotal = $pdo->prepare($sqlTotal);
$stmtTotal->execute($params);
$totalRecords = $stmtTotal->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$sqlBedrijven = "SELECT DISTINCT bedrijfnaam FROM klant ORDER BY bedrijfnaam ASC";  // Haal alle unieke bedrijfsnamen
$stmtBedrijven = $pdo->prepare($sqlBedrijven);
$stmtBedrijven->execute();
$bedrijven = $stmtBedrijven->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urenoverzicht</title>
    <link rel="stylesheet" href="css/gebruikers_uren.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

    <div class="container">
        <h1>Urenoverzicht</h1>

        <div class="filter-links">
            <a href="?filter=day&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="<?php echo $filter === 'day' ? 'active' : ''; ?>">Per Dag</a>
            <a href="?filter=week&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="<?php echo $filter === 'week' ? 'active' : ''; ?>">Per Week</a>
            <a href="?filter=month&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="<?php echo $filter === 'month' ? 'active' : ''; ?>">Per Maand</a>
        </div>

        <div class="filters">
            <select id="bedrijfFilter" onchange="updateBedrijfFilter()">
                <option value="">Alle Bedrijven</option>
                <?php
                foreach ($bedrijven as $bedrijf) {
                    $selected = ($bedrijf === $selectedBedrijfsnaam) ? 'selected' : '';
                    echo "<option value='$bedrijf' $selected>$bedrijf</option>";
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
                    <th>Bedrijfsnaam</th>
                    <th>Projectnaam</th>
                    <th>Uren</th>
                    <th>Status</th>
                    <th>Tijd</th>
                </tr>
            </thead>
            <tbody>
                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?>&filter=<?php echo urlencode($filter); ?>&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="prev <?php echo ($page <= 1) ? 'disabled' : ''; ?>" <?php echo ($page <= 1) ? 'aria-disabled="true"' : ''; ?>>&#8592;</a>
                    <a href="?page=<?php echo min($totalPages, $page + 1); ?>&filter=<?php echo urlencode($filter); ?>&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="next <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>" <?php echo ($page >= $totalPages) ? 'aria-disabled="true"' : ''; ?>>&#8594;</a>
                </div>

                <?php
                if (!empty($hoursData)) {
                    foreach ($hoursData as $row) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['hours_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . " " . htmlspecialchars($row['achternaam']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['bedrijfsnaam'] ?? 'N/A') . "</td>";  // Bedrijfsnaam weergeven
                        echo "<td>" . htmlspecialchars($row['projectnaam'] ?? 'N/A') . "</td>";  // Projectnaam weergeven
                        echo "<td>" . htmlspecialchars($row['hours']) . "</td>";
                        echo "<td class='status " . strtolower(htmlspecialchars($row['accord'])) . "'>" . htmlspecialchars($row['accord']) . "</td>";
                        echo "<td>" . date('H:i', strtotime($row['start_hours'])) . " - " . date('H:i', strtotime($row['eind_hours'])) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>Geen gegevens gevonden voor deze periode</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function updateBedrijfFilter() {
            let bedrijfsnaam = document.getElementById("bedrijfFilter").value;
            let urlParams = new URLSearchParams(window.location.search);
            urlParams.set("bedrijfsnaam", bedrijfsnaam);
            window.location.search = urlParams.toString();
        }
    </script>
</body>
</html>
