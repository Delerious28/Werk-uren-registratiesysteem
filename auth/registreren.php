<?php include_once "../template/header.php"; ?>
<title>Registreren</title>
<link href="../css/inloggen.css" rel="stylesheet">

<main>
    <h1 class="form-h">Registreren</h1>

    <form action="../auth/registreren.php" method="POST" class="form">
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
include "../db/conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($name) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            $name = mysqli_real_escape_string($conn, $name);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $check_user_sql = "SELECT * FROM users WHERE name = '$name'";
            $check_result = mysqli_query($conn, $check_user_sql);

            if (mysqli_num_rows($check_result) == 0) {
                $insert_sql = "INSERT INTO users (name, password, role) VALUES ('$name', '$hashed_password', 'user')";
                if (mysqli_query($conn, $insert_sql)) {
                    header("location: inloggen.php");
                } else {
                    echo "<p>Er is een fout opgetreden. Probeer het later opnieuw.</p>";
                }
            } else {
                echo "<p>Gebruikersnaam bestaat al. Kies een andere naam.</p>";
            }
        } else {
            echo "<p>Wachtwoorden komen niet overeen.</p>";
        }
    } else {
        echo "<p>Vul alle velden in.</p>";
    }
}
?>

