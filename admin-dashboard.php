<?php
require __DIR__ . '/db/conn.php';

// Helperfunctie om alleen actieve projecten op te halen
function getActiveProjects($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM project WHERE status = 'actief'");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Controleer of het een AJAX-request is voor e-mailvalidatie
if (isset($_GET['action']) && $_GET['action'] === 'check_email' && isset($_GET['email'])) {
    $email = $_GET['email'];
    // Controleer in de tabel users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $countUser = $stmt->fetchColumn();
    // Controleer in de tabel klant (voor klanten)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM klant WHERE email = ?");
    $stmt->execute([$email]);
    $countKlant = $stmt->fetchColumn();
    $exists = ($countUser + $countKlant) > 0;
    
    header('Content-Type: application/json');
    echo json_encode(['exists' => $exists]);
    exit();
}

session_start();

// Controleer adminrechten
if (!isset($_SESSION['role'])) {
    header("Location: inloggen.php");
    exit();
} elseif ($_SESSION['role'] !== 'admin') {
    die("Geen toegang!");
}

// Verwerk verwijderingsactie voor gebruiker
if (isset($_POST['delete_user'])) {
    $userId = $_POST['delete_user'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        header("Location: admin-dashboard.php?success_user_deleted=1#users");
        exit();
    } catch (PDOException $e) {
        die("Fout bij verwijderen: " . $e->getMessage());
    }
}

// Haal initiële gegevens op
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
    $klant_id     = $_POST['klant_id'];
    $beschrijving = $_POST['beschrijving'];
    $contract_uren= $_POST['contract_uren'];

    try {
        $stmt = $pdo->prepare("INSERT INTO project (project_naam, klant_id, beschrijving, contract_uren, status) VALUES (?, ?, ?, ?, 'actief')");
        $stmt->execute([$project_naam, $klant_id, $beschrijving, $contract_uren]);
        header("Location: admin-dashboard.php?success_project_added=1#projects");
        exit();
    } catch (PDOException $e) {
        die("Fout bij toevoegen project: " . $e->getMessage());
    }
}

