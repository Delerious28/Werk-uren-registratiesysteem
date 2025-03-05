<?php
session_start();
include "db/conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: inloggen.php");
    exit();
}

$user_id = $_SESSION['user_id']; 

$query = "SELECT name, achternaam FROM users WHERE user_id = :user_id"; 
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$first_name = $user ? $user['name'] : 'Gebruiker';
$last_name = $user ? $user['achternaam'] : ''; 

$username = $first_name . ' ' . $last_name;

$query_hours = "SELECT SUM(hours) AS total_hours, SUM(contract_hours) AS total_contract_hours 
                FROM hours 
                WHERE user_id = :user_id";
$stmt_hours = $pdo->prepare($query_hours);
$stmt_hours->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_hours->execute();
$hours_data = $stmt_hours->fetch(PDO::FETCH_ASSOC);

$total_hours = $hours_data['total_hours'] ?? 0;
$total_contract_hours = $hours_data['total_contract_hours'] ?? 1; 

$percentage = ($total_hours / $total_contract_hours) * 100;
$percentage = min($percentage, 100); 

$remaining_hours = $total_contract_hours - $total_hours;

$query_project = "SELECT p.project_naam, k.bedrijfnaam 
                  FROM project p
                  JOIN klant k ON p.klant_id = k.klant_id
                  WHERE p.user_id = :user_id LIMIT 1";
$stmt_project = $pdo->prepare($query_project);
$stmt_project->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_project->execute();
$project_data = $stmt_project->fetch(PDO::FETCH_ASSOC);

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
        <h1 id="percentageText"><!--Text--><?php echo (100 - round($percentage, 2)); ?>%<!--Text--></h1>
        <div class="progress-bar-container">
            <div class="progress-bar" id="progress" style="width: <?php echo round($percentage, 2); ?>%;"></div>
        </div>
        <h3>Te werken uren: <?php echo $remaining_hours; ?> uur</h3>
    </div>
    <div class="container boven-container-rechts">
        <h4 id="workText"><?php echo htmlspecialchars($company_name); ?></h4>
    </div>
    <div class="foto-container">
        <img src="img/logoindex-modified.png" alt="Foto" class="midden-foto">
    </div>
    <div class="container onder-container">
        <div class="welkom-container" id="welkomContainer">
            <h2>Welkom, <span id="username"><?php echo htmlspecialchars($username); ?></span></h2>
        </div>
    </div>
</div>

<script>
        const bovenContainerLinks = document.querySelector('.boven-container-links');
        const percentageText = document.getElementById('percentageText');
        const h3Text = document.querySelector('.boven-container-links h3');

        bovenContainerLinks.addEventListener('animationend', () => {
            percentageText.classList.add('visible');
            h3Text.classList.add('visible');
        });

        const bovenContainerRechts = document.querySelector('.boven-container-rechts');
        const workText = document.getElementById('workText');

        bovenContainerRechts.addEventListener('animationend', () => {
            workText.classList.add('visible');
        });

        const onderContainer = document.querySelector('.onder-container');
        const welkomContainer = document.getElementById('welkomContainer');

        onderContainer.addEventListener('animationend', () => {
            welkomContainer.classList.add('visible');
        });
</script>

</body>
</html>
