<?php
session_start();

// Databaseverbinding
try {
    require 'db/conn.php';
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Databaseverbinding mislukt: " . $e->getMessage());
    die(json_encode(['status' => 'error', 'message' => 'Databasefout']));
}

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header("Location: inloggen.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'klantDetails' && isset($_GET['klantId'])) {
    $klant_id = filter_input(INPUT_GET, 'klantId', FILTER_SANITIZE_NUMBER_INT);

    try {
        $klant_sql = "SELECT klant_id, voornaam, achternaam, email, telefoon, bedrijfnaam FROM klant WHERE klant_id = :klant_id";
        $klant_stmt = $pdo->prepare($klant_sql);
        $klant_stmt->execute(['klant_id' => $klant_id]);
        $klant = $klant_stmt->fetch(PDO::FETCH_ASSOC);

        if ($klant) {
            // Geef de klantgegevens terug als HTML
            echo '<div class="columns">';
            echo '<div class="column">';
            echo '<h3>Klantgegevens</h3>';
            echo '<div><h3>Voornaam</h3><p><img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16" data-field="klant_voornaam" data-value="' . htmlspecialchars($klant['voornaam']) . '" class="edit-button"><span id="klant_voornaam" data-id="' . $klant['klant_id'] . '">' . htmlspecialchars($klant['voornaam']) . '</span></p></div>';
            echo '<div><h3>Achternaam</h3><p><img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16" data-field="klant_achternaam" data-value="' . htmlspecialchars($klant['achternaam']) . '" class="edit-button"><span id="klant_achternaam" data-id="' . $klant['klant_id'] . '">' . htmlspecialchars($klant['achternaam']) . '</span></p></div>';
            echo '<div><h3>Email</h3><p><img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16" data-field="klant_email" data-value="' . htmlspecialchars($klant['email']) . '" class="edit-button"><span id="klant_email" data-id="' . $klant['klant_id'] . '">' . htmlspecialchars($klant['email']) . '</span></p></div>';
            echo '<div><h3>Telefoon</h3><p><img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16" data-field="klant_telefoon" data-value="' . htmlspecialchars($klant['telefoon']) . '" class="edit-button"><span id="klant_telefoon" data-id="' . $klant['klant_id'] . '">' . htmlspecialchars($klant['telefoon']) . '</span></p></div>';
            echo '<div><h3>Bedrijfsnaam</h3><p><img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16" data-field="klant_bedrijfnaam" data-value="' . htmlspecialchars($klant['bedrijfnaam']) . '" class="edit-button"><span id="klant_bedrijfnaam" data-id="' . $klant['klant_id'] . '">' . htmlspecialchars($klant['bedrijfnaam']) . '</span></p></div>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<p>Klant niet gevonden.</p>';
        }
    } catch (PDOException $e) {
        error_log("Klantdetails query mislukt: " . $e->getMessage());
        echo '<p>Er is een probleem bij het ophalen van de klantgegevens.</p>';
    }
    exit();
}

// Haal de rol van de gebruiker op
try {
    $role_sql = "SELECT role FROM users WHERE user_id = :user_id";
    $role_stmt = $pdo->prepare($role_sql);
    $role_stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user_role = $role_stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Rol query mislukt: " . $e->getMessage());
    die(json_encode(['status' => 'error', 'message' => 'Kon rol niet ophalen']));
}

