<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db/conn.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['user', 'klant'])) {
    header("Location: inloggen.php");
    exit();
}

$role = $_SESSION['role'];

// Controleer of de huidige pagina de downloadpagina is
$currentPage = basename($_SERVER['PHP_SELF']);
$isDownloadPage = ($currentPage === 'admin-download.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/sidebar.css" rel="stylesheet">
</head>
<body>

<button class="toggle-btn" id="toggleBtn" onclick="toggleSidebar()">☰</button>

<div id="mySidebar" class="sidebar" style="<?= $isDownloadPage ? 'left: 0;' : 'left: -250px;' ?>">
    <button class="close-btn" onclick="toggleSidebar()">❌</button>
    <img src="img/logo.png" alt="Profile Image">

    <?php if ($role === 'admin'): ?>
        <a href="admin-dashboard.php">Dashboard</a>
        <a href="profiel.php">Profiel</a>
        <a href="admin-download.php">Download</a>
    <?php elseif ($role === 'klant'): ?>
        <a href="klant-dashboard.php">Dashboard</a>
        <a href="profiel.php">Profiel</a>
    <?php elseif ($role === 'user'): ?>
        <a href="index.php">Home</a>
        <a href="uren-registreren.php">Uren registreren</a>
        <a href="gebruiker_uren.php">Urenoverzicht</a>
        <a href="profiel.php">Bedrijfsinformatie </a>
    <?php endif; ?>

    <a href="uitloggen.php" class="logout-btn">Uitloggen</a>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var sidebar = document.getElementById("mySidebar");
        var toggleBtn = document.getElementById("toggleBtn");

        // Als de huidige pagina de downloadpagina is, open de sidebar standaard
        var isDownloadPage = <?= $isDownloadPage ? 'true' : 'false'; ?>;

        if (isDownloadPage) {
            sidebar.style.left = "0";
            toggleBtn.style.display = "none";
            localStorage.setItem("sidebarOpen", "true");
        } else {
            // Check of de sidebar open was
            if (localStorage.getItem("sidebarOpen") === "true") {
                sidebar.style.transition = "none"; // Geen animatie bij laden
                sidebar.style.left = "0";
                toggleBtn.style.display = "none";

                setTimeout(() => {
                    sidebar.style.transition = "0.5s ease"; // Zet animatie terug na laden
                }, 100);
            }
        }

        // Toggle Sidebar open/close
        window.toggleSidebar = function () {
            if (sidebar.style.left === "0px") {
                sidebar.style.left = "-250px";
                localStorage.setItem("sidebarOpen", "false");
                toggleBtn.style.display = "block";
            } else {
                sidebar.style.transition = "0.5s ease"; // Alleen animatie bij klikken
                sidebar.style.left = "0";
                localStorage.setItem("sidebarOpen", "true");
                toggleBtn.style.display = "none";
            }
        };

        // Voeg de event listener alleen toe als het NIET de downloadpagina is
        if (!isDownloadPage) {
            document.addEventListener("click", function (event) {
                if (sidebar.style.left === "0px" && !sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.style.left = "-250px";
                    localStorage.setItem("sidebarOpen", "false");
                    toggleBtn.style.display = "block";
                }
            });
        }
    });
</script>

</body>
</html>