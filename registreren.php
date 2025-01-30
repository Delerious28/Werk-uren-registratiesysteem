<?php
include_once "header.php";
include "db/conn.php"; // Include the PDO connection

$title = "Registreren";
?>
<title><?php echo $title; ?></title>
<link href="css/inloggen.css" rel="stylesheet">

<main>
    <h1 class="form-h"><?php echo $title; ?></h1>

    <form action="registreren.php" method="POST" class="form">
        <div class="form-row">
            <input type="text" name="name" class="form-input" placeholder="Gebruikersnaam" required>
        </div>

        <div class="form-row">
            <input type="password" name="password" class="form-input" placeholder="Wachtwoord" required>
        </div>

        <div class="form-row">
            <input type="password" name="confirm_password" class="form-input" placeholder="Bevestig wachtwoord" required>
        </div>

        <button type="submit" class="submit-btn">Registreren</button>
    </form>
</main>

<?php
// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($name) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            // Use PDO to query the database and hash the password
            try {
                // Check if the username already exists
                $stmt = $pdo->prepare("SELECT * FROM users WHERE name = :name");
                $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                $stmt->execute();

                if ($stmt->rowCount() == 0) {
                    // If user doesn't exist, hash the password and insert into the database
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $insert_sql = "INSERT INTO users (name, password, role) VALUES (:name, :password, 'user')";
                    $insert_stmt = $pdo->prepare($insert_sql);
                    $insert_stmt->bindParam(':name', $name, PDO::PARAM_STR);
                    $insert_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);

                    if ($insert_stmt->execute()) {
                        header("Location: inloggen.php");
                        exit();
                    } else {
                        echo "<p>Er is een fout opgetreden. Probeer het later opnieuw.</p>";
                    }
                } else {
                    echo "<p>Gebruikersnaam bestaat al. Kies een andere naam.</p>";
                }
            } catch (PDOException $e) {
                echo "<p>Fout bij databaseverbinding: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>Wachtwoorden komen niet overeen.</p>";
        }
    } else {
        echo "<p>Vul alle velden in.</p>";
    }
}
?>
