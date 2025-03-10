<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db/conn.php';

if (!isset($_SESSION['role'])) {
    die("Geen toegang. Log in om verder te gaan.");
}

$role = $_SESSION['role'];
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

<div id="mySidebar" class="sidebar">
    <button class="close-btn" onclick="toggleSidebar()">❌</button>
    <img src="img/logo.png" alt="Profile Image">

    <a href="index.php">Home</a>

    <?php if ($role === 'admin'): ?>
        <a href="404.php">Downloads</a>
        <a href="404.php">Klanten toewijzen</a>
        <a href="profiel.php">Profiel</a>

    <?php elseif ($role === 'klant'): ?>
        <a href="profiel.php">Profiel</a>
        <a href="klant-dashboard.php">Klanten Dashboard</a>

    <?php elseif ($role === 'user'): ?>
        <a href="uren-registreren.php">Uren invoeren</a>
        <a href="profiel.php">Profiel</a>
        <a href="gebruiker_uren.php">Gebruiker Uren</a>
    <?php endif; ?>

    <a href="uitloggen.php" class="logout-btn">Uitloggen</a>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    var sidebar = document.getElementById("mySidebar");
    var toggleBtn = document.getElementById("toggleBtn");

    // Check of de sidebar open was
    if (localStorage.getItem("sidebarOpen") === "true") {
        sidebar.style.transition = "none"; // Geen animatie bij laden
        sidebar.style.left = "0";
        toggleBtn.style.display = "none";

        setTimeout(() => {
            sidebar.style.transition = "0.5s ease"; // Zet animatie terug na laden
        }, 100);
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

    // Sluit de sidebar als er buiten de sidebar wordt geklikt
    document.addEventListener("click", function (event) {
        if (sidebar.style.left === "0px" && !sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.style.left = "-250px";
            localStorage.setItem("sidebarOpen", "false");
            toggleBtn.style.display = "block";
        }
    });
});

</script>

</body>
</html>
