<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: inloggen.php");
    exit();
}
require 'db/conn.php';
require 'sidebar.php';

// Klanten ophalen die gekoppeld zijn aan de projecten van de gebruiker
$klantenQuery = "
    SELECT k.klant_id, k.voornaam, k.achternaam
    FROM klant k
    JOIN project p ON p.klant_id = k.klant_id
    JOIN project_users pu ON pu.project_id = p.project_id
    WHERE pu.user_id = :user_id
";
$klantenStmt = $pdo->prepare($klantenQuery);
$klantenStmt->execute(['user_id' => $_SESSION['user_id']]);
$klanten = $klantenStmt->fetchAll(PDO::FETCH_ASSOC);

// Gekozen klant ophalen uit het formulier (voor het filteren van projecten)
$selectedKlant = isset($_POST['klant']) ? $_POST['klant'] : "";

// Projecten ophalen op basis van de geselecteerde klant
$projecten = [];
if (!empty($selectedKlant)) {
    $projectenQuery = "SELECT project_id, project_naam FROM project WHERE klant_id = ? AND project_id IN (
        SELECT project_id FROM project_users WHERE user_id = ?
    )";
    $projectenStmt = $pdo->prepare($projectenQuery);
    $projectenStmt->execute([$selectedKlant, $_SESSION['user_id']]);
    $projecten = $projectenStmt->fetchAll(PDO::FETCH_ASSOC);
}

$message = "";
$duplicateMessage = "";

// Week navigatie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['week_offset'])) {
    $_SESSION['week_offset'] = $_POST['week_offset']; // Sla de week_offset op in de sessie
}

// Week offset ophalen (standaard is 0)
$weekOffset = $_SESSION['week_offset'] ?? 0;

// Dag selecteren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_day'])) {
    $_SESSION['selected_day'] = $_POST['selected_day']; // Sla de geselecteerde dag op in de sessie
}

// Geselecteerde dag ophalen (standaard is het vandaag)
$selectedDay = $_SESSION['selected_day'] ?? date('Y-m-d');

// Formulier verwerken (uren toevoegen)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['klant'], $_POST['project'], $_POST['begin'], $_POST['eind'])) {
    if (!empty($_POST['klant']) && !empty($_POST['project']) && !empty($_POST['begin']) && !empty($_POST['eind'])) {
        $klantId      = $_POST['klant'];
        $projectId    = $_POST['project'];
        $beschrijving = isset($_POST['beschrijving']) ? htmlspecialchars($_POST['beschrijving']) : "";
        $begin        = $_POST['begin'];
        $eind         = $_POST['eind'];

        // Gebruik de geselecteerde dag uit het formulier
        $selectedDay = $_POST['selected_day'];

        // Controleer of er al uren zijn ingevoerd voor de geselecteerde dag
        $checkQuery = "SELECT COUNT(*) AS count FROM hours WHERE user_id = :user_id AND date = :date";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute(['user_id' => $_SESSION['user_id'], 'date' => $selectedDay]); // Gebruik de user_id uit de sessie
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            $duplicateMessage = "Er zijn al uren ingevoerd voor de geselecteerde dag. U kunt geen nieuwe uren toevoegen.";
        } else {
            $startHours = $begin . ":00";
            $endHours   = $eind . ":00";

            $startSeconds = strtotime($startHours);
            $endSeconds   = strtotime($endHours);
            if ($endSeconds > $startSeconds) {
                $urenVerschil = ($endSeconds - $startSeconds) / 3600;
                $totaalUren = (int)$urenVerschil;
            } else {
                $totaalUren = 0;
            }

            $userId         = $_SESSION['user_id']; // Gebruik de user_id uit de sessie
            $hours          = $totaalUren;
            $accord         = "Pending";
            $contract_hours = 0;

            $insertQuery = "INSERT INTO hours (project_id, user_id, date, start_hours, eind_hours, hours, accord, contract_hours, beschrijving) 
                            VALUES (:project_id, :user_id, :date, :start_hours, :eind_hours, :hours, :accord, :contract_hours, :beschrijving)";
            $stmt = $pdo->prepare($insertQuery);
            $result = $stmt->execute([
                'project_id'      => $projectId,
                'user_id'         => $userId,
                'date'            => $selectedDay, // Gebruik de geselecteerde dag
                'start_hours'     => $startHours,
                'eind_hours'      => $endHours,
                'hours'           => $hours,
                'accord'          => $accord,
                'contract_hours'  => $contract_hours,
                'beschrijving'    => $beschrijving
            ]);
            if ($result) {
                $message = "Uren succesvol toegevoegd voor " . date('d-m-Y', strtotime($selectedDay)) . "!";
                // Redirect naar dezelfde pagina om de rechter container bij te werken
                header("Location: " . $_SERVER['PHP_SELF']);
                exit(); // Zorg ervoor dat de scriptuitvoering stopt na de redirect
            } else {
                $message = "Er is een fout opgetreden bij het toevoegen van de uren.";
            }
        }
    } else {
        $message = "Vul alle vereiste velden in.";
    }
}

