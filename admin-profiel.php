<?php
session_start();
require_once 'db/conn.php';

// Only allow admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle POST submissions for both chiefs and contact updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_chiefs') {
        // Update chiefs record
        $chief_id    = $_POST['chief_id'];
        $telefoon    = $_POST['telefoon'];
        $adres       = $_POST['adres'];
        $bedrijfnaam = $_POST['bedrijfnaam'];
        $stad        = $_POST['stad'];
        $postcode    = $_POST['postcode'];
        $provincie   = $_POST['provincie'];
        $land        = $_POST['land'];
        
        try {
            $stmt = $pdo->prepare("UPDATE chiefs SET telefoon = ?, adres = ?, bedrijfnaam = ?, stad = ?, postcode = ?, provincie = ?, land = ? WHERE chief_id = ?");
            $stmt->execute([$telefoon, $adres, $bedrijfnaam, $stad, $postcode, $provincie, $land, $chief_id]);
            $chiefs_message = "Chief record updated successfully!";
        } catch (PDOException $e) {
            $chiefs_error = "Error updating chiefs: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'update_contact') {
        // Update contact record
        $contact_id  = $_POST['contact_id'];
        $voornaam    = $_POST['voornaam'];
        $achternaam  = $_POST['achternaam'];
        $email       = $_POST['email'];
        $telefoon    = $_POST['telefoon'];
        $bericht     = $_POST['bericht'];
        
        try {
            $stmt = $pdo->prepare("UPDATE contact SET voornaam = ?, achternaam = ?, email = ?, telefoon = ?, bericht = ? WHERE contact_id = ?");
            $stmt->execute([$voornaam, $achternaam, $email, $telefoon, $bericht, $contact_id]);
            $contact_message = "Contact record updated successfully!";
        } catch (PDOException $e) {
            $contact_error = "Error updating contact: " . $e->getMessage();
        }
    }
}

// Fetch chiefs data
try {
    $stmt = $pdo->query("SELECT * FROM chiefs");
    $chiefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error (chiefs): " . $e->getMessage());
}

