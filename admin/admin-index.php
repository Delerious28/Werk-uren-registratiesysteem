<?php
session_start();
include "../db/conn.php"; // Database Connectie

// Check als de gebruiker ingelogd is:
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/inloggen.php");
    exit();
}

$user_id = htmlspecialchars($_SESSION['user_id'], ENT_QUOTES, 'UTF-8'); // Sanitize session data
$user_name = htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8'); // Sanitize session data

// Initialiseer de succesberichtvariabele
$success_message = '';

$sql = "SELECT user_id, hours, date FROM hours";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
</head>
<body>
    <?php if (count($rows) > 0): ?>
        <?php foreach ($rows as $row): ?>
            <p>user_id: <?= htmlspecialchars($row["user_id"]) ?> - Name: <?= htmlspecialchars($row["$user_name"]) ?> - Uren: <?= htmlspecialchars($row["hours"]) ?></p>
        <?php endforeach; ?>
    <?php else: ?>
        <p>0 results</p>
    <?php endif; ?>
</body>
</html>