// Verwerk overige POST-acties
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verwerk gebruikersupdate
    if (isset($_POST['update_user'])) {
        $userId = $_POST['user_id'];
        $name = $_POST['name'];
        $achternaam = $_POST['achternaam'];
        $email = $_POST['email'];
        $telefoon = $_POST['telefoon'];
        $role = $_POST['role'];

        // Voorkom dat een gebruiker de rol 'klant' krijgt
        if ($role === 'klant') {
            die("Fout: Een gebruiker mag niet als klant worden bewerkt.");
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, achternaam = ?, email = ?, telefoon = ?, role = ? WHERE user_id = ?");
            $stmt->execute([$name, $achternaam, $email, $telefoon, $role, $userId]);
            header("Location: admin-dashboard.php?success_user_updated=1#users");
            exit();
        } catch (PDOException $e) {
            die("Fout bij bijwerken: " . $e->getMessage());
        }
    }

    // Verwerk gebruikersformulier (toevoegen gebruiker)
    if (isset($_POST['add_user'])) {
        $role = $_POST['role'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            if ($role === 'klant') {
                if (empty($_POST['bedrijfnaam'])) {
                    throw new Exception("Bedrijfsnaam is verplicht voor klanten!");
                }
                
                $stmt = $pdo->prepare("INSERT INTO klant (voornaam, achternaam, email, telefoon, password, role, bedrijfnaam) VALUES (?, ?, ?, ?, ?, ?, ?)");
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
                $stmt = $pdo->prepare("INSERT INTO users (name, achternaam, email, telefoon, password, role) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['achternaam'],
                    $_POST['email'],
                    $_POST['telefoon'],
                    $password,
                    $role
                ]);
            }
            header("Location: admin-dashboard.php?success_user_added=1#users");
            exit();
        } catch (Exception $e) {
            die("Fout: " . $e->getMessage());
        }
    }

    // Verwerk gebruiker toevoegen aan project
    if (isset($_POST['assign_user'])) {
        try {
            $project_id = $_POST['project_id'];
            $user_id = $_POST['user_id'];

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
                    header("Location: admin-dashboard.php?success_project_user_added=1#projects");
                    exit();
                }
            } else {
                header("Location: admin-dashboard.php?error=project_of_gebruiker_bestaat_niet#projects");
                exit();
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                header("Location: admin-dashboard.php?error=gebruiker_al_gekoppeld#projects");
                exit();
            } else {
                header("Location: admin-dashboard.php?error=" . urlencode("Fout bij koppelen: " . $e->getMessage()) . "#projects");
                exit();
            }
        }
    }
    
    // Verwerk projectverwijdering
    if (isset($_POST['delete_project'])) {
        $projectId = $_POST['delete_project'];
        try {
            $stmt = $pdo->prepare("DELETE FROM project_users WHERE project_id = ?");
            $stmt->execute([$projectId]);
            $stmt = $pdo->prepare("DELETE FROM project WHERE project_id = ?");
            $stmt->execute([$projectId]);
            header("Location: admin-dashboard.php?success_project_deleted=1#projects");
            exit();
        } catch (PDOException $e) {
            die("Fout bij verwijderen project: " . $e->getMessage());
        }
    }

    // Verwerk het verwijderen van een gebruiker uit een project
    if (isset($_POST['remove_project_user'])) {
        $project_id = $_POST['project_id'];
        $user_id = $_POST['user_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM project_users WHERE project_id = ? AND user_id = ?");
            $stmt->execute([$project_id, $user_id]);
            header("Location: admin-dashboard.php?success_project_user_removed=1#projects");
            exit();
        } catch (PDOException $e) {
            die("Fout bij verwijderen van projectgebruiker: " . $e->getMessage());
        }
    }

    // Verwerk project-update
    if (isset($_POST['update_project'])) {
        $project_id = $_POST['project_id'];
        $project_naam = $_POST['project_naam'];
        $klant_id = $_POST['klant_id'];
        $contract_uren = $_POST['contract_uren'];
        $beschrijving = $_POST['beschrijving'];

        try {
            $stmt = $pdo->prepare("UPDATE project SET project_naam = ?, klant_id = ?, contract_uren = ?, beschrijving = ? WHERE project_id = ?");
            $stmt->execute([$project_naam, $klant_id, $contract_uren, $beschrijving, $project_id]);
            header("Location: admin-dashboard.php?success_project_updated=1#projects");
            exit();
        } catch (PDOException $e) {
            die("Fout bij projectupdate: " . $e->getMessage());
        }
    }

    // Verwerk status update (toggle)
    if (isset($_POST['update_status'])) {
        $project_id = $_POST['project_id'];
        try {
            $stmt = $pdo->prepare("SELECT status FROM project WHERE project_id = ?");
            $stmt->execute([$project_id]);
            $currentStatus = $stmt->fetchColumn();
            $newStatus = ($currentStatus === 'actief') ? 'niet actief' : 'actief';
            $stmt = $pdo->prepare("UPDATE project SET status = ? WHERE project_id = ?");
            $stmt->execute([$newStatus, $project_id]);
            header("Location: admin-dashboard.php?success_status_updated=1#projects");
            exit();
        } catch (PDOException $e) {
            die("Fout bij status update: " . $e->getMessage());
        }
    }
    
    // Verwerk status update (dropdown)
    if (isset($_POST['set_status'])) {
        $project_id = $_POST['project_id'];
        $newStatus = $_POST['set_status'];
        try {
            $stmt = $pdo->prepare("UPDATE project SET status = ? WHERE project_id = ?");
            $stmt->execute([$newStatus, $project_id]);
            header("Location: admin-dashboard.php?success_status_updated=1#projects");
            exit();
        } catch (PDOException $e) {
            die("Fout bij status update: " . $e->getMessage());
        }
    }
}

// Herhaal enkele queries voor de weergave
try {
    $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $klantenCount = $pdo->query("SELECT COUNT(*) FROM klant")->fetchColumn();
    $pendingHoursCount = $pdo->query("SELECT COUNT(*) FROM hours WHERE accord = 'Pending'")->fetchColumn();
    $projects = $pdo->query("SELECT * FROM project")->fetchAll(PDO::FETCH_ASSOC);
    $activeProjects = getActiveProjects($pdo);
    $hours = $pdo->query("SELECT * FROM hours")->fetchAll(PDO::FETCH_ASSOC);
    $klanten = $pdo->query("SELECT * FROM klant")->fetchAll(PDO::FETCH_ASSOC);
    $users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database fout: " . $e->getMessage());
}