// Bereken de start- en einddatum van de huidige week op basis van de week_offset
$currentWeekStart = date('Y-m-d', strtotime('monday this week') + ($weekOffset * 7 * 86400)); // Start van de huidige week (maandag)
$currentWeekEnd = date('Y-m-d', strtotime('friday this week') + ($weekOffset * 7 * 86400));   // Einde van de huidige week (vrijdag)

// Haal alle ingevoerde uren op met bijbehorende project- en klantgegevens voor de huidige week
$hoursQuery = "SELECT h.*, p.project_naam, 
                      k.voornaam AS klant_voornaam, k.achternaam AS klant_achternaam,
                      u.name AS user_name, u.achternaam AS user_achternaam
               FROM hours h
               JOIN project p ON h.project_id = p.project_id
               JOIN klant k ON p.klant_id = k.klant_id
               JOIN users u ON h.user_id = u.user_id
               WHERE h.date BETWEEN :weekStart AND :weekEnd
               AND h.user_id = :user_id
               ORDER BY h.date ASC, h.start_hours ASC";
$hoursStmt = $pdo->prepare($hoursQuery);
$hoursStmt->execute([
    'weekStart' => $currentWeekStart,
    'weekEnd'   => $currentWeekEnd,
    'user_id'   => $_SESSION['user_id'] // Voeg de user_id van de ingelogde gebruiker toe
]);
$hoursRecords = $hoursStmt->fetchAll(PDO::FETCH_ASSOC);

$dutchDays = [
    'Monday' => 'Maandag',
    'Tuesday' => 'Dinsdag',
    'Wednesday' => 'Woensdag',
    'Thursday' => 'Donderdag',
    'Friday' => 'Vrijdag',
    'Saturday' => 'Zaterdag',
    'Sunday' => 'Zondag'
];

// Groepeer de uren per dag
$groupedHours = [];
foreach ($hoursRecords as $record) {
    $todayDate = date('Y-m-d');
    $dayOfWeek = date('l', strtotime($record['date'])); // Bijv. "Monday"
    $dayOfWeekDUtch = $dutchDays[$dayOfWeek] ?? $dayOfWeek;
    $groupedHours[$dayOfWeekDUtch][] = $record;
}

?>


<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uren Registreren</title>
  <link rel="stylesheet" href="css/uren-registreren.css">
