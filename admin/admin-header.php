<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="../css/admin-header.css">

<div class="sidebar">
    <a href="admin-index.php" class="sidebar-btns <?php echo ($current_page == 'admin-index.php') ? 'active' : ''; ?>">Dashboard</a>
    <a href="admin-download.php" class="sidebar-btns <?php echo ($current_page == 'admin-download.php') ? 'active' : ''; ?>">Download</a>
    <a href="admin-gebruikers.php" class="sidebar-btns <?php echo ($current_page == 'admin-gebruikers.php') ? 'active' : ''; ?>">Gebruikers</a>
    <a href="../uitloggen.php" class="sidebar-btns">Uitloggen</a>
</div>

