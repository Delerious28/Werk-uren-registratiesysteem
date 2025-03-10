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

// Voeg de basis SQL-query toe voordat je de filters toepast
$sql = "SELECT h.hours_id, h.date, u.name, u.achternaam, h.hours, h.start_hours, h.eind_hours, h.accord,
                k.bedrijfnaam AS bedrijfsnaam, p.project_naam AS projectnaam 
        FROM hours h
        JOIN users u ON h.user_id = u.user_id
        LEFT JOIN project p ON h.project_id = p.project_id
        LEFT JOIN klant k ON p.klant_id = k.klant_id
        WHERE h.date BETWEEN :start_date AND :end_date";

// Voeg de extra WHERE-filter toe als een gebruiker is geselecteerd
if (!empty($selectedUserId)) {
    $sql .= " AND u.user_id = :user_id";
    $params['user_id'] = $selectedUserId;
}

$limit = 7;
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
    // Query voor urengegevens, inclusief eventuele filters
    $stmt = $pdo->prepare($sql . " ORDER BY h.date ASC LIMIT :limit OFFSET :offset");

    $params = ['start_date' => $start_date, 'end_date' => $end_date];
    if (!empty($selectedUserId)) $params['user_id'] = $selectedUserId;

    foreach ($params as $key => $val) $stmt->bindValue(":$key", $val);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $hoursData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gebruikersfilter ophalen
    $gebruikers = $pdo->query("SELECT user_id, name, achternaam FROM users WHERE role = 'user' ORDER BY name ASC")
        ->fetchAll(PDO::FETCH_ASSOC);

    // Haal het totaal aantal records
    $sqlTotal = "
        SELECT COUNT(*) FROM hours h
        JOIN users u ON h.user_id = u.user_id
        LEFT JOIN project p ON h.project_id = p.project_id
        LEFT JOIN klant k ON p.klant_id = k.klant_id
        WHERE h.date BETWEEN :start_date AND :end_date"
        . (!empty($selectedUserId) ? " AND u.user_id = :user_id" : "");

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
    <h1>Klant Dashboard</h1>

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
                    <?php echo $gebruiker['user_id'] == ($_GET['user_id'] ?? '') ? 'selected' : ''; ?>>
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
                        <!-- De maanden worden hier dynamisch toegevoegd -->
                    </select>
                    <button class="approve-month">Accordeer</button>
                    </div>
                </div>
            </th>
            <th>Tijd</th>
        </tr>
        </thead>
        <tbody>

        <div class="pagination">
            <a href="?page=<?php echo max(1, $page - 1); ?>&filter=<?php echo urlencode($filter); ?>&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="prev <?php echo ($page <= 1) ? 'disabled' : ''; ?>" <?php echo ($page <= 1) ? 'aria-disabled="true"' : ''; ?>>&#8592;</a>
            <a href="?page=<?php echo min($totalPages, $page + 1); ?>&filter=<?php echo urlencode($filter); ?>&bedrijfsnaam=<?php echo urlencode($selectedBedrijfsnaam); ?>" class="next <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>" <?php echo ($page >= $totalPages) ? 'aria-disabled="true"' : ''; ?>>&#8594;</a>
        </div>

        <?php if (empty($hoursData)): ?>
            <tr><td colspan="8">Geen gegevens gevonden.</td></tr>
        <?php else: ?>
            <?php foreach ($hoursData as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['hours_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['name'] . ' ' . $row['achternaam']); ?></td>
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
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Maanden dynamisch toevoegen aan de dropdown
        const monthSelect = document.getElementById('month-select');
        const currentYear = new Date().getFullYear();
        const months = [
            'Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni',
            'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'
        ];

        // Voeg de maanden toe aan de dropdown
        months.forEach((month, index) => {
            const option = document.createElement('option');
            option.value = `${currentYear}-${String(index + 1).padStart(2, '0')}`;  // YYYY-MM formaat
            option.textContent = month;
            monthSelect.appendChild(option);
        });

        // Functie om de dropdown-content te tonen of verbergen
        function toggleDropdown() {
            const dropdownContent = document.getElementById("dropdown-content");
            dropdownContent.style.display = dropdownContent.style.display === 'none' ? 'block' : 'none';
        }

        // Voeg event listener toe aan de knop om de dropdown te tonen of verbergen
        document.getElementById("dropdown-btn").addEventListener('click', function (event) {
            event.stopPropagation(); // Voorkom dat het venster-klikevent de dropdown meteen sluit
            toggleDropdown();
        });

        // Verberg dropdown zodra op een accordeer-knop wordt geklikt
        document.querySelectorAll(".approve-month").forEach(button => {
            button.addEventListener("click", function () {
                const dropdownContent = document.getElementById("dropdown-content");
                if (dropdownContent) {
                    dropdownContent.style.display = "none";
                }
            });
        });

        // Verberg dropdown als er buiten wordt geklikt
        document.addEventListener("click", function (event) {
            const dropdownContent = document.getElementById("dropdown-content");
            const dropdownBtn = document.getElementById("dropdown-btn");

            // Controleer of de klik buiten de dropdown-content en de knop was
            if (dropdownContent && dropdownContent.style.display === "block" &&
                !dropdownContent.contains(event.target) &&
                !dropdownBtn.contains(event.target)) {
                dropdownContent.style.display = "none";
            }
        });

        // Event listener voor de status dropdowns
        document.querySelectorAll('.status-dropdown').forEach(dropdown => {
            dropdown.addEventListener('change', function () {
                const hoursId = this.getAttribute('data-hours-id');
                const status = this.value;

                updateStatus(hoursId, status, dropdown);
            });

            // Initialiseer de kleur van de dropdown bij het laden
            updateDropdownColor(dropdown, dropdown.value);
        });

        // Functie om de kleur van de dropdown aan te passen op basis van de status
        function updateDropdownColor(dropdown, status) {
            dropdown.classList.remove('approved', 'rejected', 'pending');
            if (status === 'Approved') {
                dropdown.classList.add('approved');
            } else if (status === 'Rejected') {
                dropdown.classList.add('rejected');
            } else if (status === 'Pending') {
                dropdown.classList.add('pending');
            }
        }

        // Functie voor het bijwerken van de status van uren
        function updateStatus(hoursId, status, dropdown) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `hours_id=${encodeURIComponent(hoursId)}&status=${encodeURIComponent(status)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessMessage(data.message);
                        updateDropdownColor(dropdown, status);
                    } else {
                        showFailMessage(data.message);
                    }
                })
                .catch(error => {
                    console.error('Fout bij het bijwerken van de status:', error);
                    showFailMessage('Fout bij het bijwerken van de status.');
                });
        }

        // Functie voor het tonen van succesbericht
        function showSuccessMessage(message) {
            showMessage('klant-success-mess', message);
        }

        // Functie voor het tonen van foutbericht
        function showFailMessage(message) {
            showMessage('klant-fail-mess', message);
        }

        // Algemene functie voor het tonen van berichten
        function showMessage(className, message) {
            const messageElement = document.createElement('div');
            messageElement.classList.add(className);  // Voegt de juiste klasse toe (bijv. 'klant-fail-mess' voor foutmeldingen)
            messageElement.textContent = message;
            document.querySelector('.container').prepend(messageElement);

            setTimeout(function() {
                messageElement.remove();
            }, 5000);
        }

        // Event listener voor de goedkeurknop van de maand
        document.querySelector('.approve-month').addEventListener('click', function () {
            const selectedMonth = monthSelect.value;

            if (selectedMonth) {
                approveMonth(selectedMonth);
            } else {
                showFailMessage('Selecteer een maand om te accorderen.');
            }
        });

        // Functie voor het goedkeuren van de geselecteerde maand
        function approveMonth(month) {
            fetch('update-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'month=' + encodeURIComponent(month)
            })
                .then(response => response.json())
                .then(data => {
                    // Controleer of de status 'error' is om te bepalen of het een fout is
                    if (data.status === 'success') {
                        showSuccessMessage(data.message);
                        setTimeout(function (){
                            location.reload();
                        }, 4000);
                    } else {
                        showFailMessage(data.message);
                    }
                })
                .catch(error => {
                    console.error('Er is een fout opgetreden:', error);
                    showFailMessage('Er is een fout opgetreden bij het verzenden van de aanvraag.');
                });
        }
    });

    function updateGebruikerFilter() {
        const userId = document.getElementById("gebruikerFilter").value;
        const urlParams = new URLSearchParams(window.location.search);

        if (userId) {
            urlParams.set("user_id", userId);
        } else {
            urlParams.delete("user_id");
        }

        window.location.search = urlParams.toString();
    }

</script>
</body>
</html>