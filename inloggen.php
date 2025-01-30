<?php
session_start(); // Start session at the top for session management

include "db/conn.php"; // Include the PDO connection

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (!empty($_POST['name']) && !empty($_POST['password'])) {
        $name = $_POST['name'];
        $password = $_POST['password'];

        // Use PDO to query the database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE name = :name");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Set session variables upon successful login
                $_SESSION['login'] = true;
                $_SESSION['user'] = $user['name'];
                $_SESSION['user_id'] = $user['user_id']; // Store user_id in session
                $_SESSION['role'] = $user['role']; // Store user role

                // Redirect to the home page after successful login
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Ongeldig wachtwoord!"; // Invalid password message
            }
        } else {
            $error_message = "Gebruiker niet gevonden!"; // User not found message
        }
    } else {
        $error_message = "Vul alle velden in!"; // Missing fields message
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
<?php include_once "header.php"; ?>

<main>
    <h1 class="form-h">Inloggen</h1>
    <form method="POST" class="form">
        <div class="form-row">
            <input type="text" id="nameInput" class="form-input" placeholder="Gebruikersnaam" name="name" required>
        </div>

        <div class="form-row">
            <input type="password" id="passwordInput" class="form-input" placeholder="Wachtwoord" name="password" required>
        </div>

        <button type="submit" class="submit-btn">Login</button>
    </form>

    <?php
    if (isset($error_message)) {
        echo "<p style='color: red;'>$error_message</p>";
    }
    ?>
</main>

</body>
</html>
