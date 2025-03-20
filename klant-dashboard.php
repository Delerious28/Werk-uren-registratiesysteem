<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: inloggen.php");
    exit();
}

require_once 'db/conn.php';

// Functie om de status in de database bij te werken
function updateStatus($pdo, $hoursId, $status) {
    try {
        $stmt = $pdo->prepare("UPDATE hours SET accord = :status WHERE hours_id = :hours_id");
        $stmt->execute(['status' => $status, 'hours_id' => $hoursId]);
        return true;
    } catch (PDOException $e) {
        error_log("Fout bij het bijwerken van de status: " . $e->getMessage());
        return false;
    }
}

// Verwerk statusupdates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hours_id']) && isset($_POST['status'])) {
    $hoursId = $_POST['hours_id'];
    $status = $_POST['status'];
    if (updateStatus($pdo, $hoursId, $status)) {
        echo json_encode(['success' => true, 'message' => 'Status succesvol bijgewerkt!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fout bij het bijwerken van de status.']);
    }
    exit; // Stop verdere verwerking
}

// Filters en paginering
$filter = $_GET['filter'] ?? 'day';
$selectedUserId = $_GET['user_id'] ?? '';

// Basis SQL-query met user_id toegevoegd
$sql = "SELECT u.user_id, h.hours_id, h.date, u.name, u.achternaam, h.hours, 
               h.start_hours, h.eind_hours, h.accord,
               k.bedrijfnaam AS bedrijfsnaam, p.project_naam AS projectnaam 
        FROM hours h
        JOIN users u ON h.user_id = u.user_id
        LEFT JOIN project p ON h.project_id = p.project_id
        LEFT JOIN klant k ON p.klant_id = k.klant_id
        WHERE h.date BETWEEN :start_date AND :end_date";

// Datumbereik bepaling
$dateRange = [
    'day'   => [date('Y-m-d'), date('Y-m-d')],
    'week'  => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))],
    'month' => [date('Y-m-01'), date('Y-m-t')]
];
[$start_date, $end_date] = $dateRange[$filter] ?? $dateRange['day'];

// Parameters voor de query
$params = ['start_date' => $start_date, 'end_date' => $end_date];
if (!empty($selectedUserId)) {
    $sql .= " AND u.user_id = :user_id";
    $params['user_id'] = $selectedUserId;
}

$limit = 7;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

try {
    // Query voor urengegevens, inclusief eventuele filters
    $stmt = $pdo->prepare($sql . " ORDER BY h.date ASC LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $val) {
        $stmt->bindValue(":$key", $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $hoursData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gebruikersfilter ophalen
    $gebruikers = $pdo->query("SELECT user_id, name, achternaam FROM users WHERE role = 'user' ORDER BY name ASC")
        ->fetchAll(PDO::FETCH_ASSOC);

    // Haal het totaal aantal records op
    $sqlTotal = "SELECT COUNT(*) FROM hours h
                 JOIN users u ON h.user_id = u.user_id
                 LEFT JOIN project p ON h.project_id = p.project_id
                 LEFT JOIN klant k ON p.klant_id = k.klant_id
                 WHERE h.date BETWEEN :start_date AND :end_date" . 
                (!empty($selectedUserId) ? " AND u.user_id = :user_id" : "");
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute($params);
    $totalRecords = $stmtTotal->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
} catch (PDOException $e) {
    die("Fout bij het ophalen van gegevens: " . $e->getMessage());
}
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
    <h1>Dashboard</h1>
 
    <div class="filter-links">
        <a href="?filter=day&user_id=<?php echo urlencode($selectedUserId); ?>" class="<?php echo $filter === 'day' ? 'active' : ''; ?>">Per Dag</a>
        <a href="?filter=week&user_id=<?php echo urlencode($selectedUserId); ?>" class="<?php echo $filter === 'week' ? 'active' : ''; ?>">Per Week</a>
        <a href="?filter=month&user_id=<?php echo urlencode($selectedUserId); ?>" class="<?php echo $filter === 'month' ? 'active' : ''; ?>">Per Maand</a>
    </div>

    <div class="filters">
        <select id="gebruikerFilter" onchange="updateGebruikerFilter()">
            <option value="">Alle Gebruikers</option>
            <?php foreach ($gebruikers as $gebruiker): ?>
                <option value="<?php echo htmlspecialchars($gebruiker['user_id']); ?>"
                    <?php echo $gebruiker['user_id'] == ($selectedUserId) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($gebruiker['name'] . ' ' . $gebruiker['achternaam']); ?>
                </option>
            <?php endforeach; ?>
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
            <th class="status-th">
                Status
                <button id="dropdown-btn" class="dropdown-btn">▼</button>
                <div id="dropdown-content" style="display: none;">
                    <div class="grid-dropdown">
                        <select id="month-select" class="month-select">
                            <!-- Dynamisch toe te voegen maanden -->
                        </select>
                        <button class="approve-month">Accordeer</button>
                    </div>
                </div>
            </th>
            <th>Tijd</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($hoursData)): ?>
            <tr><td colspan="8">Geen gegevens gevonden.</td></tr>
        <?php else: ?>
            <?php foreach ($hoursData as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['hours_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td class="view-user-profile" data-user-id="<?php echo htmlspecialchars($row['user_id']); ?>">
                        <?php echo htmlspecialchars($row['name'] . ' ' . $row['achternaam']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['bedrijfsnaam'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['projectnaam'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['hours']); ?></td>
                    <td class="select-td">
                        <select class="status-dropdown <?php echo strtolower($row['accord']); ?>" data-hours-id="<?php echo htmlspecialchars($row['hours_id']); ?>">
                            <option value="Pending" <?php echo $row['accord'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo $row['accord'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo $row['accord'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </td>
                    <td><?php echo date('H:i', strtotime($row['start_hours'])) . ' - ' . date('H:i', strtotime($row['eind_hours'])); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <a href="?page=<?php echo max(1, $page - 1); ?>&filter=<?php echo urlencode($filter); ?>&user_id=<?php echo urlencode($selectedUserId); ?>" class="prev <?php echo ($page <= 1) ? 'disabled' : ''; ?>" <?php echo ($page <= 1) ? 'aria-disabled="true"' : ''; ?>>&#8592;</a>
        <a href="?page=<?php echo min($totalPages, $page + 1); ?>&filter=<?php echo urlencode($filter); ?>&user_id=<?php echo urlencode($selectedUserId); ?>" class="next <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>" <?php echo ($page >= $totalPages) ? 'aria-disabled="true"' : ''; ?>>&#8594;</a>
    </div>
</div>

<!-- Pop-up voor het tonen van gebruikersgegevens -->
<div id="gebruikerPopup" class="gebruiker-popup">
    <div class="popup-content">
        <span class="close-popup">&times;</span>
        <h3>Gebruikersprofiel</h3>
        <p><strong>Naam:</strong> <span id="popup-name"></span></p>
        <p><strong>Bedrijf:</strong> <span id="popup-bedrijf"></span></p>
        <p><strong>Project:</strong> <span id="popup-project"></span></p>
        <p><strong>Gewerkte uren:</strong> <span id="popup-uren"></span></p>
        <p><strong>Tijd:</strong> <span id="popup-tijd"></span></p>
    </div>
</div>

<script src="js/klant-dashboard.js"></script>

</body>
</html>
