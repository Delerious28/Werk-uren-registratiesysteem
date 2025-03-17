<?php
session_start(); // Start de sessie voor sessiebeheer
include "db/conn.php"; // Inclusie van de PDO databaseverbinding

// Controleer of het formulier is ingediend
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    // Controleer of e-mail en wachtwoord zijn ingevuld
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $email = $_POST['email'];       // Verkrijg het e-mailadres
        $password = $_POST['password']; // Verkrijg het wachtwoord

        // Zoek eerst in de users tabel op basis van e-mail
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Als de gebruiker niet is gevonden in de users tabel, zoek dan in de klant tabel
        if (!$user) {
            $stmt = $pdo->prepare("SELECT * FROM klant WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($user) {
            // Controleer of het wachtwoord correct is
            if (password_verify($password, $user['password'])) {
                // Stel sessievariabelen in bij succesvolle inlog
                $_SESSION['login'] = true;
                // Gebruik 'name' als beschikbaar, anders 'voornaam'
                $_SESSION['user'] = $user['name'] ?? $user['voornaam'];
                // Gebruik 'user_id' als beschikbaar, anders 'klant_id'
                $_SESSION['user_id'] = $user['user_id'] ?? $user['klant_id'];
                $_SESSION['role'] = $user['role'];

                // Redirect naar het juiste dashboard afhankelijk van de rol
                if ($user['role'] == 'admin') {
                    header("Location: admin-dashboard.php");
                } elseif ($user['role'] == 'klant') {
                    header("Location: klant-dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit(); // Stop verdere verwerking
            } else {
                $error_message = "Ongeldig wachtwoord!";
            }
        } else {
            $error_message = "Gebruiker niet gevonden!";
        }
    } else {
        $error_message = "Vul alle velden in!";
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <title>Inloggen</title>
    <link href="css/inloggen.css" rel="stylesheet">
</head>
<body>
<main>
    <h1 class="form-h">Inloggen</h1>
    <form method="POST" class="form">
        <div class="form-row">
            <!-- Invoerveld voor e-mailadres -->
            <input type="email" id="emailInput" class="form-input" placeholder="Email" name="email" required>
        </div>
        <div class="form-row">
            <input type="password" id="passwordInput" class="form-input" placeholder="Wachtwoord" name="password" required>
        </div>
        <button type="submit" class="submit-btn">Login</button>
    </form>
    <?php
    // Als er een foutmelding is, toon deze dan
    if (isset($error_message)) {
        echo "<p style='color: red;'>$error_message</p>";
    }
    ?>
</main>
</body>
</html>
