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

// Initialiseer de succesberichtvariabele
$success_message = '';

// Formulierinzending afhandelen
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (!empty($_POST['hours']) && !empty($_POST['date'])) {
        $hours = $_POST['hours'];
        $date = $_POST['date'];

        // Zorg ervoor dat user_id is ingesteld voordat ik doorga met het invoegen van de database.
        if ($user_id) {
            // Ingangen opschonen (SQL-injectie voorkomen).
            $hours = intval($hours); // Zorg ervoor dat uren een geheel getal (Integer) zijn.
            // Het is niet nodig om de datum handmatig te zuiveren, aangezien deze door PDO wordt afgehandeld in de voorbereide verklaring.

            // Gegevens invoeren in de database in (urentabel).
            $stmt = $pdo->prepare("INSERT INTO hours (user_id, date, hours) VALUES (?, ?, ?)");
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $date, PDO::PARAM_STR);
            $stmt->bindParam(3, $hours, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Success bericht.
                $success_message = "Uren succesvol ingevoerd voor $date.";
            } else {
                echo "<p>Er is een fout opgetreden bij het invoeren van de uren: " . $stmt->errorInfo()[2] . "</p>";
            }
        } else {
            echo "<p>Er is een probleem met je gebruikersgegevens.</p>";
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
    <script>
        function showInputForm(dayOffset) {
            const today = new Date();
            const dayOfWeek = today.getDay(); // 0 (Zondag) tot 6 (Zaterdag).

            // Het begin van de huidige week berekenen (maandag).
            let monday = new Date(today);
            let adjustment = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
            monday.setDate(today.getDate() + adjustment);

            // De geselecteerde datum op basis van de verschuiving vanaf maandag berekenen.
            const selectedDate = new Date(monday);
            selectedDate.setDate(monday.getDate() + dayOffset); // De offset toevoegen om de specifieke weekdag te krijgen.

            // De geselecteerde dag en datum weergeven.
            document.getElementById('selected-day').innerText = selectedDate.toDateString();

            // De verborgen datuminvoerwaarde instellen (in JJJJ-MM-DD formaat).
            const formattedDate = selectedDate.toISOString().split('T')[0]; // Formaat naar YYYY-MM-DD.
            document.getElementById('date-input').value = formattedDate;

            // De form weergeven.
            document.getElementById('day-form').style.display = 'block';
        }

        // Functie om de weergegeven datums voor maandag tot en met vrijdag bij te werken.
        function updateWeekdays() {
            const today = new Date();
            const dayOfWeek = today.getDay();
            let adjustment = (dayOfWeek === 0) ? -6 : 1 - dayOfWeek;

            let monday = new Date(today);
            monday.setDate(today.getDate() + adjustment);

            const weekdays = ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag'];
            const buttons = document.querySelectorAll('.dag'); // Alle knoppen selecteren

            buttons.forEach((button, index) => {
                let weekdayDate = new Date(monday);
                weekdayDate.setDate(monday.getDate() + index);
                button.innerText = weekdays[index]; // Alleen de dag tonen

                // **Highlight de huidige dag**
                if (weekdayDate.toDateString() === today.toDateString()) {
                    button.classList.add("highlight");
                } else {
                    button.classList.remove("highlight");
                }

                // **Toon de datum en activeer het formulier bij klik**
                button.onclick = function () {
                    // Toon de geselecteerde dag
                    document.getElementById('selected-day').innerText =
                        `${weekdays[index]} ${weekdayDate.toLocaleDateString('nl-NL')}`;

                    // Zet de juiste datum in het verborgen inputveld
                    document.getElementById('date-input').value = weekdayDate.toISOString().split('T')[0];

                    // Maak het formulier zichtbaar
                    document.getElementById('day-form').style.display = 'block';
                };
            });
        }

        window.onload = updateWeekdays;

    </script>
</head>
<body>

<?php include "template/header.php"; ?>

<main>
    <div class="content-container">
        <!-- Ingelogde gebruikersnaam weergeven -->
        <div class="welcome-message">
            Welkom, <?php echo htmlspecialchars($user_name); ?>!
        </div>

        <div><?php echo date("d F Y"); ?></div>

        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="week-container">
            <button class="dag" onclick="showInputForm(0)">Maandag</button>
            <button class="dag" onclick="showInputForm(1)">Dinsdag</button>
            <button class="dag" onclick="showInputForm(2)">Woensdag</button>
            <button class="dag" onclick="showInputForm(3)">Donderdag</button>
            <button class="dag" onclick="showInputForm(4)">Vrijdag</button>
        </div>

        <div class="date-ctn">
            <form id="day-form" action="index.php" method="POST" style="display: none;">
                <div class="uren-form">
                    <div id="selected-day"></div>
                <input type="number" name="hours" min="0" max="24" required placeholder="Enter hours">
                <input type="hidden" name="date" id="date-input">
                <button type="submit">Indienen</button>
                </div>
            </form>
        </div>
    </div>
</main>

</body>
</html>
