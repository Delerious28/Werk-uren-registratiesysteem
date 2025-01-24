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

        if ($user_id) {
            // Ingangen opschonen (SQL-injectie voorkomen)
            $hours = intval($hours);

            // Controleer of er al uren zijn ingevoerd voor deze dag
            $stmt = $pdo->prepare("SELECT * FROM hours WHERE user_id = ? AND date = ?");
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $date, PDO::PARAM_STR);
            $stmt->execute();
            $existingEntry = $stmt->fetch();

            if ($existingEntry) {
                // Als er al een record bestaat, geef een foutmelding
                echo "<script> 
                                   var removeBtn = document.getElementById('indien-btn');
                                removeBtn.style.display = 'none';
                    </script>";
            } else {
                // Gegevens invoeren in de database als er nog geen record bestaat
                $stmt = $pdo->prepare("INSERT INTO hours (user_id, date, hours) VALUES (?, ?, ?)");
                $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                $stmt->bindParam(2, $date, PDO::PARAM_STR);
                $stmt->bindParam(3, $hours, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $success_message = "Uren succesvol ingevoerd voor $date.";
                } else {
                    echo "<p>Er is een fout opgetreden bij het invoeren van de uren: " . $stmt->errorInfo()[2] . "</p>";
                }
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
        let activeButton = null;  // Variabele om de actieve knop bij te houden

        buttons.forEach((button, index) => {
            button.addEventListener("click", function () {
                // Verwijder de highlight van alle knoppen (behalve de actieve knop)
                buttons.forEach(btn => btn.classList.remove('highlight'));

                // Als het formulier al zichtbaar is onder dezelfde knop, verberg het en verwijder de highlight
                if (dateCtn.style.display === "block" && dateCtn.parentElement === button.parentNode) {
                    dateCtn.style.display = "none"; // Verberg het formulier
                    button.classList.remove('highlight'); // Verwijder de highlight
                    activeButton = null; // Geen actieve knop meer
                } else {
                    // Voeg de highlight toe aan de aangeklikte knop
                    button.classList.add('highlight');
                    activeButton = button;  // Markeer deze knop als actief

                    // Verplaats de date-ctn onder de aangeklikte knop
                    button.parentNode.insertBefore(dateCtn, button.nextSibling);
                    dateCtn.style.display = "block"; // Maak het zichtbaar
                }

                // Zet de geselecteerde dag in het formulier
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
