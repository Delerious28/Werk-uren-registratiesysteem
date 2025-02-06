<?php
session_start();
require('../fpdf/fpdf.php');
include "../db/conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../inloggen.php");
    exit();
}

$user_id = htmlspecialchars($_SESSION['user_id'], ENT_QUOTES, 'UTF-8');
$user_name = htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8');

// Fetch all users with role 'user'
$sql = "SELECT user_id, name, role FROM users WHERE role = 'user' ORDER BY name ASC";

try {
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error retrieving users: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_user'])) {
        $user_id = intval($_POST['user_id']);
        $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = htmlspecialchars($_POST['role'], ENT_QUOTES, 'UTF-8');

        $update_sql = "UPDATE users SET name = :name, password = :password, role = :role WHERE user_id = :user_id";
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([':name' => $name, ':password' => $password, ':role' => $role, ':user_id' => $user_id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        $delete_sql = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $pdo->prepare($delete_sql);
        $stmt->execute([':user_id' => $user_id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['create_user'])) {
        $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = htmlspecialchars($_POST['role'], ENT_QUOTES, 'UTF-8');

        $insert_sql = "INSERT INTO users (name, password, role) VALUES (:name, :password, :role)";
        $stmt = $pdo->prepare($insert_sql);
        $stmt->execute([':name' => $name, ':password' => $password, ':role' => $role]);
        header("Location: " . $_SERVER['PHP_SELF']);
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
    <link rel="stylesheet" href="admin-index.css">
</head>
<body>
<div class="container">
    <div class="sidebar">
        <div class="menu-item">Dashboard</div>
        <div class="menu-item">Download</div>
        <div class="menu-item active">Gebruikers</div>
        <div class="menu-item">Uitloggen</div>
    </div>
    <div class="content">
        <h1>Gebruikers <button onclick="document.getElementById('createUserForm').style.display='block'">New</button></h1>

        <table>
            <tr>
                <th>Naam</th>
                <th>Role</th>
                <th>Acties</th>
            </tr>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                            <button type="submit" name="delete_user">🗑️</button>
                        </form>
                        <button onclick="editUser('<?= $user['user_id'] ?>', '<?= htmlspecialchars($user['name']) ?>', '<?= $user['role'] ?>')">✏️</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Create User Form -->
        <div id="createUserForm" style="display:none;">
            <h2>Nieuwe Gebruiker</h2>
            <form method="POST">
                <input type="text" name="name" placeholder="Naam" required>
                <input type="password" name="password" placeholder="Wachtwoord" required>
                <select name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" name="create_user">Toevoegen</button>
            </form>
        </div>

        <!-- Edit User Form -->
        <div id="editUserForm" style="display:none;">
            <h2>Gebruiker Bewerken</h2>
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                <input type="text" name="name" id="edit_name" required>
                <input type="password" name="password" placeholder="Nieuw wachtwoord">
                <select name="role" id="edit_role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" name="edit_user">Opslaan</button>
            </form>
        </div>
    </div>
</div>

<script>
    function editUser(id, name, role) {
        document.getElementById('editUserForm').style.display = 'block';
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_role').value = role;
    }
</script>

</body>
</html>
