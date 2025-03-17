<?php
session_start();
include "db/conn.php";

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['user', 'klant'])) {
    header("Location: inloggen.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Haal de eigen gebruikersgegevens op
$queryUser = "SELECT user_id, name, achternaam, email, telefoon, role 
              FROM users 
              WHERE user_id = :user_id";
$stmtUser = $pdo->prepare($queryUser);
$stmtUser->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmtUser->execute();
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Haal de bedrijfsgegevens op (tabel chiefs)
$queryChiefs = "SELECT * FROM chiefs LIMIT 1";
$stmtChiefs = $pdo->query($queryChiefs);
$chiefsData = $stmtChiefs->fetch(PDO::FETCH_ASSOC);

// Haal de klantgegevens op via project_users, project en klant
$queryClient = "SELECT k.klant_id, k.voornaam, k.achternaam, k.email, k.telefoon, k.bedrijfnaam 
                FROM project_users pu
                JOIN project p ON pu.project_id = p.project_id
                JOIN klant k ON p.klant_id = k.klant_id
                WHERE pu.user_id = :user_id
                LIMIT 1";
$stmtClient = $pdo->prepare($queryClient);
$stmtClient->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmtClient->execute();
$clientData = $stmtClient->fetch(PDO::FETCH_ASSOC);

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
  <img src="img/logo.png" alt="Logo" class="logo">
  <h1>Bedrijfsinformatie Portal</h1>
</header>

<?php include 'sidebar.php'; ?>

<!-- Bedrijfsinformatie-container met toggle -->
<div class="info-section bedrijfs-container">
    <!-- Toggle: Contacttekst + icoon -->
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

    <!-- Contactinformatie, aanvankelijk verborgen met slide-in effect -->
    <div class="contact-info" id="contact-info">
        <h3>Contactinformatie</h3>
        <?php if ($contactData): ?>
            <div class="info-item"><span class="info-label">Naam:</span> <?php echo htmlspecialchars($contactData['voornaam'] . " " . $contactData['achternaam']); ?></div>
            <div class="info-item"><span class="info-label">Email:</span> <?php echo htmlspecialchars($contactData['email']); ?></div>
            <div class="info-item"><span class="info-label">Telefoon:</span> <?php echo htmlspecialchars($contactData['telefoon']); ?></div>
            <div class="info-item"><span class="info-label">Bericht:</span> <?php echo htmlspecialchars($contactData['bericht']); ?></div>
            <div class="info-item"><span class="info-label">Verstuurd op:</span> <?php echo htmlspecialchars($contactData['created_at']); ?></div>
        <?php else: ?>
            <p>Geen contactinformatie beschikbaar.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Klantinformatie -->
<div class="info-section">
    <h2>Klantinformatie</h2>
    <?php if ($clientData): ?>
        <div class="info-item"><span class="info-label">Bedrijfsnaam:</span> <?php echo htmlspecialchars($clientData['bedrijfnaam']); ?></div>
        <div class="info-item"><span class="info-label">Contactpersoon:</span> <?php echo htmlspecialchars($clientData['voornaam'] . " " . $clientData['achternaam']); ?></div>
        <div class="info-item"><span class="info-label">Email:</span> <?php echo htmlspecialchars($clientData['email']); ?></div>
        <div class="info-item"><span class="info-label">Telefoon:</span> <?php echo htmlspecialchars($clientData['telefoon']); ?></div>
    <?php else: ?>
        <p>Geen klant gekoppeld.</p>
    <?php endif; ?>
</div>

<!-- Mijn Gegevens -->
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

<script>
  // Toggle de zichtbaarheid van de contactinformatie met een slide-in animatie
  const toggleIcon = document.querySelector('.toggle-contact-icon');
  const contactInfo = document.querySelector('.contact-info');

  toggleIcon.addEventListener('click', () => {
      contactInfo.classList.toggle('active');
  });
</script>
</body>
</html>
