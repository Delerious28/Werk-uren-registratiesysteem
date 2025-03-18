<?php
session_start();
include "db/conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: inloggen.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Haal gebruikersgegevens op
$query = "SELECT name, achternaam FROM users WHERE user_id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$first_name = $user ? $user['name'] : 'Gebruiker';
$last_name = $user ? $user['achternaam'] : '';
$username = $first_name . ' ' . $last_name;

// Haal urengegevens op en de contract_uren van het project
$query_hours = "
    SELECT SUM(h.hours) AS total_hours, p.contract_uren 
    FROM hours h
    JOIN project p ON h.project_id = p.project_id
    WHERE h.user_id = :user_id
";
$stmt_hours = $pdo->prepare($query_hours);
$stmt_hours->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_hours->execute();
$hours_data = $stmt_hours->fetch(PDO::FETCH_ASSOC);

$total_hours = $hours_data['total_hours'] ?? 0;
$total_contract_hours = $hours_data['contract_uren'] ?? 1; // Zet op 1 als het niet bestaat

// Zorg ervoor dat $total_contract_hours niet nul is voor de berekening
if ($total_contract_hours == 0) {
    $total_contract_hours = 1; // Zet het op 1 als het 0 is om deling door nul te voorkomen
}

$percentage = ($total_hours / $total_contract_hours) * 100;
$percentage = min($percentage, 100);
$remaining_hours = $total_contract_hours - $total_hours;

// Haal projectgegevens op via project_users
$query_project = "SELECT p.project_id, p.project_naam, k.bedrijfnaam 
                  FROM project_users pu
                  JOIN project p ON pu.project_id = p.project_id
                  JOIN klant k ON p.klant_id = k.klant_id
                  WHERE pu.user_id = :user_id
                  LIMIT 1";

$stmt_project = $pdo->prepare($query_project);
$stmt_project->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_project->execute();
$project_data = $stmt_project->fetch(PDO::FETCH_ASSOC);
$project_id = $project_data ? $project_data['project_id'] : 0; // Zorg ervoor dat project_id beschikbaar is

$project_name = $project_data ? $project_data['project_naam'] : 'Onbekend project';
$company_name = $project_data ? $project_data['bedrijfnaam'] : 'Onbekend bedrijf';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina met Containers</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include 'sidebar.php'; ?>
<div class="start">
    <div class="container boven-container-links">
        <h1 id="percentageText"><?php echo (100 - round($percentage, 2)); ?>%</h1>
        <div class="progress-bar-container">
            <div class="progress-bar" id="progress" style="width: <?php echo round($percentage, 2); ?>%;"></div>
        </div>
        <h3>Project uren: <?php echo $remaining_hours; ?> uur</h3>
    </div>
    <div class="container boven-container-rechts">
        <button class="project-info-popup" data-project-id="<?php echo $project_id; ?>" data-user-id="<?php echo $_SESSION['user_id']; ?>">
            <h4 id="workText">Alle projecten</h4>
        </button>
    </div>
    <div class="foto-container">
        <img src="img/logoindex-modified.png" alt="Foto" class="midden-foto">
    </div>
    <div class="container onder-container">
        <div class="welkom-container" id="welkomContainer">
            <h2>Welkom, <span id="username"><?php echo htmlspecialchars($username); ?>!</span></h2>
        </div>
    </div>
</div>

<!-- Pop-up overlay -->
<div id="popup-overlay" class="popup-overlay">
    <div id="popup" class="pop-up">
        <span class="close">&times;</span>
        <h6>Gekoppelde projecten</h6>
        <!-- Nieuw element om de projectinformatie weer te geven -->
        <div id="popup-content"></div>
    </div>
</div>

<script src="js/index.js"></script>
<script>
</body>
</html>
