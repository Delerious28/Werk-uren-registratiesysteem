<?php
session_start();
include "db/conn.php"; // Database Connectie.

// Check als de gebruiker ingelogd is:
if (!isset($_SESSION['user_id'])) {
    echo "<p>Er is een probleem met uw inloggegevens. Log opnieuw in.</p>";
    header("Location: auth/inloggen.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Haal de gebruiker's id op uit de sessie.
$user_name = $_SESSION['user']; // Haal de gebruiker's naam op uit de sessie.

// Bereken gewerkte uren per week
$stmt_week = $pdo->prepare("
    SELECT YEAR(date) AS jaar, WEEK(date, 1) AS week_nummer, SUM(hours) AS totaal_uren
    FROM hours
    WHERE user_id = ?
    GROUP BY jaar, week_nummer
    ORDER BY jaar DESC, week_nummer DESC
    LIMIT 5
");
$stmt_week->execute([$user_id]);
$week_uren = $stmt_week->fetchAll(PDO::FETCH_ASSOC);

// Bereken gewerkte uren per maand
$stmt_month = $pdo->prepare("
    SELECT DATE_FORMAT(date, '%Y-%m') AS maand, SUM(hours) AS totaal_uren
    FROM hours
    WHERE user_id = ?
    GROUP BY maand
    ORDER BY maand DESC
    LIMIT 5
");
$stmt_month->execute([$user_id]);
$maand_uren = $stmt_month->fetchAll(PDO::FETCH_ASSOC);

// Initialiseer de succesberichtvariabele
$success_message = '';

// Formulierinzending afhandelen
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (!empty($_POST['hours']) && !empty($_POST['date'])) {
        $hours = $_POST['hours'];
        $date = $_POST['date'];

        if ($user_id) {
            $hours = intval($hours);

            // Controleer of er al uren zijn ingevoerd voor deze dag
            $stmt = $pdo->prepare("SELECT * FROM hours WHERE user_id = ? AND date = ?");
            $stmt->execute([$user_id, $date]);
            $existingEntry = $stmt->fetch();

            if ($existingEntry) {
                echo "<p>Je hebt al uren ingevoerd voor deze dag.</p>";
            } else {
                // Gegevens invoeren in de database als er nog geen record bestaat
                $stmt = $pdo->prepare("INSERT INTO hours (user_id, date, hours) VALUES (?, ?, ?)");
                if ($stmt->execute([$user_id, $date, $hours])) {
                    $success_message = "Uren succesvol ingevoerd voor $date.";
                } else {
                    echo "<p>Er is een fout opgetreden bij het invoeren van de uren.</p>";
                }
            }
        }
    } else {
        echo "<p>Vul alle velden in!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<?php include "template/header.php"; ?>

<main>
    <div class="content-container">
        <div class="welcome-message">
            Welkom, <?php echo htmlspecialchars($user_name); ?>!
        </div>

        <div><?php echo date("d F Y"); ?></div>

        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Overzicht van gewerkte uren per week -->
        <h2>Gewerkte uren per week</h2>
        <table>
            <tr>
                <th>Week</th>
                <th>Totaal uren</th>
            </tr>
            <?php foreach ($week_uren as $week): ?>
                <tr>
                    <td><?php echo htmlspecialchars($week['jaar']) . '-W' . str_pad($week['week_nummer'], 2, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars($week['totaal_uren']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Overzicht van gewerkte uren per maand -->
        <h2>Gewerkte uren per maand</h2>
        <table>
            <tr>
                <th>Maand</th>
                <th>Totaal uren</th>
            </tr>
            <?php foreach ($maand_uren as $maand): ?>
                <tr>
                    <td><?php echo htmlspecialchars($maand['maand']); ?></td>
                    <td><?php echo htmlspecialchars($maand['totaal_uren']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Formulier om nieuwe uren in te voeren -->
        <div class="week-container">
            <?php
            $weekdagen = ["Maandag", "Dinsdag", "Woensdag", "Donderdag", "Vrijdag"];
            foreach ($weekdagen as $dag) {
                echo "<div><button class='dag'>$dag</button></div>";
            }
            ?>
        </div>

        <div class="date-ctn">
            <form id="day-form" action="index.php" method="POST">
                <div class="uren-form">
                    <div id="selected-day"></div>
                    <input type="number" name="hours" min="0" max="24" required placeholder="Voer uren in">
                    <input type="hidden" name="date" id="date-input">
                    <button type="submit" id="indien-btn">Indienen</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const buttons = document.querySelectorAll('.dag');
        const dateCtn = document.querySelector('.date-ctn');

        buttons.forEach((button, index) => {
            button.addEventListener("click", function () {
                buttons.forEach(btn => btn.classList.remove('highlight'));
                button.classList.add('highlight');

                button.parentNode.insertBefore(dateCtn, button.nextSibling);
                dateCtn.style.display = "block";

                const weekdays = ["Maandag", "Dinsdag", "Woensdag", "Donderdag", "Vrijdag"];
                const today = new Date();
                const monday = new Date(today.setDate(today.getDate() - today.getDay() + 1));
                let selectedDate = new Date(monday);
                selectedDate.setDate(monday.getDate() + index);

                document.getElementById('date-input').value = selectedDate.toISOString().split('T')[0];
                document.getElementById('selected-day').innerText = `Geselecteerde dag: ${weekdays[index]} (${selectedDate.toLocaleDateString('nl-NL')})`;
            });
        });
    });
</script>

</body>
</html>
