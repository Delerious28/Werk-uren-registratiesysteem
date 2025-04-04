<?php
session_start();
include "db/conn.php";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Eerst proberen als gebruiker
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Als de gebruiker niet bestaat, proberen als klant
        if (!$user) {
            $stmt = $pdo->prepare("SELECT * FROM klant WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['login'] = true;
                $_SESSION['user'] = $user['name'] ?? $user['voornaam'];  // Voornaam of naam, afhankelijk van de tabel
                $_SESSION['role'] = $user['role'];

                // Stel de juiste user_id of klant_id in op basis van de ingelogde gebruiker
                if (isset($user['user_id'])) {
                    // Stel user_id in voor gebruikers
                    $_SESSION['user_id'] = $user['user_id'];
                } else {
                    // Stel klant_id in voor klanten
                    $_SESSION['klant_id'] = $user['klant_id'];
                }

                // Redirect op basis van rol
                if ($user['role'] == 'admin') {
                    header("Location: admin-dashboard.php");
                } elseif ($user['role'] == 'klant') {
                    header("Location: klant-dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inloggen</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="css/inloggen.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
    <div class="login-info">
        <h2>Welkom Terug</h2>
        <p>Log in op uw account om toegang te krijgen. Mocht u nog geen account hebben, neem dan gerust contact met ons op voor meer informatie.</p>
    </div>
    <div class="login-form-container">
        <div class="login-header">Inloggen</div>
        <form method="POST" class="login-form">
            <input type="email" id="emailInput" placeholder="Email" name="email" required>
            <input type="password" id="passwordInput" placeholder="Wachtwoord" name="password" required>
            <button type="submit">Login</button>
        </form>
        <?php if (isset($error_message)) { ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php } ?>
    </div>
</div>
</body>
</html>
