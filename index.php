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
        <button class="progress-info-popup" data-user-id="<?php echo $_SESSION['user_id']; ?>">
            <h4 id="workText1">Project voortgang</h4>
        </button>
    </div>
    <div class="container boven-container-rechts">
        <button class="project-info-popup" data-project-id="<?php echo $project_id; ?>" data-user-id="<?php echo $_SESSION['user_id']; ?>">
            <h4 id="workText2">Alle projecten</h4>
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
        <div class="popup-header">
            <h6></h6>
            <span class="close">&times;</span>
        </div>
        <div id="popup-content"></div>
    </div>
</div>


<script src="js/index.js"></script>
<script>
</body>
</html>
