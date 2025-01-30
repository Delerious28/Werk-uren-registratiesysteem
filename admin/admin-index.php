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

// Handle filter selection
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // Default to 'all'

$date_condition = ""; // Default to no date condition
if ($filter === 'vandaag') {
    $date_condition = "WHERE DATE(hours.date) = CURDATE()"; // Today's records
} elseif ($filter === 'week') {
    $date_condition = "WHERE YEARWEEK(hours.date, 1) = YEARWEEK(CURDATE(), 1)"; // Current week
} elseif ($filter === 'maand') {
    $date_condition = "WHERE MONTH(hours.date) = MONTH(CURDATE()) AND YEAR(hours.date) = YEAR(CURDATE())"; // Current month
}

// SQL query with filter condition
$sql = "
    SELECT 
        hours.hours_id,
        hours.user_id, 
        users.name, 
        hours.hours, 
        hours.date, 
        hours.accord 
    FROM hours
    JOIN users ON hours.user_id = users.user_id
    $date_condition
    ORDER BY hours.date ASC
";

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error retrieving data: " . $e->getMessage());
}

// Handle form submissions for editing or completing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_hours'])) {
        // Update hours in the database
        $new_hours = htmlspecialchars($_POST['new_hours'], ENT_QUOTES, 'UTF-8');
        $hours_id = intval($_POST['hours_id']);

        $update_sql = "UPDATE hours SET hours = :hours WHERE hours_id = :hours_id";
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([':hours' => $new_hours, ':hours_id' => $hours_id]);

        header("Location: " . $_SERVER['PHP_SELF'] . "?filter=" . urlencode($filter));
        exit();
    }

    if (isset($_POST['complete_status'])) {
        // Update accord status to 'Completed'
        $hours_id = intval($_POST['hours_id']);

        $update_sql = "UPDATE hours SET accord = 'Approved' WHERE hours_id = :hours_id";
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([':hours_id' => $hours_id]);

        header("Location: " . $_SERVER['PHP_SELF'] . "?filter=" . urlencode($filter));
        exit();
    }
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

        <!-- Filter Dropdown -->
        <form class="filter-form" method="GET" action="">
            <label for="filter">Filter op:</label>
            <select name="filter" id="filter" onchange="this.form.submit()">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Alles</option>
                <option value="vandaag" <?= $filter === 'vandaag' ? 'selected' : '' ?>>Vandaag</option>
                <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Week</option>
                <option value="maand" <?= $filter === 'maand' ? 'selected' : '' ?>>Maand</option>
            </select>
        </form>

        <?php if (count($rows) > 0): ?>
            <?php 
            $currentDay = ''; // Track the current day for week filtering
            foreach ($rows as $row): 
            ?>
                <?php if ($filter === 'week' && date('l', strtotime($row["date"])) !== $currentDay): ?>
                    <!-- Display day heading for weekly filtering -->
                    <h3><?= htmlspecialchars(date('l', strtotime($row["date"]))) ?></h3>
                    <?php $currentDay = date('l', strtotime($row["date"])); ?>
                <?php endif; ?>

                <div class="record">
                    <div>
                        <p><strong>Name:</strong> <?= htmlspecialchars($row["name"]) ?></p>
                        <p><strong>Hours Worked:</strong> <?= htmlspecialchars($row["hours"]) ?></p>
                        <p><strong>Date:</strong> <?= htmlspecialchars($row["date"]) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($row["accord"]) ?></p>
                    </div>
                    <div class="buttons">
                        <!-- Edit Hours Form -->
                        <form method="POST" action="">
                            <input type="hidden" name="hours_id" value="<?= htmlspecialchars($row['hours_id']) ?>">
                            <input type="number" name="new_hours" value="<?= htmlspecialchars($row['hours']) ?>" min="0" max="24" required>
                            <button type="submit" name="edit_hours">Edit</button>
                        </form>

                        <!-- Complete Status Form -->
                        <form method="POST" action="">
                            <input type="hidden" name="hours_id" value="<?= htmlspecialchars($row['hours_id']) ?>">
                            <button type="submit" name="complete_status">Approved</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No records found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
