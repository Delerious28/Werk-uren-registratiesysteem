<?php
session_start();
include "db/conn.php";

// Controleer of er een inlog-ID aanwezig is en of de rol geldig is
if ((!isset($_SESSION['user_id']) && !isset($_SESSION['klant_id'])) || !in_array($_SESSION['role'], ['user', 'klant'])) {
    header("Location: inloggen.php");
    exit();
}

if ($_SESSION['role'] === 'klant') {
    // Voor klantaccounts: Haal klantgegevens op uit de tabel klant
    $klant_id = $_SESSION['klant_id'];
    $queryClient = "SELECT klant_id, voornaam, achternaam, email, telefoon, bedrijfnaam 
                    FROM klant 
                    WHERE klant_id = :klant_id";
    $stmtClient = $pdo->prepare($queryClient);
    $stmtClient->bindParam(':klant_id', $klant_id, PDO::PARAM_INT);
    $stmtClient->execute();
    $clientData = $stmtClient->fetch(PDO::FETCH_ASSOC);

    // Haal de admingegevens op, zodat deze voor elke klant zichtbaar zijn
    $queryAdmin = "SELECT user_id, name, achternaam, email, telefoon 
                   FROM users 
                   WHERE role = 'admin' 
                   LIMIT 1";
    $stmtAdmin = $pdo->query($queryAdmin);
    $adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
} else {
    // Voor gebruikers (admin of gewone user): Haal eigen gegevens op uit de tabel users
    $user_id = $_SESSION['user_id'];
    $queryUser = "SELECT user_id, name, achternaam, email, telefoon, role 
                  FROM users 
                  WHERE user_id = :user_id";
    $stmtUser = $pdo->prepare($queryUser);
    $stmtUser->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmtUser->execute();
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Haal de klantgegevens op via de koppeling van project_users en project
    $queryClient = "SELECT k.klant_id, k.voornaam, k.achternaam, k.email, k.telefoon, k.bedrijfnaam, p.project_naam AS projectnaam
                    FROM project_users pu
                    JOIN project p ON pu.project_id = p.project_id
                    JOIN klant k ON p.klant_id = k.klant_id
                    WHERE pu.user_id = :user_id";
    $stmtClient = $pdo->prepare($queryClient);
    $stmtClient->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmtClient->execute();
    $clientData = $stmtClient->fetch(PDO::FETCH_ASSOC);
}

// Haal de bedrijfsgegevens op (tabel chiefs)
$queryChiefs = "SELECT * FROM chiefs LIMIT 1";
$stmtChiefs = $pdo->query($queryChiefs);
$chiefsData = $stmtChiefs->fetch(PDO::FETCH_ASSOC);

// Haal de laatst ingevoerde contactgegevens op
$queryContact = "SELECT * FROM contact ORDER BY created_at DESC LIMIT 1";
$stmtContact = $pdo->query($queryContact);
$contactData = $stmtContact->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informatie</title>
    <link rel="stylesheet" href="css/profiel.css">
</head>
<body>
<header>
    <h1>Bedrijfsinformatie Portal</h1>
</header>
<?php include 'sidebar.php'; ?>

<div class="profiel-container">
    <!-- Bedrijfsinformatie blijft altijd getoond -->
    <div class="info-section bedrijfs-container">
        <div class="toggle-contact">
            <span class="contact-text">Contact</span>
            <img src="img/info-icon.png" alt="Toon contactinformatie" class="toggle-contact-icon">
        </div>
        <h2>Bedrijfsinformatie</h2>
        <?php if ($chiefsData): ?>
            <div class="info-item"><span class="info-label">Bedrijfsnaam:</span> <?php echo htmlspecialchars($chiefsData['bedrijfnaam']); ?></div>
            <div class="info-item"><span class="info-label">Adres:</span> <?php echo htmlspecialchars($chiefsData['adres']); ?></div>
            <div class="info-item"><span class="info-label">Telefoon:</span> <?php echo htmlspecialchars($chiefsData['telefoon']); ?></div>
            <div class="info-item"><span class="info-label">Stad:</span> <?php echo htmlspecialchars($chiefsData['stad']); ?></div>
            <div class="info-item"><span class="info-label">Postcode:</span> <?php echo htmlspecialchars($chiefsData['postcode']); ?></div>
            <div class="info-item"><span class="info-label">Provincie:</span> <?php echo htmlspecialchars($chiefsData['provincie']); ?></div>
            <div class="info-item"><span class="info-label">Land:</span> <?php echo htmlspecialchars($chiefsData['land']); ?></div>
        <?php else: ?>
            <p>Geen bedrijfsinformatie beschikbaar.</p>
        <?php endif; ?>
    </div>

    <?php if ($_SESSION['role'] === 'klant'): ?>
    <div class="info-section">
        <h2>Mijn Gegevens</h2>
        <?php if ($clientData): ?>
            <div class="info-item"><span class="info-label">Bedrijfsnaam:</span> <?php echo htmlspecialchars($clientData['bedrijfnaam']); ?></div>
            <div class="info-item"><span class="info-label">Contactpersoon:</span> <?php echo htmlspecialchars($clientData['voornaam'] . " " . $clientData['achternaam']); ?></div>
            <div class="info-item"><span class="info-label">Email:</span> <?php echo htmlspecialchars($clientData['email']); ?></div>
            <div class="info-item"><span class="info-label">Telefoon:</span> <?php echo htmlspecialchars($clientData['telefoon']); ?></div>
        <?php else: ?>
            <p>Geen klantgegevens beschikbaar.</p>
        <?php endif; ?>
    </div>

    <!-- Admingegevens voor elke klant -->
    <div class="info-section">
        <h2>Admin</h2>
        <?php if ($adminData): ?>
            <div class="info-item"><span class="info-label">Naam:</span> <?php echo htmlspecialchars($adminData['name'] . " " . $adminData['achternaam']); ?></div>
            <div class="info-item"><span class="info-label">Email:</span> <?php echo htmlspecialchars($adminData['email']); ?></div>
            <div class="info-item"><span class="info-label">Telefoon:</span> <?php echo htmlspecialchars($adminData['telefoon']); ?></div>
        <?php else: ?>
            <p>Geen admin gegevens beschikbaar.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- Voor admin of gebruikers -->
    <div class="info-section">
        <h2>Mijn Gegevens</h2>
        <?php if ($userData): ?>
            <div class="info-item"><span class="info-label">Naam:</span> <?php echo htmlspecialchars($userData['name'] . " " . $userData['achternaam']); ?></div>
            <div class="info-item"><span class="info-label">Email:</span> <?php echo htmlspecialchars($userData['email']); ?></div>
            <div class="info-item"><span class="info-label">Telefoon:</span> <?php echo htmlspecialchars($userData['telefoon']); ?></div>
        <?php else: ?>
            <p>Geen gegevens beschikbaar.</p>
        <?php endif; ?>
    </div>

    <div class="info-section">
        <h2>Klantinformatie</h2>
        <button class="toggle-klanten-info">
            <img src="img/info-icon.png" alt="Toon klantinformatie" class="toggle-contact-icon">
        </button>
        <?php if ($clientData): ?>
            <div class="info-item"><span class="info-label">Bedrijfsnaam:</span> <?php echo htmlspecialchars($clientData['bedrijfnaam']); ?></div>
            <div class="info-item"><span class="info-label">Contactpersoon:</span> <?php echo htmlspecialchars($clientData['voornaam'] . " " . $clientData['achternaam']); ?></div>
            <div class="info-item"><span class="info-label">Email:</span> <?php echo htmlspecialchars($clientData['email']); ?></div>
            <div class="info-item"><span class="info-label">Telefoon:</span> <?php echo htmlspecialchars($clientData['telefoon']); ?></div>
            <div class="info-item"><span class="info-label">Project:</span> <?php echo htmlspecialchars($clientData['projectnaam']); ?></div>
        <?php else: ?>
            <p>Geen klant gekoppeld.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