</head>
<body>
<div class="bigbox">
  <div class="topheader">
    <div class="datum">
      <h3 id="date-today"><?php echo date('d M Y'); ?> (Today)</h3>
    </div>
    <div class="week-nav">
      <form method="POST" action="" style="display: inline;">
        <input type="hidden" name="week_offset" value="<?= $weekOffset - 1; ?>">
        <button type="submit" id="prev" title="Vorige week">&larr;</button>
      </form>
      <form method="POST" action="" style="display: inline;">
        <input type="hidden" name="week_offset" value="<?= $weekOffset + 1; ?>">
        <button type="submit" id="next" title="Volgende week">&rarr;</button>
      </form>
    </div>
  </div>

  <div class="error-bericht">
    <?php if ($duplicateMessage): ?>
        <p class="dupliceer-bericht"><?php echo $duplicateMessage; ?></p>
    <?php endif; ?>
  </div>

  <div class="wrapper">
    <!-- Linker container: invoerformulier -->
    <div class="blok-1">

        <form id="urenForm" method="POST" action="" class="form-div">
            <input type="hidden" name="selected_day" value="<?= $selectedDay; ?>">

            <li>Klant:</li>
            <select name="klant" class="small-input" onchange="this.form.submit()">
                <option value="">-- Kies Klant --</option>
                <?php
                // Groepeer de klanten op klant_id om duplicaten te verwijderen
                $uniqueKlanten = [];
                foreach ($klanten as $klant) {
                    $uniqueKlanten[$klant['klant_id']] = $klant;
                }

                // Toon alleen unieke klanten
                foreach ($uniqueKlanten as $klant): ?>
                    <option value="<?= $klant['klant_id']; ?>" <?= ($klant['klant_id'] == $selectedKlant) ? 'selected' : ''; ?>>
                        <?= $klant['voornaam'] . ' ' . $klant['achternaam']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="vereisten-bericht" id="error-klant"></p>

            <li>Project naam:</li>
            <select name="project" class="small-input">
                <option value="">-- Kies Project --</option>
                <?php foreach ($projecten as $project): ?>
                    <option value="<?= $project['project_id']; ?>" <?= (isset($_POST['project']) && $_POST['project'] == $project['project_id']) ? 'selected' : ''; ?>>
                        <?= $project['project_naam']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="vereisten-bericht" id="error-project"></p>

            <li>Beschrijving:</li>
            <input type="text" name="beschrijving" class="small-input" placeholder="Projectbeschrijving (optioneel)">

            <li>Starttijd:</li>
            <select name="begin">
                <option value="">-- Kies --</option>
                <option value="08:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '08:00') ? 'selected' : ''; ?>>08:00</option>
                <option value="09:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '09:00') ? 'selected' : ''; ?>>09:00</option>
                <option value="10:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '10:00') ? 'selected' : ''; ?>>10:00</option>
                <option value="11:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '11:00') ? 'selected' : ''; ?>>11:00</option>
                <option value="12:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '12:00') ? 'selected' : ''; ?>>12:00</option>
            </select>
            <p class="vereisten-bericht" id="error-begin"></p>

            <li>Eindtijd:</li>
            <select name="eind">
                <option value="">-- Kies --</option>
                <option value="12:00" <?= (isset($_POST['eind']) && $_POST['eind'] == '12:00') ? 'selected' : ''; ?>>12:00</option>
                <option value="13:00" <?= (isset($_POST['eind']) && $_POST['eind'] == '13:00') ? 'selected' : ''; ?>>13:00</option>
                <option value="14:00" <?= (isset($_POST['eind']) && $_POST['eind'] == '14:00') ? 'selected' : ''; ?>>14:00</option>
                <option value="15:00" <?= (isset($_POST['eind']) && $_POST['eind'] == '15:00') ? 'selected' : ''; ?>>15:00</option>
                <option value="16:00" <?= (isset($_POST['eind']) && $_POST['eind'] == '16:00') ? 'selected' : ''; ?>>16:00</option>
                <option value="17:00" <?= (isset($_POST['eind']) && $_POST['eind'] == '17:00') ? 'selected' : ''; ?>>17:00</option>
                <option value="18:00" <?= (isset($_POST['eind']) && $_POST['eind'] == '18:00') ? 'selected' : ''; ?>>18:00</option>
                <option value="19:00" <?= (isset($_POST['eind']) && $_POST['eind'] == '19:00') ? 'selected' : ''; ?>>19:00</option>
            </select>
            <p class="vereisten-bericht" id="error-eind"></p>

            <button type="submit">+ Voeg toe</button>
        </form>

    </div>

    <!-- Rechter container: overzicht van ingevoerde uren voor de huidige week -->
      <div class="block-b">
          <div class="overzicht">
              <?php
              // Definieer de dagen van de week in het Nederlands
              $dayOfWeekDUtch = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
              foreach ($dayOfWeekDUtch as $dag):
                  $dagNaam = strtolower($dag); // Bijv. "monday"
                  $dagDatum = date('Y-m-d', strtotime($dag . ' this week') + ($weekOffset * 7 * 86400)); // Datum van de dag
                  $dagRecords = $groupedHours[$dutchDays[$dag]] ?? []; // Haal de uren op voor deze dag in het Nederlands
                  $isSelected = ($selectedDay === $dagDatum); // Controleer of deze dag geselecteerd is
                  ?>
                  <form method="POST" action="" class="dag-uren-form">
                      <input type="hidden" name="selected_day" value="<?= $dagDatum; ?>">
                      <div class="dag <?= $isSelected ? 'selected' : ''; ?> <?= ($dagDatum === $todayDate) ? 'today-highlight' : ''; ?>" onclick="this.parentNode.submit();">
                          <div class="dag-info">
                              <span class="dagnaam"><?php echo $dutchDays[$dag]; // Vertaalde dagnaam ?></span>
                              <span class="datum-klein"><?php echo date('d-m', strtotime($dagDatum)); ?></span>
                          </div>
                          <?php if (!empty($dagRecords)): ?>
                              <?php foreach ($dagRecords as $record): ?>
                                  <div class="info">
                                      Klant: <?php echo $record['klant_voornaam'] ?><br>
                                      Project: <?php echo $record['project_naam']; ?><br>
                                      Beschrijving: ...
                                  </div>
                                  <div class="tijd">
                                      <span class="uren-dik"><?php echo $record['hours']; ?> uur</span><br>
                                      <?php echo date('H:i', strtotime($record['start_hours'])); ?> - <?php echo date('H:i', strtotime($record['eind_hours'])); ?>
                                  </div>
                              <?php endforeach; ?>
                          <?php else: ?>
                              <p class="geen-uren-bericht">Geen uren ingevoerd voor <?php echo $dutchDays[$dag]; // Vertaalde dagnaam ?>.</p>
                          <?php endif; ?>
                      </div>
                      <div class="info-png-div">
                          <!-- Link naar de uren-informatie-pagina met de geselecteerde datum als queryparameter -->
                          <a href="javascript:void(0);" data-date="<?= urlencode($dagDatum); ?>" class="info-icon">
                              <img src="<?= $isSelected ? 'img/info-icon-white.png' : 'img/info-icon.png'; ?>" alt="Info Icon" class="info-icon" title="Meer info">
                          </a>
                      </div>
                  </form>
              <?php endforeach; ?>
          </div>
      </div>

      <!-- Pop-up / Modaal venster -->
      <div id="urenInformatieModal" class="modal">
          <div class="modal-content">
              <span class="close">&times;</span>
              <div id="modalContent">
                  <!-- De gegevens van de geselecteerde datum komen hier te staan -->
              </div>
          </div>
      </div>

  </div>
</div>

<script src="js/uren-registreren.js"></script>
</body>
</html>