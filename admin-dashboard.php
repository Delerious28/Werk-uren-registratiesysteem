<?php
require __DIR__ . '/db/conn.php';
session_start();

// Controleer admin rechten
if (!isset($_SESSION['role'])) {
    header("Location: inloggen.php");
    exit();
} elseif ($_SESSION['role'] !== 'admin') {
    die("Geen toegang!");
}

// Verwerk verwijderingsactie
if (isset($_POST['delete_user'])) {
    $userId = $_POST['delete_user'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        header("Location: admin-dashboard.php#users");
        exit();
    } catch (PDOException $e) {
        die("Fout bij verwijderen: " . $e->getMessage());
    }
}

// Haal gegevens op
try {
    $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $klantenCount = $pdo->query("SELECT COUNT(*) FROM klant")->fetchColumn();
    $pendingHoursCount = $pdo->query("SELECT COUNT(*) FROM hours WHERE accord = 'Pending'")->fetchColumn();
    $projects = $pdo->query("SELECT project_id, project_naam, klant_id, beschrijving, contract_uren FROM project")->fetchAll(PDO::FETCH_ASSOC);
    $hours = $pdo->query("SELECT * FROM hours")->fetchAll(PDO::FETCH_ASSOC);
    $klanten = $pdo->query("SELECT * FROM klant")->fetchAll(PDO::FETCH_ASSOC);
    $users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database fout: " . $e->getMessage());
}

// Project toevoegen met contract_uren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) {
    $project_naam = $_POST['project_naam'];
    $klant_id = $_POST['klant_id'];
    $beschrijving = $_POST['beschrijving'];
    $contract_uren = $_POST['contract_uren'];

    try {
        $stmt = $pdo->prepare("INSERT INTO project 
            (project_naam, klant_id, beschrijving, contract_uren) 
            VALUES (?, ?, ?, ?)");
        $stmt->execute([$project_naam, $klant_id, $beschrijving, $contract_uren]);
        
        header("Location: admin-dashboard.php#projects");
        exit();
    } catch (PDOException $e) {
        die("Fout bij toevoegen project: " . $e->getMessage());
    }
}
// Haal gegevens op
try {
    $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $klantenCount = $pdo->query("SELECT COUNT(*) FROM klant")->fetchColumn();
    $pendingHoursCount = $pdo->query("SELECT COUNT(*) FROM hours WHERE accord = 'Pending'")->fetchColumn();
    $projects = $pdo->query("SELECT * FROM project")->fetchAll(PDO::FETCH_ASSOC);
    $hours = $pdo->query("SELECT * FROM hours")->fetchAll(PDO::FETCH_ASSOC);
    $klanten = $pdo->query("SELECT * FROM klant")->fetchAll(PDO::FETCH_ASSOC);
    $users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database fout: " . $e->getMessage());
}

// Verwerk gebruikersupdate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = $_POST['user_id'];
    $name = $_POST['name'];
    $achternaam = $_POST['achternaam'];
    $email = $_POST['email'];
    $telefoon = $_POST['telefoon'];
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, achternaam = ?, email = ?, telefoon = ?, role = ? WHERE user_id = ?");
        $stmt->execute([$name, $achternaam, $email, $telefoon, $role, $userId]);
        
        // Als Ajax, stuur JSON terug
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Gebruiker succesvol bijgewerkt']);
            exit();
        } else {
            header("Location: admin-dashboard.php?success=1#users");
            exit();
        }
    } catch (PDOException $e) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Fout bij bijwerken: " . $e->getMessage()]);
            exit();
        } else {
            die("Fout bij bijwerken: " . $e->getMessage());
        }
    }
}


// Bereken de uren per project
$projectHours = [];
foreach ($projects as $project) {
    $projectId = $project['project_id'];
    $projectHours[$project['project_naam']] = 0;
    foreach ($hours as $hour) {
        if ($hour['project_id'] == $projectId && isset($hour['hours'])) {
            $projectHours[$project['project_naam']] += $hour['hours'];
        }
    }
}

