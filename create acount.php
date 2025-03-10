<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: inloggen.php");
    exit();
}

require 'db/conn.php';
require 'sidebar.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['voornaam'], $_POST['achternaam'], $_POST['email'], $_POST['telefoon'], $_POST['password'])) {
    $role = $_POST['role'];
    $voornaam = $_POST['voornaam'];
    $achternaam = $_POST['achternaam'];
    $email = $_POST['email'];
    $telefoon = $_POST['telefoon'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if ($role == 'klant') {
        $bedrijfnaam = $_POST['bedrijfnaam'] ?? null;
        $sql = "INSERT INTO klant (voornaam, achternaam, email, telefoon, password, role, bedrijfnaam) 
                VALUES (:voornaam, :achternaam, :email, :telefoon, :password, :role, :bedrijfnaam)";
    } else {
        $sql = "INSERT INTO users (name, achternaam, email, telefoon, password, role) 
                VALUES (:voornaam, :achternaam, :email, :telefoon, :password, :role)";
    }

    try {
        $stmt = $pdo->prepare($sql);
        $params = [
            ':voornaam' => $voornaam,
            ':achternaam' => $achternaam,
            ':email' => $email,
            ':telefoon' => $telefoon,
            ':password' => $password,
            ':role' => $role
        ];

        if ($role == 'klant') {
            $params[':bedrijfnaam'] = $bedrijfnaam;
        }

        $stmt->execute($params);
        echo "Registratie succesvol!";
    } catch (PDOException $e) {
        echo "Fout bij registreren: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registratie</title>
</head>
<body>
    <h2>Registratieformulier</h2>
    
    <form method="POST">
        <label for="role">Kies rol:</label>
        <select id="role" name="role" onchange="this.form.submit()">
            <option value="" disabled selected>Selecteer rol</option>
            <option value="user">User</option>
            <option value="admin">Admin</option>
            <option value="klant">Klant</option>
        </select>
    </form>

    <?php if(isset($_POST['role'])): ?>
    <form method="POST">
        <input type="hidden" name="role" value="<?php echo htmlspecialchars($_POST['role']); ?>">
        <label>Voornaam:</label>
        <input type="text" name="voornaam" required>
        <label>Achternaam:</label>
        <input type="text" name="achternaam" required>
        <label>Email:</label>
        <input type="email" name="email" required>
        <label>Telefoon:</label>
        <input type="text" name="telefoon" required>
        <label>Wachtwoord:</label>
        <input type="password" name="password" required>
        
        <?php if($_POST['role'] == 'klant'): ?>
            <label>Bedrijf:</label>
            <input type="text" name="bedrijfnaam">
        <?php endif; ?>
        
        <button type="submit">Registreren</button>
    </form>
    <?php endif; ?>
</body>
</html>
