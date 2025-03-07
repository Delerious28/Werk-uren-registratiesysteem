<?php
session_start();

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Databaseverbinding
try {
    include 'db/conn.php';
} catch (PDOException $e) {
    die("Databaseverbinding mislukt: " . $e->getMessage());
}

// Filters en paginering
$filter = $_GET['filter'] ?? 'day';
$selectedBedrijfsnaam = $_GET['bedrijfsnaam'] ?? '';
$limit = 10;
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$offset = ($page - 1) * $limit;

// Datumbereik bepalen
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

try {
    // SQL-query om alle uren op te halen binnen het datumbereik
    $sql = "SELECT h.hours_id, h.date, u.name, u.achternaam, h.hours, h.accord, h.start_hours, h.eind_hours, k.bedrijfnaam AS bedrijfsnaam, p.project_naam AS projectnaam 
            FROM hours h 
            JOIN users u ON h.user_id = u.user_id 
            LEFT JOIN project p ON h.project_id = p.project_id 
            LEFT JOIN klant k ON p.klant_id = k.klant_id 
            WHERE h.date BETWEEN :start_date AND :end_date";

    $params = ['start_date' => $start_date, 'end_date' => $end_date];

    if (!empty($selectedBedrijfsnaam)) {
        $sql .= " AND k.bedrijfnaam = :bedrijfsnaam";
        $params['bedrijfsnaam'] = $selectedBedrijfsnaam;
    }

    $sql .= " ORDER BY h.date ASC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind de parameters
    $stmt->bindValue(':start_date', $start_date);
    $stmt->bindValue(':end_date', $end_date);
    if (isset($params['bedrijfsnaam'])) {
        $stmt->bindValue(':bedrijfsnaam', $params['bedrijfsnaam']);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $hoursData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // SQL-query voor het tellen van totale records
    $sqlTotal = "SELECT COUNT(*) FROM hours h LEFT JOIN project p ON h.project_id = p.project_id LEFT JOIN klant k ON p.klant_id = k.klant_id WHERE h.date BETWEEN :start_date AND :end_date";
    if (!empty($selectedBedrijfsnaam)) {
        $sqlTotal .= " AND k.bedrijfnaam = :bedrijfsnaam";
    }

    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute($params);
    $totalRecords = $stmtTotal->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // SQL-query voor het ophalen van unieke bedrijfsnamen
    $sqlBedrijven = "SELECT DISTINCT bedrijfnaam FROM klant ORDER BY bedrijfnaam ASC";
    $stmtBedrijven = $pdo->prepare($sqlBedrijven);
    $stmtBedrijven->execute();
    $bedrijven = $stmtBedrijven->fetchAll(PDO::FETCH_COLUMN);

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
    <h1>Urenoverzicht</h1>

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
                            <option class="option-tag" value="Pending" <?php echo $row['accord'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option class="option-tag" value="Approved" <?php echo $row['accord'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option<option class="option-tag" value="Rejected" <?php echo $row['accord'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </td>
                    <td><?php echo date('H:i', strtotime($row['start_hours'])) . ' - ' . date('H:i', strtotime($row['eind_hours'])); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Haal de dropdowns op voor de status
        const statusDropdowns = document.querySelectorAll('.status-dropdown');

        // Loop door de dropdowns en voeg een event listener toe
        statusDropdowns.forEach(function (dropdown) {
            // Lees de opgeslagen status uit localStorage en pas de achtergrondkleur aan
            const savedStatus = localStorage.getItem(`status-${dropdown.getAttribute('data-hours-id')}`);
            if (savedStatus) {
                applyBackgroundColor(dropdown, savedStatus);
            }

            dropdown.addEventListener('change', function () {
                const hoursId = this.getAttribute('data-hours-id');  // Haal het hours_id uit de dropdown
                const status = this.value;  // Haal de geselecteerde status op

                // Stuur een AJAX-verzoek naar de update-status.php
                fetch('update-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        hours_id: hoursId,
                        accord: status
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Server response:', data); // Log de server response
                        if (data.status === 'success') {
                            // Succesbericht weergeven
                            showSuccessMessage('Status succesvol bijgewerkt!');
                        } else {
                            showFailMessage('Status kon niet bijgewerkt worden!');
                        }

                        // Sla de status op in localStorage
                        localStorage.setItem(`status-${hoursId}`, status);
                        applyBackgroundColor(dropdown, status);  // Pas de achtergrondkleur toe
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Er is een fout opgetreden');
                    });
            });
        });

        // Functie om de achtergrondkleur toe te passen op de dropdown
        function applyBackgroundColor(dropdown, status) {
            if (status === 'Pending') {
                dropdown.style.backgroundColor = '#fff3cd';  // Geel voor Pending
                dropdown.style.color = 'black';  // Zwarte tekst voor de gehele select
                // Specifieke styling voor option tags
                const options = dropdown.querySelectorAll('option');
                options.forEach(option => {
                    option.style.color = 'black';  // Zorg ervoor dat alle options zwarte tekst hebben
                });
            } else if (status === 'Approved') {
                dropdown.style.backgroundColor = '#155724';  // Groen voor Approved
                dropdown.style.color = 'white';  // Witte tekst voor de gehele select
                const options = dropdown.querySelectorAll('option');
                options.forEach(option => {
                    option.style.color = 'black';  // Zwarte tekst voor de options
                });
            } else if (status === 'Rejected') {
                dropdown.style.backgroundColor = '#F44336';  // Rood voor Rejected
                dropdown.style.color = 'white';  // Witte tekst voor de gehele select
                const options = dropdown.querySelectorAll('option');
                options.forEach(option => {
                    option.style.color = 'black';  // Zwarte tekst voor de options
                });
            }
        }

        // Functie voor succesbericht
        function showSuccessMessage(message) {
            // Maak een div voor het succesbericht
            const successMessage = document.createElement('div');
            successMessage.classList.add('klant-success-mess');
            successMessage.textContent = message;

            // Voeg het bericht toe aan de container
            const container = document.querySelector('.container');
            container.prepend(successMessage);

            // Verwijder het bericht na 5 seconden
            setTimeout(() => {
                successMessage.remove();
            }, 3000);
        }

        // Functie voor foutbericht
        function showFailMessage(message) {
            // Maak een div voor het foutbericht
            const failMessage = document.createElement('div');
            failMessage.classList.add('klant-fail-mess');
            failMessage.textContent = message;

            // Voeg het bericht toe aan de container
            const container = document.querySelector('.container');
            container.prepend(failMessage);

            // Verwijder het bericht na 5 seconden
            setTimeout(() => {
                failMessage.remove();
            }, 3000);
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