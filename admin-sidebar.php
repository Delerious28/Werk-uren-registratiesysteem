<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="css/admin-dashboard.css" rel="stylesheet">
<link href="css/admin-sidebar.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Toggle Button for Mobile -->
<button id="toggle-sidebar" class="btn btn-primary d-md-none">
    ☰
</button>

<!-- Sidebar -->
<nav id="sidebar" class="col-md-2 sidebar">
    <div class="p-3">
        <h4>Admin Dashboard</h4>
        <div class="list-group">
            <a href="admin-dashboard.php" 
               class="list-group-item <?= ($currentPage === 'admin-dashboard.php') ? 'active' : '' ?>">
                Dashboard
            </a>
            <a href="admin-download.php" 
               class="list-group-item <?= ($currentPage === 'admin-download.php') ? 'active' : '' ?>">
                Download
            </a>
            <a href="profiel.php" 
               class="list-group-item <?= ($currentPage === 'profiel.php') ? 'active' : '' ?>">
                Profiel
            </a>
            <a href="uitloggen.php" 
               class="list-group-item list-group-item-danger <?= ($currentPage === 'uitloggen.php') ? 'active' : '' ?>">
                Uitloggen
            </a>
        </div>
    </div>
</nav>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggleButton = document.getElementById("toggle-sidebar");

    toggleButton.addEventListener("click", function () {
        if (sidebar.classList.contains("open")) {
            sidebar.classList.remove("open"); // Sluit de sidebar
        } else {
            sidebar.classList.add("open"); // Open de sidebar
        }
    });
});
</script>

<style>
/* Mobile Sidebar */
@media (max-width: 768px) {
    #sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 250px;
        height: 100%;
        background: #fff;
        box-shadow: 2px 0 5px rgba(0,0,0,0.2);
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
        z-index: 1000;
    }

    #sidebar.open {
        transform: translateX(0);
    }

    #toggle-sidebar {
        border-radius: 10px !important;
        border: none !important;
        background-color: #6d0f10 !important;
        position: fixed;
        top: 10px;
        left: 10px;
        z-index: 1100;
        
    }
}
</style>
