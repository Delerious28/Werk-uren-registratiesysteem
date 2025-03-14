<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/admin-dashboard.css" rel="stylesheet">
    <link href="css/admin-sidebar.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<nav class="col-md-2 sidebar">
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