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
