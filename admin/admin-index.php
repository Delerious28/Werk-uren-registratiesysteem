<?php
session_start();
include "../db/conn.php"; // Database Connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/inloggen.php");
    exit();
}

// Sanitize session data
$user_id = htmlspecialchars($_SESSION['user_id'], ENT_QUOTES, 'UTF-8'); 
$user_name = htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8');

// SQL query to retrieve user hours with user names
$sql = "
    SELECT 
        hours.user_id, 
        users.name, 
        hours.hours, 
        hours.date, 
        hours.accord 
    FROM hours
    JOIN users ON hours.user_id = users.user_id
";

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error retrieving data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <?php if (count($rows) > 0): ?>
            <?php foreach ($rows as $row): ?>
                <div class="record">
                    <p><strong>Name:</strong> <?= htmlspecialchars($row["name"]) ?></p>
                    <p><strong>Hours Worked:</strong> <?= htmlspecialchars($row["hours"]) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No records found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