</div>

<!-- Contactinformatie Popup -->
<div class="contact-popup" id="contact-popup">
    <div class="contact-popup-content">
        <div class="popup-header">
            <h3>Contact informatie</h3>
            <span class="close-popup" id="close-contact">&times;</span>
        </div>
        <?php if ($contactData): ?>
            <div class="popup-gegevens">
                <div class="verticaal-lijn"> | </div>
                <div class="info-item-naam"><span class="info-label">Naam:</span> <?php echo htmlspecialchars($contactData['voornaam'] . " " . $contactData['achternaam']); ?></div>
                <div class="info-item-email"><span class="info-label">Email:</span> <?php echo htmlspecialchars($contactData['email']); ?></div>
                <div class="info-item-telefoon"><span class="info-label">Telefoon:</span> <?php echo htmlspecialchars($contactData['telefoon']); ?></div>
                <div class="info-item-bericht"><span class="info-label">Bericht:</span> <?php echo htmlspecialchars($contactData['bericht']); ?></div>
                <div class="info-item-verstuurd"><span class="info-label">Verstuurd op:</span> <?php echo htmlspecialchars($contactData['created_at']); ?></div>
            </div>
        <?php else: ?>
            <p>Geen contactinformatie beschikbaar.</p>
        <?php endif; ?>
    </div>
</div>
<?php if ($_SESSION['role'] !== 'klant'): ?>
    <!-- Klantinformatie Popup -->
    <div class="Klanten-popup" id="klanten-popup">
        <div class="klanten-popup-content">
            <div class="popup-header">
                <h3>Gekoppelde klanten informatie</h3>
                <span class="close-popup" id="close-klant">&times;</span>
            </div>
            <div class="klanten-popup-gegevens">
                <?php
                // Haal alle klantgegevens op inclusief projectnaam
                $stmtClient->execute();
                $clients = $stmtClient->fetchAll(PDO::FETCH_ASSOC);
                if ($clients):
                    foreach ($clients as $client): ?>
                    <div class="klant-gegevens-ctn">
                        <div class="verticaal-lijn-ctn">
                            <div class="klanten-verticaal-lijn"> | </div>
                        </div>
                        <div class="klant-gegevens">
                            <div class="info-item-projectnaam">
                                <span class="info-label">Project:</span> <?php echo htmlspecialchars($client['projectnaam']); ?>
                            </div>
                            <div class="info-item-bedrijfsnaam">
                                <span class="info-label">Bedrijfsnaam:</span> <?php echo htmlspecialchars($client['bedrijfnaam']); ?>
                            </div>
                            <div class="info-item-contactpersoon">
                                <span class="info-label">Contactpersoon:</span> <?php echo htmlspecialchars($client['voornaam'] . " " . $client['achternaam']); ?>
                            </div>
                            <div class="info-item-email">
                                <span class="info-label">Email:</span> <?php echo htmlspecialchars($client['email']); ?>
                            </div>
                            <div class="info-item-k-telefoon">
                                <span class="info-label">Telefoon:</span> <?php echo htmlspecialchars($client['telefoon']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach;
                else: ?>
                    <p>Geen klanten gekoppeld.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="js/profiel.js"></script>
</body>
</html>
