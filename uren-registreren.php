<?php
require 'db/conn.php';

// Klanten ophalen
$klantenQuery = "SELECT DISTINCT klant_id, klant_voornaam, klant_achternaam FROM klant";
$klantenStmt = $pdo->prepare($klantenQuery);
$klantenStmt->execute();
$klanten = $klantenStmt->fetchAll(PDO::FETCH_ASSOC);

// Gekozen klant ophalen uit het formulier
$selectedKlant = isset($_POST['klant']) ? $_POST['klant'] : "";

// Projecten ophalen op basis van de geselecteerde klant
$projecten = [];
if (!empty($selectedKlant)) {
    $projectenQuery = "SELECT p.project_naam FROM project p 
                       JOIN klant k ON p.project_id = k.project_id 
                       WHERE k.klant_id = ?";
    $projectenStmt = $pdo->prepare($projectenQuery);
    $projectenStmt->execute([$selectedKlant]);
    $projecten = $projectenStmt->fetchAll(PDO::FETCH_COLUMN);
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
                <input list="klantenn" name="klant" class="small-input" value="<?= htmlspecialchars($selectedKlant); ?>" onchange="this.form.submit()">
                <datalist id="klantenn">
                    <?php foreach ($klanten as $klant): ?>
                        <option value="<?= $klant['klant_id']; ?>"><?= htmlspecialchars($klant['klant_voornaam'] . " " . $klant['klant_achternaam']); ?></option>
                    <?php endforeach; ?>
                </datalist>

                <label>Project naam:</label>
                <input list="projs" name="project" class="small-input">
                <datalist id="projs">
                    <?php foreach ($projecten as $project): ?>
                        <option value="<?= htmlspecialchars($project); ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <label>Beschrijving:</label>
                <input type="text" name="beschrijving" class="small-input">

                <label>Starttijd:</label>
                <select name="begin">
                    <option>08:00</option>
                    <option>09:00</option>
                    <option>10:00</option>
                    <option>11:00</option>
                    <option>12:00</option>
                    <option>13:00</option>
                    <option>14:00</option>
                    <option>15:00</option>
                    <option>16:00</option>
                </select>

                <label>Eindtijd:</label>
                <select name="eind">
                    <option>12:00</option>
                    <option>13:00</option>
                    <option>14:00</option>
                    <option>15:00</option>
                    <option>16:00</option>
                    <option>17:00</option>
                    <option>18:00</option>
                    <option>19:00</option>
                </select>

                <label>Uren totaal:</label>
                <input type="text" id="totaaluren" readonly class="small-input">

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