// Bereken de uren per actief project
$projectHours = [];
foreach ($activeProjects as $project) {
    $projectId = $project['project_id'];
    $projectHours[$project['project_naam']] = 0;
    foreach ($hours as $hour) {
        if ($hour['project_id'] == $projectId && isset($hour['hours'])) {
            $projectHours[$project['project_naam']] += $hour['hours'];
        }
    }
}

// Haal de koppelingen tussen projecten en gebruikers op
$projectUsers = $pdo->query("SELECT * FROM project_users")->fetchAll(PDO::FETCH_ASSOC);
$userMap = [];
foreach ($users as $user) {
    $userMap[$user['user_id']] = $user;
}
$projectAssignments = [];
foreach ($projectUsers as $pu) {
    $projectAssignments[$pu['project_id']][] = $pu['user_id'];
}
foreach ($hours as $h) {
    $hoursByUserProject[$h['project_id']][$h['user_id']] = 
         ($hoursByUserProject[$h['project_id']][$h['user_id']] ?? 0) + $h['hours'];
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
                    <a href="#projects" class="list-group-item" id="projectsLink">Projecten</a>
                    <button id="showOverlayButton" class="btn btn-secondary mt-2" style="display:none;">gebruiker link</button>
                    <a href="admin-klant.php" class="list-group-item">Klanten</a>
                    <a href="admin-download.php" class="list-group-item">Download</a>
                    <a href="admin-profiel.php" class="list-group-item">Profiel</a>
                    <a href="uitloggen.php" class="list-group-item list-group-item-danger">Uitloggen</a>
                </div>
            </div>
        </nav>

        <main class="col-md-10 p-4">
            <!-- Dashboard Section -->
            <section id="dashboard" class="active-section d-none">                    
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Gebruikers</h5>
                                <p class="card-text display-4"><?= $usersCount ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">Klanten</h5>
                                <p class="card-text display-4"><?= $klantenCount ?></p>
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
                        <div class="col-md-3 position-relative">
                            <div id="emailFeedback" class="alert alert-danger d-none py-1 px-2 position-absolute" style="top: -35px; left: 5px; margin-bottom: 0; z-index: 999;">
                                Deze email is al in gebruik.
                            </div>
                            <input type="email" id="email" name="email" class="form-control" placeholder="Email" required>
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
                            </select>
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
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="delete_user" value="<?= $user['user_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?')">
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
                            <!-- Let op: We hebben de submit-handler verwijderd zodat het formulier nu gewoon wordt gesubmit -->
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
                                        <textarea name="beschrijving" class="form-control" placeholder="Projectbeschrijving" required></textarea>
                                    </div>
                                    <button type="submit" name="add_project" class="btn btn-success">
                                        <i class="bi bi-folder-plus"></i> Project aanmaken
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Gebruiker koppelen aan project -->
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
                                            <?php foreach ($activeProjects as $project): ?>
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

                <!-- Alle projecten -->
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
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): 
                                    $klantInfo = current(array_filter($klanten, function($k) use ($project) {
                                        return $k['klant_id'] == $project['klant_id'];
                                    }));
                                    $statusClass = ($project['status'] === 'actief') ? 'status-active' : 'status-inactive';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($project['project_naam']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($klantInfo['bedrijfnaam'] ?? 'Onbekend') ?><br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($klantInfo['voornaam'] ?? '') ?> <?= htmlspecialchars($klantInfo['achternaam'] ?? '') ?>
                                        </small>
                                    </td>
                                    <td><?= number_format($project['contract_uren'], 2, ',', '.') ?> uur</td>
                                    <td><?= htmlspecialchars($project['beschrijving']) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-project-btn" 
                                                data-bs-toggle="modal" data-bs-target="#editProjectModal"
                                                data-projectid="<?= $project['project_id'] ?>"
                                                data-projectnaam="<?= htmlspecialchars($project['project_naam']) ?>"
                                                data-klantid="<?= $project['klant_id'] ?>"
                                                data-contracturen="<?= $project['contract_uren'] ?>"
                                                data-beschrijving="<?= htmlspecialchars($project['beschrijving']) ?>">
                                            Bewerken
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-secondary dropdown-toggle status-dropdown-btn <?= $statusClass ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <?= htmlspecialchars($project['status']) ?>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <button class="dropdown-item" type="submit" name="set_status" value="actief">Actief</button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item" type="submit" name="set_status" value="niet actief">Niet Actief</button>
                                                    </li>
                                                </ul>
                                                <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                                            </div>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Weet u zeker dat u dit project wilt verwijderen?');">
                                            <input type="hidden" name="delete_project" value="<?= $project['project_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Verwijderen</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Overlay container -->
            <div id="overlay" class="overlay">
                <div class="overlay-content card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Gekoppelde gebruikers en gewerkte uren per project</h5>
                        <button id="closeOverlay" class="btn btn-danger2">X</button>
                    </div>
                    <div class="card-body" style="max-height: 80vh; overflow-y: auto;">
                        <?php foreach ($projects as $project): ?>
                        <div class="card mt-3 mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><?= htmlspecialchars($project['project_naam']) ?></h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped table-hover mb-0 align-middle" style="table-layout: fixed; width: 100%;">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th style="width: 50%">Naam</th>
                                            <th class="text-center" style="width: 15%">Gewerkte uren</th>
                                            <th class="text-end" style="width: 35%">Actie</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            $hasUsers = isset($projectAssignments[$project['project_id']]) && !empty($projectAssignments[$project['project_id']]);
                                        ?>
                                        <?php if ($hasUsers): ?>
                                            <?php foreach ($projectAssignments[$project['project_id']] as $user_id): 
                                                $user   = $userMap[$user_id];
                                                $worked = $hoursByUserProject[$project['project_id']][$user_id] ?? 0;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['name'] . ' ' . $user['achternaam']) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($worked) ?></td>
                                                <td class="text-end">
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                                                        <input type="hidden" name="user_id" value="<?= $user_id ?>">
                                                        <button type="submit" name="remove_project_user" class="btn btn-sm btn-outline-danger" onclick="return confirm('Weet je zeker dat je deze gebruiker van dit project wilt verwijderen?');">
                                                            Verwijderen
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">Geen gebruikers gekoppeld.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Edit Project Modal -->
            <div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editProjectModalLabel">Project Bewerken</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Ook hier verwijderen we de AJAX-submit zodat het formulier normaal submit -->
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
                                    <input type="number" step="0.01" class="form-control" id="editContracturen" name="contract_uren" required>
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
        </main>
    </div>
