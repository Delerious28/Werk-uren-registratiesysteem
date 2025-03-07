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
    <h1>Klant Dashboard</h1>

    <div class="filter-links">
        <a href="?filter=day&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="<?php echo $filter === 'day' ? 'active' : ''; ?>">Per Dag</a>
        <a href="?filter=week&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="<?php echo $filter === 'week' ? 'active' : ''; ?>">Per Week</a>
        <a href="?filter=month&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="<?php echo $filter === 'month' ? 'active' : ''; ?>">Per Maand</a>
    </div>

    <div class="filters">
        <select id="bedrijfFilter" onchange="updateBedrijfFilter()">Filter
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
            <th class='status-th'>Status</th>
            <th>Tijd</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if (!empty($hoursData)) {
            foreach ($hoursData as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['hours_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . " " . htmlspecialchars($row['achternaam']) . "</td>";
                echo "<td>" . htmlspecialchars($row['bedrijfsnaam'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($row['projectnaam'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($row['hours']) . "</td>";
                echo "<td>
                    <select class='status-dropdown' data-hours-id='" . $row['hours_id'] . "'>
                        <option class='option-tag' value='Pending' " . ($row['accord'] === 'Pending' ? 'selected' : '') . ">Pending</option>
                        <option class='option-tag' value='Approved' " . ($row['accord'] === 'Approved' ? 'selected' : '') . ">Approved</option>
                        <option class='option-tag' value='Rejected' " . ($row['accord'] === 'Rejected' ? 'selected' : '') . ">Rejected</option>
                    </select>
                    </td>";
                echo "<td>" . date('H:i', strtotime($row['start_hours'])) . " - " . date('H:i', strtotime($row['eind_hours'])) . "</td>";
                echo "</tr>";
            }
        }
        ?>
        </tbody>
    </table>
</div>

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
