<!DOCTYPE html>
<html lang="nl">
<head>
    <title>Inloggen</title>
    <link href="../css/inloggen.css" rel="stylesheet">
</head>
<body>
<?php include_once "../template/header.php"; ?>

<main>
    <h1 class="form-h">Inloggen</h1>
    <form action="../auth/inloggen.php" method="POST" class="form">
        <div class="form-row">
            <input type="text" id="nameInput" class="form-input" placeholder="Gebruikersnaam" name="name" required>
        </div>

        <div class="form-row">
            <input type="password" id="passwordInput" class="form-input" placeholder="Wachtwoord" name="password" required>
        </div>

        <button type="submit" class="submit-btn">Login</button>
    </form>

    <?php
    session_start();
    include "../db/conn.php";

    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        if (!empty($_POST['name']) && !empty($_POST['password'])) {
            $name = $_POST['name'];
            $password = $_POST['password'];

            $stmt = $conn->prepare("SELECT * FROM users WHERE name = ?");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['login'] = true;
                    $_SESSION['user'] = $user['name'];
                    header("Location: ../index.php");
                    exit();
                } else {
                    echo "<p>Ongeldig wachtwoord!</p>";
                }
            } else {
                echo "<p>Gebruiker niet gevonden!</p>";
            }
        } else {
            echo "<p>Vul alle velden in!</p>";
        }
    }
    ?>

</main>
</body>
</html>