</div>

<script>
// Toon notificaties op basis van URL-parameters (notificatie wordt pas na refresh getoond)
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('success_user_deleted')) {
        showNotification('Gebruiker succesvol verwijderd!', 'success');
    }
    if(urlParams.has('success_project_added')) {
        showNotification('Project succesvol toegevoegd!', 'success');
    }
    if(urlParams.has('success_user_updated')) {
        showNotification('Gebruiker succesvol bijgewerkt!', 'success');
    }
    if(urlParams.has('success_user_added')) {
        showNotification('Gebruiker succesvol toegevoegd!', 'success');
    }
    if(urlParams.has('success_project_deleted')) {
        showNotification('Project succesvol verwijderd!', 'success');
    }
    if(urlParams.has('success_project_user_removed')) {
        showNotification('Gebruiker succesvol verwijderd uit project!', 'success');
    }
    if(urlParams.has('success_project_updated')) {
        showNotification('Project succesvol bijgewerkt!', 'success');
    }
    if(urlParams.has('success_status_updated')) {
        showNotification('Status succesvol bijgewerkt!', 'success');
    }
});

// Chart.js grafiek
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
        responsive: false,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Vul de Edit Project Modal met data
document.querySelectorAll('.edit-project-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault(); // voorkom standaard modal-actie
        const modalEl = document.getElementById('editProjectModal');
        document.getElementById('editProjectId').value = this.dataset.projectid;
        document.getElementById('editProjectNaam').value = this.dataset.projectnaam;
        document.getElementById('editKlantId').value = this.dataset.klantid;
        document.getElementById('editContracturen').value = this.dataset.contracturen;
        document.getElementById('editBeschrijving').value = this.dataset.beschrijving;
        addCustomBackdrop();
        let editProjectModalInstance = new bootstrap.Modal(modalEl);
        editProjectModalInstance.show();
        modalEl.addEventListener('hidden.bs.modal', removeCustomBackdrop, { once: true });
    });
});

