<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: inloggen.php");
    exit();
}

try {
    include 'db/conn.php';
} catch (PDOException $e) {
    die("Databaseverbinding mislukt: " . $e->getMessage());
}

// Filters en paginering
$filter = $_GET['filter'] ?? 'day';
$selectedBedrijfsnaam = $_GET['bedrijfsnaam'] ?? '';
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Datumbereik bepaling
$dateRange = [
    'day' => [date('Y-m-d'), date('Y-m-d')],
    'week' => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))],
    'month' => [date('Y-m-01'), date('Y-m-t')]
];
[$start_date, $end_date] = $dateRange[$filter] ?? $dateRange['day'];

try {
    // Hoofdquery voor urengegevens
    $sql = "SELECT h.hours_id, h.date, u.name, u.achternaam, h.hours, h.start_hours, h.eind_hours,
                   k.bedrijfnaam AS bedrijfsnaam, p.project_naam AS projectnaam 
            FROM hours h
            JOIN users u ON h.user_id = u.user_id
            LEFT JOIN project p ON h.project_id = p.project_id
            LEFT JOIN klant k ON p.klant_id = k.klant_id
            WHERE h.date BETWEEN :start_date AND :end_date"
            . (!empty($selectedBedrijfsnaam) ? " AND k.bedrijfnaam = :bedrijfsnaam" : "");

    $stmt = $pdo->prepare($sql . " ORDER BY h.date ASC LIMIT :limit OFFSET :offset");

    $params = ['start_date' => $start_date, 'end_date' => $end_date];
    if (!empty($selectedBedrijfsnaam)) $params['bedrijfsnaam'] = $selectedBedrijfsnaam;

    foreach ($params as $key => $val) $stmt->bindValue(":$key", $val);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $hoursData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Bedrijfsfilter
    $bedrijven = $pdo->query("SELECT DISTINCT bedrijfnaam FROM klant ORDER BY bedrijfnaam ASC")
                     ->fetchAll(PDO::FETCH_COLUMN);

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
    <style>
    .klant-success-mess {
        background-color: #d4edda;
        color: #155724;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #c3e6cb;
        border-radius: 4px;
    }
    .klant-fail-mess {
        background-color: #f8d7da;
        color: #721c24;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #f5c6cb;
        border-radius: 4px;
    }
    .status-dropdown {
        padding: 5px;
        border-radius: 4px;
        border: 1px solid #ccc;
        color: black; /* Tekstkleur altijd zwart */
        background-color: white; /* Standaard achtergrondkleur */
    }
    .status-dropdown.approved {
        background-color: #d4edda; /* Lichtgroen voor Approved */
    }
    .status-dropdown.rejected {
        background-color: #f8d7da; /* Lichtrood voor Rejected */
    }
    .status-dropdown.pending {
        background-color: #fff3cd; /* Lichtgeel voor Pending */
    }
    .status-dropdown option {
        color: black; /* Tekstkleur altijd zwart */
        background-color: white; /* Achtergrondkleur altijd wit */
    }
    .status-dropdown option:hover {
        background-color: #f0f0f0; /* Grijs bij hover */
    }
    .status-dropdown option:checked {
        background-color: #e0e0e0; /* Lichtgrijs voor geselecteerde optie */
    }
</style>
<body>
<?php include 'sidebar.php'; ?>

<div class="container">
    <h1>Klant Dashboard</h1>

    <div class="filter-links">
        <a href="?filter=day&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="<?php echo $filter === 'day' ? 'active' : ''; ?>">Per Dag</a>
        <a href="?filter=week&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="<?php echo $filter === 'week' ? 'active' : ''; ?>">Per Week</a>
        <a href="?filter=month&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="<?php echo $filter === 'month' ? 'active' : ''; ?>">Per Maand</a>
    </div>

    <div class="filters">
        <select id="bedrijfFilter" onchange="updateBedrijfFilter()">
            <option value="">Alle Bedrijven</option>
            <?php foreach ($bedrijven as $bedrijf): ?>
                <option value="<?php echo htmlspecialchars($bedrijf); ?>" <?php echo $bedrijf === $selectedBedrijfsnaam ? 'selected' : ''; ?>><?php echo htmlspecialchars($bedrijf); ?></option>
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
            <th class="status-th">Status</th>
            <th>Tijd</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($hoursData)): ?>
            <tr>
                <td colspan="8">Geen gegevens gevonden.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($hoursData as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['hours_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['name'] . ' ' . $row['achternaam']); ?></td>
                    <td><?php echo htmlspecialchars($row['bedrijfsnaam'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['projectnaam'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['hours']); ?></td>
                    <td>
                        <select class="status-dropdown" data-hours-id="<?php echo htmlspecialchars($row['hours_id']); ?>">
                            <option class="option-tag" value="Pending">Pending</option>
                            <option class="option-tag" value="Approved">Approved</option>
                            <option class="option-tag" value="Rejected">Rejected</option>
                        </select>
                    </td>
                    <td><?php echo date('H:i', strtotime($row['start_hours'])) . ' - ' . date('H:i', strtotime($row['eind_hours'])); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const statusDropdowns = document.querySelectorAll('.status-dropdown');

        statusDropdowns.forEach(function (dropdown) {
            const savedStatus = localStorage.getItem(`status-${dropdown.getAttribute('data-hours-id')}`);
            if (savedStatus) {
                dropdown.value = savedStatus;
                updateDropdownColor(dropdown, savedStatus); // Pas de kleur aan bij het laden
            }

            dropdown.addEventListener('change', function () {
                const hoursId = this.getAttribute('data-hours-id');
                const status = this.value;

                // Update de kleur van de dropdown
                updateDropdownColor(dropdown, status);

                // Simuleer een succesvolle update
                localStorage.setItem(`status-${hoursId}`, status);
                showSuccessMessage('Status succesvol bijgewerkt! (lokaal opgeslagen)');
            });
        });

        function updateDropdownColor(dropdown, status) {
            // Verwijder alle bestaande kleurklassen
            dropdown.classList.remove('approved', 'rejected', 'pending');

            // Voeg de juiste klasse toe op basis van de status
            if (status === 'Approved') {
                dropdown.classList.add('approved');
            } else if (status === 'Rejected') {
                dropdown.classList.add('rejected');
            } else if (status === 'Pending') {
                dropdown.classList.add('pending');
            }
        }

        function showSuccessMessage(message) {
            const successMessage = document.createElement('div');
            successMessage.classList.add('klant-success-mess');
            successMessage.textContent = message;
            document.querySelector('.container').prepend(successMessage);
            setTimeout(() => successMessage.remove(), 3000);
        }

        function showFailMessage(message) {
            const failMessage = document.createElement('div');
            failMessage.classList.add('klant-fail-mess');
            failMessage.textContent = message;
            document.querySelector('.container').prepend(failMessage);
            setTimeout(() => failMessage.remove(), 3000);
        }
    });

    function updateBedrijfFilter() {
        let bedrijfsnaam = document.getElementById("bedrijfFilter").value;
        let urlParams = new URLSearchParams(window.location.search);
        urlParams.set("bedrijfsnaam", bedrijfsnaam);
        window.location.search = urlParams.toString();
    }
</script>
</body>
</html>