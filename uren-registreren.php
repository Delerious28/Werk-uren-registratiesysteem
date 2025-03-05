<?php
require 'db/conn.php';
require 'sidebar.php';

// Klanten ophalen
$klantenQuery = "SELECT klant_id, voornaam, achternaam FROM klant";
$klantenStmt = $pdo->prepare($klantenQuery);
$klantenStmt->execute();
$klanten = $klantenStmt->fetchAll(PDO::FETCH_ASSOC);

// Gekozen klant ophalen uit het formulier (voor het filteren van projecten)
$selectedKlant = isset($_POST['klant']) ? $_POST['klant'] : "";

// Projecten ophalen op basis van de geselecteerde klant
$projecten = [];
if (!empty($selectedKlant)) {
    // Omdat in de projecttabel elk project een klant_id heeft
    $projectenQuery = "SELECT project_id, project_naam FROM project WHERE klant_id = ?";
    $projectenStmt = $pdo->prepare($projectenQuery);
    $projectenStmt->execute([$selectedKlant]);
    $projecten = $projectenStmt->fetchAll(PDO::FETCH_ASSOC);
}

$message = "";

// Formulier verwerken
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['klant'], $_POST['project'], $_POST['begin'], $_POST['eind'])) {
    if (!empty($_POST['klant']) && !empty($_POST['project']) && !empty($_POST['begin']) && !empty($_POST['eind'])) {
        // Verkrijg de ingevoerde waarden
        $klantId      = $_POST['klant'];
        $projectId    = $_POST['project'];
        $beschrijving = isset($_POST['beschrijving']) ? htmlspecialchars($_POST['beschrijving']) : "";
        $begin        = $_POST['begin']; // verwacht formaat "08:00"
        $eind         = $_POST['eind'];  // verwacht formaat "12:00"

        $date = date('Y-m-d'); // Huidige datum

        // Maak van "08:00" -> "08:00:00"
        $startHours = $begin . ":00";
        $endHours   = $eind . ":00";

        // Bereken het verschil in uren als integer
        $startSeconds = strtotime($startHours);
        $endSeconds   = strtotime($endHours);
        if ($endSeconds > $startSeconds) {
            $urenVerschil = ($endSeconds - $startSeconds) / 3600;
            $totaalUren = (int)$urenVerschil;
        } else {
            $totaalUren = 0;
        }

        // Voor dit voorbeeld gebruiken we een standaard user_id (bijvoorbeeld de ingelogde gebruiker)
        $userId         = 1; 
        $hours          = $totaalUren;
        $accord         = "Pending"; // Overeenkomend met je ENUM ('Pending','Approved','Rejected')
        $contract_hours = 0;

        // INSERT in de tabel hours (controleer of je tabel de kolom project_id bevat)
        $insertQuery = "INSERT INTO hours (project_id, user_id, date, start_hours, eind_hours, hours, accord, contract_hours, beschrijving) 
                        VALUES (:project_id, :user_id, :date, :start_hours, :eind_hours, :hours, :accord, :contract_hours, :beschrijving)";
        $stmt = $pdo->prepare($insertQuery);
        $result = $stmt->execute([
            'project_id'      => $projectId,
            'user_id'         => $userId,
            'date'            => $date,
            'start_hours'     => $startHours,
            'eind_hours'      => $endHours,
            'hours'           => $hours,
            'accord'          => $accord,
            'contract_hours'  => $contract_hours,
            'beschrijving'    => $beschrijving
        ]);
        if ($result) {
            $message = "Uren succesvol toegevoegd!";
        } else {
            $message = "Er is een fout opgetreden bij het toevoegen van de uren.";
        }
    } else {
        $message = "Vul alle vereiste velden in.";
    }
}

// Haal alle ingevoerde uren op met bijbehorende project- en klantgegevens
$hoursQuery = "SELECT h.*, p.project_naam, 
                      k.voornaam AS klant_voornaam, k.achternaam AS klant_achternaam,
                      u.name AS user_name, u.achternaam AS user_achternaam
               FROM hours h
               JOIN project p ON h.project_id = p.project_id
               JOIN klant k ON p.klant_id = k.klant_id
               JOIN users u ON h.user_id = u.user_id
               ORDER BY h.date DESC, h.start_hours DESC";
$hoursStmt = $pdo->prepare($hoursQuery);
$hoursStmt->execute();
$hoursRecords = $hoursStmt->fetchAll(PDO::FETCH_ASSOC);
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
      <h3 id="date-today"><?php echo date('d M'); ?></h3>
    </div>
    <div class="week-nav">
      <button id="prev">&larr;</button>
      <button id="next">&rarr;</button>
    </div>
  </div>
  <div class="wrapper">
    <!-- Linker container: invoerformulier -->
    <div class="blok-1">
      <?php if ($message): ?>
        <p><?php echo $message; ?></p>
      <?php endif; ?>
      <form method="POST" action="">
        <label>Klant:</label>
        <select name="klant" class="small-input" onchange="this.form.submit()">
          <option value="">-- Kies Klant --</option>
          <?php foreach ($klanten as $klant): ?>
            <option value="<?= $klant['klant_id']; ?>" <?= ($klant['klant_id'] == $selectedKlant) ? 'selected' : ''; ?>>
              <?= $klant['voornaam'] . ' ' . $klant['achternaam']; ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>Project naam:</label>
        <select name="project" class="small-input">
          <option value="">-- Kies Project --</option>
          <?php foreach ($projecten as $project): ?>
            <option value="<?= $project['project_id']; ?>" <?= (isset($_POST['project']) && $_POST['project'] == $project['project_id']) ? 'selected' : ''; ?>>
              <?= $project['project_naam']; ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>Beschrijving:</label>
        <input type="text" name="beschrijving" class="small-input" placeholder="Projectbeschrijving (optioneel)">

        <label>Starttijd:</label>
        <select name="begin">
          <option value="">-- Kies --</option>
          <option value="08:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '08:00') ? 'selected' : ''; ?>>08:00</option>
          <option value="09:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '09:00') ? 'selected' : ''; ?>>09:00</option>
          <option value="10:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '10:00') ? 'selected' : ''; ?>>10:00</option>
          <option value="11:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '11:00') ? 'selected' : ''; ?>>11:00</option>
          <option value="12:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '12:00') ? 'selected' : ''; ?>>12:00</option>
        </select>

        <label>Eindtijd:</label>
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

        <button type="submit">+ Voeg toe</button>
      </form>
    </div>

    <!-- Rechter container: overzicht van ingevoerde uren -->
    <div class="block-b">
      <div class="overzicht">
        <?php if (count($hoursRecords) > 0): ?>
          <?php foreach ($hoursRecords as $record): ?>
            <div class="dag">
              <div class="dag-info">
                <span class="dagnaam"><?php echo date('l', strtotime($record['date'])); ?></span>
                <span class="datum-klein"><?php echo date('d-m', strtotime($record['date'])); ?></span>
              </div>
              <div class="info">
                Klant: <?php echo $record['klant_voornaam'] . ' ' . $record['klant_achternaam']; ?><br>
                Project: <?php echo $record['project_naam']; ?><br>
                Beschrijving: <?php echo $record['beschrijving']; ?>
              </div>
              <div class="tijd">
                <span class="uren-dik"><?php echo $record['hours']; ?> uur</span><br>
                <?php echo date('H:i', strtotime($record['start_hours'])); ?> - <?php echo date('H:i', strtotime($record['eind_hours'])); ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>Geen ingevoerde uren.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