// Fetch contact data
try {
    $stmt = $pdo->query("SELECT * FROM contact");
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error (contact): " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel: Edit Chiefs & Contact</title>
  <link rel="stylesheet" href="css/profiel.css">
  <style>
    /* Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: Arial, sans-serif;
      background-color: #f8f9fa;
      color: #333;
      padding: 20px;
    }
    /* Header with logo */
    header {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 20px;
    }
    header .logo {
      max-width: 150px;
    }
    header h1 {
      font-size: 2em;
      color: #6d0f10;
    }
    /* Notification styling */
    .notification {
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 5px;
      color: white;
    }
    .notification.success {
      background-color: #4CAF50;
    }
    .notification.error {
      background-color: #F44336;
    }
    /* Main container with vertical stacking */
    .main-container {
      display: flex;
      flex-direction: column;
      gap: 40px;
      margin-top: 20px;
    }
    /* Container styling */
    .container {
      background-color: #ffffff;
      border: 1px solid #6d0f10;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 900px;
      margin: 0 auto;
    }
    .container h2 {
      color: #6d0f10;
      margin-bottom: 15px;
      border-bottom: 2px solid #6d0f10;
      padding-bottom: 5px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    th, td {
      border: 1px solid #6d0f10;
      padding: 8px;
      text-align: left;
    }
    th {
      background-color: #6d0f10;
      color: #fff;
    }
    input[type="text"],
    input[type="email"],
    textarea {
      width: 95%;
      padding: 5px;
    }
    button {
      padding: 5px 10px;
      background-color: #6d0f10;
      color: #fff;
      border: none;
      border-radius: 3px;
      cursor: pointer;
    }
    button:hover {
      background-color: #5a0c0d;
    }
    /* Ensure sidebar displays correctly */
    .admin-sidebar {
      margin-bottom: 20px;
    }
    .sidebar{ 
        background: #f8f9fa;
  height: 120vh !important;
  width: 250px;
  position: fixed;
  z-index: 1000;
  box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-top: -30px !important;
  left: 10px;
    }
  </style>
</head>
<body>
  <?php include 'admin-sidebar.php'; ?>

  <header>
    <img src="img/logo.png" alt="Logo" class="logo">
    <h1>Admin Panel: Edit Chiefs & Contact</h1>
  </header>

  <div class="main-container">
    <!-- Chiefs Section -->
    <div class="container">
      <h2>Chiefs Information</h2>
      <?php if (isset($chiefs_message)): ?>
          <div class="notification success"><?= htmlspecialchars($chiefs_message) ?></div>
      <?php endif; ?>
      <?php if (isset($chiefs_error)): ?>
          <div class="notification error"><?= htmlspecialchars($chiefs_error) ?></div>
      <?php endif; ?>
      <?php if ($chiefs): ?>
          <table>
              <thead>
                  <tr>
                      <th>Telefoon</th>
                      <th>Adres</th>
                      <th>Bedrijfsnaam</th>
                      <th>Stad</th>
                      <th>Postcode</th>
                      <th>Provincie</th>
                      <th>Land</th>
                      <th>Save</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach ($chiefs as $chief): ?>
                  <tr>
                      <form method="post">
                          <input type="hidden" name="action" value="update_chiefs">
                          <input type="hidden" name="chief_id" value="<?= $chief['chief_id'] ?>">
                          <td><input type="text" name="telefoon" value="<?= htmlspecialchars($chief['telefoon']) ?>"></td>
                          <td><input type="text" name="adres" value="<?= htmlspecialchars($chief['adres']) ?>"></td>
                          <td><input type="text" name="bedrijfnaam" value="<?= htmlspecialchars($chief['bedrijfnaam']) ?>"></td>
                          <td><input type="text" name="stad" value="<?= htmlspecialchars($chief['stad']) ?>"></td>
                          <td><input type="text" name="postcode" value="<?= htmlspecialchars($chief['postcode']) ?>"></td>
                          <td><input type="text" name="provincie" value="<?= htmlspecialchars($chief['provincie']) ?>"></td>
                          <td><input type="text" name="land" value="<?= htmlspecialchars($chief['land']) ?>"></td>
                          <td><button type="submit">Save</button></td>
                      </form>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
      <?php else: ?>
          <p>No chiefs records found.</p>
      <?php endif; ?>
    </div>

    <!-- Contact Section -->
    <div class="container">
      <h2>Contact Information</h2>
      <?php if (isset($contact_message)): ?>
          <div class="notification success"><?= htmlspecialchars($contact_message) ?></div>
      <?php endif; ?>
      <?php if (isset($contact_error)): ?>
          <div class="notification error"><?= htmlspecialchars($contact_error) ?></div>
      <?php endif; ?>
      <?php if ($contacts): ?>
          <table>
              <thead>
                  <tr>
                      <th>Voornaam</th>
                      <th>Achternaam</th>
                      <th>Email</th>
                      <th>Telefoon</th>
                      <th>Bericht</th>
                      <th>Save</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach ($contacts as $contact): ?>
                  <tr>
                      <form method="post">
                          <input type="hidden" name="action" value="update_contact">
                          <input type="hidden" name="contact_id" value="<?= $contact['contact_id'] ?>">
                          <td><input type="text" name="voornaam" value="<?= htmlspecialchars($contact['voornaam']) ?>"></td>
                          <td><input type="text" name="achternaam" value="<?= htmlspecialchars($contact['achternaam']) ?>"></td>
                          <td><input type="email" name="email" value="<?= htmlspecialchars($contact['email']) ?>"></td>
                          <td><input type="text" name="telefoon" value="<?= htmlspecialchars($contact['telefoon']) ?>"></td>
                          <td><textarea name="bericht"><?= htmlspecialchars($contact['bericht']) ?></textarea></td>
                          <td><button type="submit">Save</button></td>
                      </form>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
      <?php else: ?>
          <p>No contact records found.</p>
      <?php endif; ?>
    </div>
  </div>
  
</body>
</html>
