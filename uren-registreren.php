<?php
require 'db/conn.php';

// Klanten ophalen
$klantenQuery = "SELECT klant_id, voornaam, achternaam FROM klant";
$klantenStmt = $pdo->prepare($klantenQuery);
$klantenStmt->execute();
$klanten = $klantenStmt->fetchAll(PDO::FETCH_ASSOC);

// Gekozen klant ophalen uit het formulier
$selectedKlant = isset($_POST['klant']) ? $_POST['klant'] : "";

// Projecten ophalen op basis van de geselecteerde klant
$projecten = [];
if (!empty($selectedKlant)) {
    $projectenQuery = "SELECT p.project_id, p.project_naam 
                       FROM project p
                       JOIN klant k ON k.project_id = p.project_id
                       WHERE k.klant_id = ?";
    $projectenStmt = $pdo->prepare($projectenQuery);
    $projectenStmt->execute([$selectedKlant]);
    $projecten = $projectenStmt->fetchAll(PDO::FETCH_ASSOC);
}

$totaalUren = "";
if (isset($_POST['begin']) && isset($_POST['eind'])) {
    $startTijd = strtotime($_POST['begin']);
    $eindTijd = strtotime($_POST['eind']);

    // Zorg dat de eindtijd later is dan de starttijd
    if ($eindTijd > $startTijd) {
        $urenVerschil = ($eindTijd - $startTijd) / 3600; // Converteer seconden naar uren
        $totaalUren = $urenVerschil . " uur";
    } else {
        $totaalUren = "Ongeldige tijd";
    }
}

// Insert the data into the database when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['klant'], $_POST['project'], $_POST['begin'], $_POST['eind'], $_POST['beschrijving'])) {
    // Sanitize the form inputs
    $klantId = $_POST['klant'];
    $projectId = $_POST['project'];
    $beschrijving = htmlspecialchars($_POST['beschrijving']);
    $begin = $_POST['begin'];
    $eind = $_POST['eind'];
    $totaaluren = htmlspecialchars($totaalUren);

    // Insert into the `hours` table
    $insertQuery = "INSERT INTO hours (user_id, date, start_hours, eind_hours, hours, accord, contract_hours)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute([$userId, $date, $startHours, $endHours, $hours, $accord, $contractHours]);

    $stmt = $pdo->prepare($insertQuery);
    $stmt->bindParam(':user_id', $klantId, PDO::PARAM_INT);
    $stmt->bindParam(':start_hours', $begin, PDO::PARAM_STR);
    $stmt->bindParam(':eind_hours', $eind, PDO::PARAM_STR);
    $stmt->bindParam(':hours', $totaaluren, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo "Uren succesvol toegevoegd!";
    } else {
        echo "Er is een fout opgetreden bij het toevoegen van de uren.";
    }
}
?>

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
            <h3 id="date-today">21 feb</h3>
        </div>
        <div class="week-nav">
            <button id="prev">&larr;</button>
            <button id="next">&rarr;</button>
        </div>
    </div>

    <div class="wrapper">
        <!-- invoer gedeelte -->
        <form method="POST">
            <div class="blok-1">
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
                <input type="text" name="beschrijving" class="small-input">

                <label>Starttijd:</label>
                <select name="begin" onchange="this.form.submit()">
                    <option value="">-- Kies --</option>
                    <option value="08:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '08:00') ? 'selected' : ''; ?>>08:00</option>
                    <option value="09:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '09:00') ? 'selected' : ''; ?>>09:00</option>
                    <option value="10:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '10:00') ? 'selected' : ''; ?>>10:00</option>
                    <option value="11:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '11:00') ? 'selected' : ''; ?>>11:00</option>
                    <option value="12:00" <?= (isset($_POST['begin']) && $_POST['begin'] == '12:00') ? 'selected' : ''; ?>>12:00</option>
                </select>

                <label>Eindtijd:</label>
                <select name="eind" onchange="this.form.submit()">
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

                <label>Uren totaal:</label>
                <input type="text" id="totaaluren" name="totaaluren" value="<?= htmlspecialchars($totaalUren); ?>" readonly class="small-input">

                <button type="submit">+ Voeg toe</button>
            </div>
        </form>

        <div class="block-b">
            <div class="overzicht">
                <div class="dag">
                    <div class="dag-info">
                        <span class="dagnaam">Maandag</span>
                        <span class="datum-klein">26-02</span>
                    </div>
                    <div class="info">
                        Klant: <br> Project: <br> Beschrijving:
                    </div>
                    <div class="tijd">
                        <span class="uren-dik">8 uur</span> <br> 08:00 - 17:00
                    </div>
                </div>
                <div class="dag">
                    <div class="dag-info">
                        <span class="dagnaam">Dinsdag</span>
                        <span class="datum-klein">27-02</span>
                    </div>
                    <div class="info">
                        Klant: <br> Project: <br> Beschrijving:
                    </div>
                    <div class="tijd">
                        <span class="uren-dik">7 uur</span> <br> 09:00 - 16:00
                    </div>
                </div>
                <div class="dag">
                    <div class="dag-info">
                        <span class="dagnaam">Woensdag</span>
                        <span class="datum-klein">28-02</span>
                    </div>
                    <div class="info">
                        Klant: <br> Project: <br> Beschrijving:
                    </div>
                    <div class="tijd">
                        <span class="uren-dik">6 uur</span> <br> 10:00 - 16:00
                    </div>
                </div>
                <div class="dag">
                    <div class="dag-info">
                        <span class="dagnaam">Donderdag</span>
                        <span class="datum-klein">29-02</span>
                    </div>
                    <div class="info">
                        Klant: <br> Project: <br> Beschrijving:
                    </div>
                    <div class="tijd">
                        <span class="uren-dik">8 uur</span> <br> 08:00 - 17:00
                    </div>
                </div>
                <div class="dag">
                    <div class="dag-info">
                        <span class="dagnaam">Vrijdag</span>
                        <span class="datum-klein">01-03</span>
                    </div>
                    <div class="info">
                        Klant: <br> Project: <br> Beschrijving:
                    </div>
                    <div class="tijd">
                        <span class="uren-dik">7 uur</span> <br> 09:00 - 16:00
                    </div>
                </div>

                <div class="totaalweek">
                    <span> Totaal week: 36 uur</span>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