// Role selection handler (indien er een bedrijfnaam-veld bestaat)
document.getElementById('roleSelect').addEventListener('change', function() {
    const bedrijfField = document.getElementById('bedrijfnaamField');
    if(bedrijfField){
        bedrijfField.style.display = this.value === 'klant' ? 'block' : 'none';
        bedrijfField.querySelector('input').toggleAttribute('required', this.value === 'klant');
    }
});

// Tab navigatie
document.querySelectorAll('.list-group-item').forEach(link => {
    link.addEventListener('click', function(e) {
        const target = this.getAttribute('href');
        if (target !== "uitloggen.php" && target !== "admin-download.php" && target !== "admin-profiel.php" && target !== 'admin-klant.php') {
            e.preventDefault();
            document.querySelectorAll('.list-group-item').forEach(item => item.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('section').forEach(section => section.classList.remove('active-section'));
            const targetSection = document.querySelector(target);
            if (targetSection) { targetSection.classList.add('active-section'); }
        }
    });
});

// Edit User Modal handler met custom backdrop
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
        addCustomBackdrop();
        editUserModalInstance = new bootstrap.Modal(modalEl);
        editUserModalInstance.show();
        modalEl.addEventListener('hidden.bs.modal', removeCustomBackdrop, { once: true });
    });
});

// Functie om notificaties te tonen
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

// E-mail validatie
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const emailFeedback = document.getElementById('emailFeedback');
    emailInput.addEventListener('input', function() {
        const email = emailInput.value;
        if (email.length > 5) {
            fetch('admin-dashboard.php?action=check_email&email=' + encodeURIComponent(email))
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    emailInput.classList.add('is-invalid');
                    emailFeedback.classList.remove('d-none');
                } else {
                    emailInput.classList.remove('is-invalid');
                    emailFeedback.classList.add('d-none');
                }
            })
            .catch(error => console.error('Error:', error));
        } else {
            emailInput.classList.remove('is-invalid');
            emailFeedback.classList.add('d-none');
        }
    });
});

// Functie om een custom backdrop toe te voegen
function addCustomBackdrop() {
    const backdrop = document.createElement('div');
    backdrop.id = 'customBackdrop';
    backdrop.style.position = 'fixed';
    backdrop.style.top = 0;
    backdrop.style.left = 0;
    backdrop.style.width = '100%';
    backdrop.style.height = '100%';
    backdrop.style.background = 'rgba(0, 0, 0, 0.7)';
    backdrop.style.zIndex = '1040';
    document.body.appendChild(backdrop);
}

// Functie om de custom backdrop te verwijderen
function removeCustomBackdrop() {
    const backdrop = document.getElementById('customBackdrop');
    if (backdrop) { backdrop.remove(); }
}

// Sidebar overlay knop
function updateSidebarButton() {
    const projectsLink = document.getElementById('projectsLink');
    const overlayBtn = document.getElementById('showOverlayButton');
    if (projectsLink && projectsLink.classList.contains('active')) {
        if (overlayBtn.style.display !== 'block') {
            overlayBtn.style.display = 'block';
            overlayBtn.classList.add('fade-in');
            setTimeout(() => { overlayBtn.classList.remove('fade-in'); }, 500);
        }
    } else {
        overlayBtn.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', updateSidebarButton);
window.addEventListener('hashchange', updateSidebarButton);
document.querySelectorAll('.list-group-item').forEach(item => {
    item.addEventListener('click', function() { setTimeout(updateSidebarButton, 100); });
});
document.getElementById('showOverlayButton').addEventListener('click', function(){
    document.getElementById('overlay').style.display = 'flex';
});
document.getElementById('closeOverlay').addEventListener('click', function(){
    document.getElementById('overlay').style.display = 'none';
});
document.getElementById('overlay').addEventListener('click', function(e) {
    if (e.target === this) { this.style.display = 'none'; }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
