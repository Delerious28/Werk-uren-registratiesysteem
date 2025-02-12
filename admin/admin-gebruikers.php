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

$message = "";

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
        $password = $_POST['password'];
        $role = htmlspecialchars($_POST['role'], ENT_QUOTES, 'UTF-8');

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name = :name AND user_id != :user_id");
        $stmt->execute([':name' => $name, ':user_id' => $user_id]);
        $userExists = $stmt->fetchColumn();

        if (preg_match('/\d/', $name)) {
            $message = "Fout: Naam mag geen cijfers bevatten.";
        } elseif ($userExists) {
            $message = "Fout: Gebruikersnaam bestaat al.";
        } elseif (strlen($password) > 0 && strlen($password) < 5) {
            $message = "Fout: Wachtwoord moet meer dan 4 tekens bevatten.";
        } else {
            $update_sql = "UPDATE users SET name = :name, role = :role";
            $params = [':name' => $name, ':role' => $role, ':user_id' => $user_id];

            if (!empty($password)) {
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                $update_sql .= ", password = :password";
                $params[':password'] = $password_hashed;
            }

            $update_sql .= " WHERE user_id = :user_id";
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute($params);
            $message = "Gebruiker succesvol bijgewerkt.";
        }
    }

    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        $delete_sql = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $pdo->prepare($delete_sql);
        $stmt->execute([':user_id' => $user_id]);
        $message = "Gebruiker succesvol verwijderd.";
    }

    if (isset($_POST['create_user'])) {
        $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
        $password = $_POST['password'];
        $role = htmlspecialchars($_POST['role'], ENT_QUOTES, 'UTF-8');

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $userExists = $stmt->fetchColumn();

        if (preg_match('/\d/', $name)) {
            $message = "Fout: Naam mag geen cijfers bevatten.";
        } elseif ($userExists) {
            $message = "Fout: Gebruikersnaam bestaat al.";
        } elseif (strlen($password) < 5) {
            $message = "Fout: Wachtwoord moet meer dan 4 tekens bevatten.";
        } else {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (name, password, role) VALUES (:name, :password, :role)";
            $stmt = $pdo->prepare($insert_sql);
            $stmt->execute([':name' => $name, ':password' => $password_hashed, ':role' => $role]);
            $message = "Nieuwe gebruiker succesvol aangemaakt.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/admin-index.css">
</head>
<body>
<div class="container">

    <?php include 'admin-header.php' ?>

    <div class="content">

        <div class="gebruikers-header-div">
            <div class="gebruikers-header-text1">Gebruikers</div>
            <button class="new-gebruiker-btn" onclick="document.getElementById('createUserForm').style.display='block'">New</button>
        </div>

        <?php if ($message): ?>
            <p><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <div class="naamNroleNacties">
            <div class="naamNroleNacties-naam">Naam</div>
            <div class="naamNroleNacties-role">Role</div>
            <div class="naamNroleNacties-acties">Acties</div>
        </div>

        <table class="gebruikers-tabel">
            <?php foreach ($users as $user): ?>
                <tr class="gebruikers-tr">
                    <td class="naamNuser-icon">
                        <img src="../img/user-icon.png" alt="icon" class="user-icon">
                        <?= htmlspecialchars($user['name']) ?>
                    </td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td class="gebruikers-action-icons">
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                            <button type="submit" name="delete_user" title="Verwijderen">🗑️</button>
                        </form>
                        <button onclick="editUser('<?= $user['user_id'] ?>', '<?= htmlspecialchars($user['name']) ?>', '<?= $user['role'] ?>')" title="Wijzigen">✏️</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div id="createUserForm" style="display:none;">
            <h2>Nieuwe Gebruiker</h2>
            <form method="POST" onsubmit="return validateForm(this)">
                <input type="text" name="name" placeholder="Naam" required pattern="^[^\d]+$" title="Naam mag geen cijfers bevatten">
                <input type="password" name="password" placeholder="Wachtwoord" required minlength="5" title="Wachtwoord moet meer dan 4 tekens bevatten">
                <select name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" name="create_user">Toevoegen</button>
            </form>
        </div>

        <div id="editUserForm" style="display:none;">
            <h2>Gebruiker Bewerken</h2>
            <form method="POST" onsubmit="return validateForm(this)">
                <input type="hidden" name="user_id" id="edit_user_id">
                <input type="text" name="name" id="edit_name" required pattern="^[^\d]+$" title="Naam mag geen cijfers bevatten">
                <input type="password" name="password" placeholder="Nieuw wachtwoord" minlength="5" title="Wachtwoord moet meer dan 4 tekens bevatten">
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

    function validateForm(form) {
        const name = form.querySelector('[name="name"]').value;
        const password = form.querySelector('[name="password"]').value;

        if (/\d/.test(name)) {
            alert('Naam mag geen cijfers bevatten.');
            return false;
        }
        if (password.length > 0 && password.length < 5) {
            alert('Wachtwoord moet meer dan 4 tekens bevatten.');
            return false;
        }
        return true;
    }
</script>

</body>
</html>