// Verwerk formulierinzendingen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['field']) && isset($_POST['value']) && isset($_POST['id'])) {
    $field = filter_input(INPUT_POST, 'field', FILTER_SANITIZE_STRING);
    $value = filter_input(INPUT_POST, 'value', FILTER_SANITIZE_STRING);
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

    if (!$field || !$value || !$id) {
        die(json_encode(['status' => 'error', 'message' => 'Ongeldige invoer']));
    }

    $table = 'chiefs';
    $column = $field;
    $idColumn = 'chief_id';

    if (strpos($field, 'contact_') === 0) {
        $table = 'contact';
        $column = str_replace('contact_', '', $field);
        $idColumn = 'contact_id';
    } elseif (strpos($field, 'klant_') === 0) {
        $table = 'klant';
        $column = str_replace('klant_', '', $field);
        $idColumn = 'klant_id';
    }

    // Controleer de rol en veld voor autorisatie
    $allowed = false;
    if ($user_role === 'admin') {
        $allowed = true;
    } elseif ($user_role === 'klant' && strpos($field, 'klant_') === 0) {
        $allowed = true;
    }

    if (!$allowed) {
        die(json_encode(['status' => 'error', 'message' => 'Geen toestemming om dit veld te bewerken.']));
    }

    try {
        $sql = "UPDATE $table SET $column = :value WHERE $idColumn = :id";
        error_log("SQL Query: " . $sql);
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['value' => $value, 'id' => $id]);

        echo json_encode(['status' => 'success', 'message' => 'Veld bijgewerkt']);
    } catch (PDOException $e) {
        error_log("Update query mislukt: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Kon veld niet bijwerken: ' . $e->getMessage()]);
    }
    exit();

}

// Haal gegevens op uit de chiefs tabel
try {
    $sql = "SELECT chief_id, telefoon, adres, stad, postcode, provincie, land, Bedrijfnaam FROM chiefs";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $chiefs_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$chiefs_rows) {
        die("Geen gegevens gevonden voor chiefs");
    }
} catch (PDOException $e) {
    error_log("Chiefs query mislukt: " . $e->getMessage());
    die("Er is iets mis met de query voor chiefs: " . $e->getMessage());
}