// Verwerk gebruikersformulier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $role = $_POST['role'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            if ($role === 'klant') {
                if (empty($_POST['bedrijfnaam'])) {
                    throw new Exception("Bedrijfsnaam is verplicht voor klanten!");
                }
                
                $stmt = $pdo->prepare("INSERT INTO klant 
                    (voornaam, achternaam, email, telefoon, password, role, bedrijfnaam) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $_POST['name'],
                    $_POST['achternaam'],
                    $_POST['email'],
                    $_POST['telefoon'],
                    $password,
                    $role,
                    $_POST['bedrijfnaam']
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO users 
                    (name, achternaam, email, telefoon, password, role) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $_POST['name'],
                    $_POST['achternaam'],
                    $_POST['email'],
                    $_POST['telefoon'],
                    $password,
                    $role
                ]);
            }
            header("Refresh:0");
        } catch (Exception $e) {
            die("Fout: " . $e->getMessage());
        }
    }

    if (isset($_POST['add_project'])) {
        $stmt = $pdo->prepare("INSERT INTO project (project_naam, klant_id, beschrijving) 
                                    VALUES (?, ?, ?)");
        $stmt->execute([
            $_POST['project_naam'],
            $_POST['klant_id'],
            $_POST['beschrijving']
        ]);
    }

 // Verwerk gebruiker toevoegen aan project
if (isset($_POST['assign_user'])) {
    try {
        $project_id = $_POST['project_id'];
        $user_id = $_POST['user_id'];

        // Controleer of het project en de gebruiker bestaan
        $projectExists = $pdo->prepare("SELECT COUNT(*) FROM project WHERE project_id = ?");
        $projectExists->execute([$project_id]);
        $projectExists = $projectExists->fetchColumn();

        $userExists = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
        $userExists->execute([$user_id]);
        $userExists = $userExists->fetchColumn();

        if ($projectExists > 0 && $userExists > 0) {
            $stmt = $pdo->prepare("INSERT INTO project_users (project_id, user_id) VALUES (?, ?)");
            $stmt->execute([$project_id, $user_id]);

            if ($stmt->rowCount() > 0) {
                echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showNotification('Gebruiker succesvol gekoppeld!', 'success');
                        });
                      </script>";
            }
        } else {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showNotification('Project of gebruiker bestaat niet!', 'danger');
                    });
                  </script>";
        }
    } catch (PDOException $e) {
        // Controleer op duplicate entry fout
        if ($e->errorInfo[1] == 1062) {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showNotification('Deze gebruiker is al gekoppeld aan het project', 'danger');
                    });
                  </script>";
        } else {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showNotification('Fout bij koppelen: " . addslashes($e->getMessage()) . "', 'danger');
                    });
                  </script>";
        }
    }
}
}
// Verwerk projectverwijdering
if (isset($_POST['delete_project'])) {
    $projectId = $_POST['delete_project'];
    try {
        $stmt = $pdo->prepare("DELETE FROM project_users WHERE project_id = ?");
        $stmt->execute([$projectId]);
        
        // Verwijder het project
        $stmt = $pdo->prepare("DELETE FROM project WHERE project_id = ?");
        $stmt->execute([$projectId]);
        
        header("Location: admin-dashboard.php#projects");
        exit();
    } catch (PDOException $e) {
        die("Fout bij verwijderen project: " . $e->getMessage());
    }
}
// VERWERK PROJECT-UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_project'])) {
    $project_id = $_POST['project_id'];
    $project_naam = $_POST['project_naam'];
    $klant_id = $_POST['klant_id'];
    $contract_uren = $_POST['contract_uren'];
    $beschrijving = $_POST['beschrijving'];

    try {
        $stmt = $pdo->prepare("UPDATE project SET 
            project_naam = ?, 
            klant_id = ?, 
            contract_uren = ?, 
            beschrijving = ? 
            WHERE project_id = ?");

        $stmt->execute([
            $project_naam,
            $klant_id,
            $contract_uren,
            $beschrijving,
            $project_id
        ]);

        header("Location: admin-dashboard.php?success_project=1#projects");
        exit();

    } catch (PDOException $e) {
        die("Fout bij projectupdate: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/admin-dashboard.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body> 

<div id="notification-container" class="notification" style="display: none;"></div>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 sidebar">
    <div class="p-3">
        <h4>Admin Dashboard</h4>
        <div class="list-group">
            <a href="#dashboard" class="list-group-item active">Dashboard</a>
            <a href="#users" class="list-group-item">Gebruikers</a>
                        <a href="admin-klant.php" class="list-group-item">Klanten</a>

            <a href="#projects" class="list-group-item">Projecten</a>
            <a href="admin-download.php" class="list-group-item">Download</a>
            <a href="admin-profiel.php" class="list-group-item">Profiel</a>
            <a href="uitloggen.php" class="list-group-item list-group-item-danger">Uitloggen</a>
        </div>
    </div>
</nav>


            <main class="col-md-10 p-4">
                <section id="dashboard" class="active-section d-none">                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Gebruikers</h5>
                                    <p class="card-text display-4"><?= $usersCount + $klantenCount ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <h5 class="card-title">Openstaande uren</h5>
                                    <p class="card-text display-4"><?= $pendingHoursCount ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <canvas id="hoursChart"></canvas>
                    </div>
                </section>
                 <!-- Clients Section -->
                 <section id="clients" class="d-none">
                    <h2>Klantenbeheer</h2>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Bedrijfsnaam</th>
                                <th>Contactpersoon</th>
                                <th>Email</th>
                                <th>Telefoon</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($klanten as $klant): ?>
                            <tr>
                                <td><?= htmlspecialchars($klant['bedrijfnaam']) ?></td>
                                <td><?= htmlspecialchars($klant['voornaam']) ?> <?= htmlspecialchars($klant['achternaam']) ?></td>
                                <td><?= htmlspecialchars($klant['email']) ?></td>
                                <td><?= htmlspecialchars($klant['telefoon']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

                <!-- Users Section -->
                <section id="users" class="d-none">
                    <h2>Gebruikersbeheer</h2>
                    <form method="POST" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <input type="text" name="name" class="form-control" placeholder="Voornaam" required>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="achternaam" class="form-control" placeholder="Achternaam" required>
                            </div>
                            <div class="col-md-3">
                                <input type="email" name="email" class="form-control" placeholder="Email" required>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="telefoon" class="form-control" placeholder="Telefoon" required>
                            </div>
                            <div class="col-md-3">
                                <input type="password" name="password" class="form-control" placeholder="Wachtwoord" required>
                            </div>
                            <div class="col-md-3">
                                <select name="role" class="form-select" id="roleSelect">
                                    <option value="user">Gebruiker</option>
                                    <option value="admin">Admin</option>
                                    <option value="klant">Klant</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="bedrijfnaamField" style="display: none;">
                                <input type="text" name="bedrijfnaam" class="form-control" placeholder="Bedrijfsnaam">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="add_user" class="btn btn-success">
                                    <i class="bi bi-person-plus"></i> Toevoegen
                                </button>
                            </div>
                        </div>
                    </form>

                    <table class="table table-striped">
    <thead>
        <tr>
            <th>Naam</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['name']) ?> <?= htmlspecialchars($user['achternaam']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['role']) ?></td>
            <td>
                <!-- Bewerkingsknop met data attributes voor de overlay -->
                <button type="button" class="btn btn-sm btn-primary edit-btn" 
                        data-bs-toggle="modal" data-bs-target="#editUserModal"
                        data-id="<?= $user['user_id'] ?>"
                        data-name="<?= htmlspecialchars($user['name']) ?>"
                        data-achternaam="<?= htmlspecialchars($user['achternaam']) ?>"
                        data-email="<?= htmlspecialchars($user['email']) ?>"
                        data-telefoon="<?= htmlspecialchars($user['telefoon']) ?>"
                        data-role="<?= htmlspecialchars($user['role']) ?>">
                    Bewerken
                </button>

                <!-- Verwijderknop -->
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="delete_user" value="<?= $user['user_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger" 
                            onclick="return confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?')">
                        Verwijderen
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
                </section>
                <!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Gebruiker Bewerken</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editUserForm">
                <input type="hidden" name="update_user" value="1">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="mb-3">
                        <label for="editName" class="form-label">Voornaam</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAchternaam" class="form-label">Achternaam</label>
                        <input type="text" class="form-control" id="editAchternaam" name="achternaam" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTelefoon" class="form-label">Telefoon</label>
                        <input type="text" class="form-control" id="editTelefoon" name="telefoon" required>
                    </div>
                    <div class="mb-3">
                        <label for="editRole" class="form-label">Rol</label>
                        <select class="form-select" id="editRole" name="role" required>
                            <option value="user">Gebruiker</option>
                            <option value="admin">Admin</option>
                            <option value="klant">Klant</option>
                        </select>
                    </div>
                    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sluiten</button>
        <button type="submit" class="btn btn-primary">Opslaan</button>
    </div>
                </form>
            </div>
        </div>
    </div>
</div>
                </section>

                <!-- Projects Section -->
                <section id="projects" class="d-none">
                    <h2>Projectbeheer</h2>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Project aanmaken</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                    <div class="mb-3">
        <input type="text" name="project_naam" class="form-control" placeholder="Projectnaam" required>
    </div>
    <div class="mb-3">
        <input type="number" step="0.01" name="contract_uren" class="form-control" placeholder="Contracturen" required>
    </div>
                                        <div class="mb-3">
                                            <select name="klant_id" class="form-select" required>
                                                <?php foreach ($klanten as $klant): ?>
                                                <option value="<?= $klant['klant_id'] ?>">
                                                    <?= htmlspecialchars($klant['bedrijfnaam']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <textarea name="beschrijving" class="form-control" 
                                                      placeholder="Projectbeschrijving" required></textarea>
                                        </div>
                                        
                                        <button type="submit" name="add_project" class="btn btn-success">
                                            <i class="bi bi-folder-plus"></i> Project aanmaken
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Gebruikers toevoegen aan project</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <select name="project_id" class="form-select" required>
                                                <option value="">Selecteer project</option>
                                                <?php foreach ($projects as $project): ?>
                                                <option value="<?= $project['project_id'] ?>">
                                                    <?= htmlspecialchars($project['project_naam']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <select name="user_id" class="form-select" required>
                                                <option value="">Selecteer gebruiker</option>
                                                <?php foreach ($users as $user): ?>
                                                <option value="<?= $user['user_id'] ?>">
                                                    <?= htmlspecialchars($user['name']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" name="assign_user" class="btn btn-info">
                                            <i class="bi bi-person-plus"></i> Gebruiker toevoegen
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                   <!-- Binnen de projects section -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Alle projecten</h5>
    </div>
    <div class="card-body">
        <table class="table table-striped">
        <thead>
        <tr>
            <th>Projectnaam</th>
            <th>Klant</th>
            <th>Contracturen</th>
            <th>Beschrijving</th>
            <th>Acties</th> <!-- Nieuwe kolom -->
        </tr>
    </thead>
    <tbody>
        <?php foreach ($projects as $project): 
            $klantInfo = current(array_filter($klanten, function($k) use ($project) {
                return $k['klant_id'] == $project['klant_id'];
            }));
        ?>
        <tr>
            <td><?= htmlspecialchars($project['project_naam']) ?></td>
            <td>
                <?= htmlspecialchars($klantInfo['bedrijfnaam'] ?? 'Onbekend') ?><br>
                <small class="text-muted">
                    <?= htmlspecialchars($klantInfo['voornaam'] ?? '') ?> 
                    <?= htmlspecialchars($klantInfo['achternaam'] ?? '') ?>
                </small>
            </td>
            <td>
                <?= number_format($project['contract_uren'] ?? 0, 2, ',', '.') ?> uur
            </td>
            <td><?= htmlspecialchars($project['beschrijving']) ?></td>
            <td>
                <!-- Bewerkknop -->
                <button type="button" class="btn btn-sm btn-primary edit-project-btn" 
                        data-bs-toggle="modal" data-bs-target="#editProjectModal"
                        data-projectid="<?= $project['project_id'] ?>"
                        data-projectnaam="<?= htmlspecialchars($project['project_naam']) ?>"
                        data-klantid="<?= $project['klant_id'] ?>"
                        data-contracturen="<?= $project['contract_uren'] ?>"
                        data-beschrijving="<?= htmlspecialchars($project['beschrijving']) ?>">
                    Bewerken
                </button>

                <!-- Verwijderknop -->
                <form method="POST" style="display: inline;" 
                      onsubmit="return confirm('Weet u zeker dat u dit project wilt verwijderen?');">
                    <input type="hidden" name="delete_project" value="<?= $project['project_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">
                        Verwijderen
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProjectModalLabel">Project Bewerken</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editProjectForm">
                    <input type="hidden" name="update_project" value="1">
                    <input type="hidden" name="project_id" id="editProjectId">
                    
                    <div class="mb-3">
                        <label for="editProjectNaam" class="form-label">Projectnaam</label>
                        <input type="text" class="form-control" id="editProjectNaam" name="project_naam" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editKlantId" class="form-label">Klant</label>
                        <select class="form-select" id="editKlantId" name="klant_id" required>
                            <?php foreach ($klanten as $klant): ?>
                            <option value="<?= $klant['klant_id'] ?>">
                                <?= htmlspecialchars($klant['bedrijfnaam']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editContracturen" class="form-label">Contracturen</label>
                        <input type="number" step="0.01" class="form-control" id="editContracturen" 
                               name="contract_uren" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editBeschrijving" class="form-label">Beschrijving</label>
                        <textarea class="form-control" id="editBeschrijving" name="beschrijving" required></textarea>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sluiten</button>
                        <button type="submit" class="btn btn-primary">Opslaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


                            
    <script>
        // Project bewerken: Vul de modal met data
document.querySelectorAll('.edit-project-btn').forEach(button => {
    button.addEventListener('click', function() {
        document.getElementById('editProjectId').value = this.dataset.projectid;
        document.getElementById('editProjectNaam').value = this.dataset.projectnaam;
        document.getElementById('editKlantId').value = this.dataset.klantid;
        document.getElementById('editContracturen').value = this.dataset.contracturen;
        document.getElementById('editBeschrijving').value = this.dataset.beschrijving;
    });
});

        
        // Role selection handler
        document.getElementById('roleSelect').addEventListener('change', function() {
            const bedrijfField = document.getElementById('bedrijfnaamField');
            bedrijfField.style.display = this.value === 'klant' ? 'block' : 'none';
            bedrijfField.querySelector('input').toggleAttribute('required', this.value === 'klant');
        });
// Tab navigation
document.querySelectorAll('.list-group-item').forEach(link => {
    link.addEventListener('click', function(e) {
        const target = this.getAttribute('href');

        // Controleer of de link NIET naar uitloggen.php of download.php verwijst
        if (target !== "uitloggen.php" && target !== "admin-download.php" && target !== "admin-profiel.php" && target !== 'klant.php') {
            e.preventDefault();

            document.querySelectorAll('.list-group-item').forEach(item => {
                item.classList.remove('active');
            });
            this.classList.add('active');

            document.querySelectorAll('section').forEach(section => {
                section.classList.remove('active-section');
            });

            // Zorg ervoor dat target geen null is voordat we proberen het te selecteren
            const targetSection = document.querySelector(target);
            if (targetSection) {
                targetSection.classList.add('active-section');
            }
        }
    });
});


        // Hours chart
        const ctx = document.getElementById('hoursChart').getContext('2d');
        const hoursChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($projectHours)) ?>,
                datasets: [{
                    label: 'Totaal gewerkte uren',
                    data: <?= json_encode(array_values($projectHours)) ?>,
                    backgroundColor: 'rgba(217, 83, 79, 0.5)',
                    borderColor: 'rgba(217, 83, 79, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
                // Edit modal handler
let editUserModalInstance;

document.querySelectorAll('.edit-btn').forEach(button => {
    button.addEventListener('click', function() {
        const modalEl = document.getElementById('editUserModal');
        document.getElementById('editUserId').value = this.dataset.id;
        document.getElementById('editName').value = this.dataset.name;
        document.getElementById('editAchternaam').value = this.dataset.achternaam;
        document.getElementById('editEmail').value = this.dataset.email;
        document.getElementById('editTelefoon').value = this.dataset.telefoon;
        document.getElementById('editRole').value = this.dataset.role;
        // Maak of hergebruik de modal-instantie en sla deze op
        editUserModalInstance = new bootstrap.Modal(modalEl);
        editUserModalInstance.show();
    });
});
function showNotification(message, type) {
    const notificationContainer = document.getElementById('notification-container');
    notificationContainer.textContent = message;
    notificationContainer.className = `notification ${type}`;
    notificationContainer.style.display = 'block';

    setTimeout(() => notificationContainer.classList.add('hide'), 3000);
    setTimeout(() => {
        notificationContainer.style.display = 'none';
        notificationContainer.classList.remove('hide');
    }, 3300);
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        showNotification('Gebruiker succesvol bijgewerkt!', 'success');
    }
});

document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Voorkom de standaard submit actie
    const formData = new FormData(this);

    fetch('admin-dashboard.php', {
         method: 'POST',
         body: formData,
         headers: {
             'X-Requested-With': 'XMLHttpRequest'
         }
    })
    .then(response => response.json())
    .then(data => {
         if (data.success) {
             showNotification(data.message, 'success');
             // Sluit de modal
             editUserModalInstance.hide();

             // (Optioneel) Werk de tabelrij bij met de nieuwe gegevens
             // Bijvoorbeeld: document.querySelector(`[data-id="${formData.get('user_id')}"]`).closest('tr').querySelector('td').textContent = formData.get('name');
         } else {
             showNotification(data.message, 'danger');
         }
    })
    .catch(error => {
         console.error('Error:', error);
         showNotification('Er is een fout opgetreden', 'danger');
    });
});

    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 