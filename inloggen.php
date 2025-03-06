<?php
session_start(); // Start de sessie bovenaan voor sessiebeheer

include "db/conn.php"; // Inclusie van de PDO databaseverbinding

// Controleer of het formulier is ingediend
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    // Controleer of gebruikersnaam en wachtwoord zijn ingevuld
    if (!empty($_POST['name']) && !empty($_POST['password'])) {
        $name = $_POST['name']; // Verkrijg de gebruikersnaam
        $password = $_POST['password']; // Verkrijg het wachtwoord

        // Gebruik PDO om de database te raadplegen
        $stmt = $pdo->prepare("SELECT * FROM users WHERE name = :name");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR); // Bind de gebruikersnaam
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC); // Haal de gebruiker op

        if ($user) {
            // Controleer of het wachtwoord correct is
            if (password_verify($password, $user['password'])) {
                // Stel sessievariabelen in bij succesvolle inlog
                $_SESSION['login'] = true; // Zet de sessie als ingelogd
                $_SESSION['user'] = $user['name']; // Zet de gebruikersnaam in de sessie
                $_SESSION['user_id'] = $user['user_id']; // Zet de user_id in de sessie
                $_SESSION['role'] = $user['role']; // Zet de rol van de gebruiker in de sessie

                // Redirect naar index.php voor zowel admin als user
                header("Location: index.php");
                exit(); // Zorg ervoor dat er geen verdere code wordt uitgevoerd na de redirect
            } else {
                $error_message = "Ongeldig wachtwoord!"; // Foutmelding bij ongeldig wachtwoord
            }
        } else {
            $error_message = "Gebruiker niet gevonden!"; // Foutmelding als gebruiker niet gevonden wordt
        }
    } else {
        $error_message = "Vul alle velden in!"; // Foutmelding als niet alle velden zijn ingevuld
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <title>Inloggen</title>
    <link href="css/inloggen-registreren.css" rel="stylesheet">
</head>

<body>

<?php include_once "header.php"; ?> <!-- Inclusie van de header -->

<main>
    <h1 class="form-h">Inloggen</h1> <!-- Titel van het inlogformulier -->
    <form method="POST" class="form">
        <div class="form-row">
            <input type="text" id="nameInput" class="form-input" placeholder="Gebruikersnaam" name="name" required> <!-- Invoerveld voor gebruikersnaam -->
        </div>

        <div class="form-row">
            <input type="password" id="passwordInput" class="form-input" placeholder="Wachtwoord" name="password" required> <!-- Invoerveld voor wachtwoord -->
        </div>

        <button type="submit" class="submit-btn">Login</button> <!-- Verzenden van het formulier -->
    </form>

    <?php
    // Als er een foutmelding is, toon deze dan
    if (isset($error_message)) {
        echo "<p style='color: red;'>$error_message</p>"; // Foutmelding in rode tekst
    }
    ?>
</main>

</body>
</html>