// Haal projecten op voor de ingelogde gebruiker
try {
    $project_sql = "SELECT project_id, klant_id FROM project WHERE user_id = :user_id";
    $project_stmt = $pdo->prepare($project_sql);
    $project_stmt->execute(['user_id' => $_SESSION['user_id']]);
    $project_rows = $project_stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($user_role === 'admin') {
        // Admin: Haal alle klanten op
        $klant_sql = "SELECT klant_id, voornaam, achternaam, email, telefoon, bedrijfnaam FROM klant";
        $klant_stmt = $pdo->prepare($klant_sql);
        $klant_stmt->execute();
        $klant_rows = $klant_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        if (!$project_rows) {
            // Gebruiker heeft geen projecten, dus geen klanten
            $klant_rows = [];
        } else {
            // Haal klantgegevens op voor de projecten van de gebruiker
            $klant_ids = array_column($project_rows, 'klant_id'); // Haal alle klant_ids op
            $klant_ids_str = implode(',', $klant_ids); // Maak een string van klant_ids voor de query

            $klant_sql = "SELECT klant_id, voornaam, achternaam, email, telefoon, bedrijfnaam FROM klant WHERE klant_id IN ($klant_ids_str)";
            $klant_stmt = $pdo->prepare($klant_sql);
            $klant_stmt->execute();
            $klant_rows = $klant_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    error_log("Project/Klant query mislukt: " . $e->getMessage());
    die("Er is iets mis met de query voor projecten/klanten: " . $e->getMessage());
}

// Haal contactgegevens op
try {
    $contact_sql = "SELECT contact_id, voornaam, achternaam, email, telefoon FROM contact";
    $contact_stmt = $pdo->prepare($contact_sql);
    $contact_stmt->execute();
    $contact_rows = $contact_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$contact_rows) {
        die("Geen contactgegevens gevonden");
    }
} catch (PDOException $e) {
    error_log("Contact query mislukt: " . $e->getMessage());
    die("Er is iets mis met de query voor contact: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profiel Pagina</title>
    <link rel="stylesheet" href="css/profiel.css">
</head>
<body data-user-role="<?php echo htmlspecialchars($user_role); ?>">

<?php include 'sidebar.php'; ?>

<div class="container2">
    <div id="notification-container" class="notification" style="display: none;"></div>
</div>

<div class="container">
    <div class="buttons">
        <button data-target="bedrijfContainer" class="toggle-button">Bedrijf</button>
        <button data-target="klantContainer" class="toggle-button">Klant</button>
    </div>

    <div id="bedrijfContainer" class="container-section fade-in">
        <?php foreach ($chiefs_rows as $chief): ?>
            <div class="columns">
                <div class="column">
                    <h3>Bedrijf gegevens</h3>
                    <div class="profiel-titels">Bedrijfsnaam</div>
                    <p>
                        <span id="Bedrijfnaam" data-id="<?php echo $chief['chief_id']; ?>"><?php echo htmlspecialchars($chief['Bedrijfnaam']); ?></span>
                        <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                             data-field="Bedrijfnaam" data-value="<?php echo htmlspecialchars($chief['Bedrijfnaam']); ?>"
                             class="edit-button">
                    </p>
                    <div class="profiel-titels">Telefoon</div>
                    <p>
                        <span id="telefoon" data-id="<?php echo $chief['chief_id']; ?>"><?php echo htmlspecialchars($chief['telefoon']); ?></span>
                        <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                             data-field="telefoon"data-value="<?php echo htmlspecialchars($chief['telefoon']); ?>"
                             class="edit-button">
                    </p>
                    <div class="profiel-titels">Adres</div>
                    <p>
                        <span id="adres" data-id="<?php echo $chief['chief_id']; ?>"><?php echo htmlspecialchars($chief['adres']); ?></span>
                        <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                             data-field="adres" data-value="<?php echo htmlspecialchars($chief['adres']); ?>"
                             class="edit-button">
                    </p>
                    <div class="profiel-titels">Stad</div>
                    <p>
                        <span id="stad" data-id="<?php echo $chief['chief_id']; ?>"><?php echo htmlspecialchars($chief['stad']); ?></span>
                        <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                             data-field="stad" data-value="<?php echo htmlspecialchars($chief['stad']); ?>"
                             class="edit-button">
                    </p>

                    <div class="profiel-titels">Postcode</div>
                    <p>
                        <span id="postcode" data-id="<?php echo $chief['chief_id']; ?>"><?php echo htmlspecialchars($chief['postcode']); ?></span>
                        <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                             data-field="postcode" data-value="<?php echo htmlspecialchars($chief['postcode']); ?>"
                             class="edit-button">
                    </p>
                    <div class="profiel-titels">Provincie</div>
                    <p>
                        <span id="provincie" data-id="<?php echo $chief['chief_id']; ?>"><?php echo htmlspecialchars($chief['provincie']); ?></span>
                        <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                             data-field="provincie" data-value="<?php echo htmlspecialchars($chief['provincie']); ?>"
                             class="edit-button">
                    </p>
                    <div class="profiel-titels">Land</div>
                    <p>
                        <span id="land" data-id="<?php echo $chief['chief_id']; ?>"><?php echo htmlspecialchars($chief['land']); ?></span>
                        <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                             data-field="land" data-value="<?php echo htmlspecialchars($chief['land']); ?>"
                             class="edit-button">
                    </p>
                </div>

                <div class="column">
                    <h3>Contactpersoon</h3>
                    <?php
                    $contact_found = false;
                    foreach ($contact_rows as $contact) {
                        if ($contact['contact_id'] == $chief['chief_id']) {
                            $contact_found = true;
                            ?>
                            <div class="profiel-titels">Voornaam</div>
                            <p>
                                <span id="contact_voornaam" data-id="<?php echo $contact['contact_id']; ?>"><?php echo htmlspecialchars($contact['voornaam']); ?></span>
                                <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                                     data-field="contact_voornaam"
                                     data-value="<?php echo htmlspecialchars($contact['voornaam']); ?>"
                                     class="edit-button">
                            </p>
                            <div class="profiel-titels">Achternaam</div>
                            <p>
                                <span id="contact_achternaam" data-id="<?php echo $contact['contact_id']; ?>"><?php echo htmlspecialchars($contact['achternaam']); ?></span>
                                <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                                     data-field="contact_achternaam"
                                     data-value="<?php echo htmlspecialchars($contact['achternaam']); ?>"
                                     class="edit-button">
                            </p>
                            <div class="profiel-titels">Email</div>
                            <p>
                                <span id="contact_email" data-id="<?php echo $contact['contact_id']; ?>"><?php echo htmlspecialchars($contact['email']); ?></span>
                                <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                                     data-field="contact_email"
                                     data-value="<?php echo htmlspecialchars($contact['email']); ?>"
                                     class="edit-button">
                            </p>
                            <div class="profiel-titels">Telefoon</div>
                            <p>
                                <span id="contact_telefoon" data-id="<?php echo $contact['contact_id']; ?>"><?php echo htmlspecialchars($contact['telefoon']); ?></span>
                                <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                                     data-field="contact_telefoon"
                                     data-value="<?php echo htmlspecialchars($contact['telefoon']); ?>"
                                     class="edit-button">
                            </p>
                            <?php
                            break;
                        }
                    }
                    if (!$contact_found) {
                        echo "<p>Geen contactgegevens gevonden.</p>";
                    }
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="klantContainer" class="container-section" style="display: none;">
    <?php if ($user_role === 'admin'): ?>
        <div style="display: flex; flex-direction: row;">
            <div id="klantenLijst">
                <h6>Klantenlijst</h6>
                <ul>
                <?php foreach ($klant_rows as $klant): ?>
    <li>
        <a href="#" data-klant-id="<?php echo $klant['klant_id']; ?>" class="klant-link">
            <?php echo htmlspecialchars($klant['bedrijfnaam']); ?>
        </a>
    </li>
<?php endforeach; ?>
                </ul>
            </div>
            <div id="klantDetails" style="flex-grow: 1; background-color: transparant;">
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($klant_rows as $klant): ?>
            <div class="columns">
                <div class="column">
                    <h3>Klantgegevens</h3>
                    <div>
                        <h3>Voornaam</h3>
                        <p>
                            <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                                data-field="klant_voornaam" data-value="<?php echo htmlspecialchars($klant['voornaam']); ?>"
                                class="edit-button">
                            <span id="klant_voornaam" data-id="<?php echo $klant['klant_id']; ?>"><?php echo htmlspecialchars($klant['voornaam']); ?></span>
                        </p>
                    </div>
                    <div>
                        <h3>Achternaam</h3>
                        <p>
                            <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                                data-field="klant_achternaam" data-value="<?php echo htmlspecialchars($klant['achternaam']); ?>"
                                class="edit-button">
                            <span id="klant_achternaam" data-id="<?php echo $klant['klant_id']; ?>"><?php echo htmlspecialchars($klant['achternaam']); ?></span>
                        </p>
                    </div>
                    <div>
                        <h3>Email</h3>
                        <p>
                            <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                                data-field="klant_email" data-value="<?php echo htmlspecialchars($klant['email']); ?>"
                                class="edit-button">
                            <span id="klant_email" data-id="<?php echo $klant['klant_id']; ?>"><?php echo htmlspecialchars($klant['email']); ?></span>
                        </p>
                    </div>
                    <div>
                        <h3>Telefoon</h3>
                        <p>
                            <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                                data-field="klant_telefoon" data-value="<?php echo htmlspecialchars($klant['telefoon']); ?>"
                                class="edit-button">
                            <span id="klant_telefoon" data-id="<?php echo $klant['klant_id']; ?>"><?php echo htmlspecialchars($klant['telefoon']); ?></span>
                        </p>
                    </div>
                    <div>
                        <h3>Bedrijfsnaam</h3>
                        <p>
                            <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                                data-field="klant_bedrijfnaam" data-value="<?php echo htmlspecialchars($klant['bedrijfnaam']); ?>"
                                class="edit-button">
                            <span id="klant_bedrijfnaam" data-id="<?php echo $klant['klant_id']; ?>"><?php echo htmlspecialchars($klant['bedrijfnaam']); ?></span>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>

<script src="js/profiel.js"></script>
</body>
</html>