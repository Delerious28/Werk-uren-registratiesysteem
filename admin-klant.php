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

// Verwerk update, delete of add actie als er een POST-request is
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update') {
        // Update klantgegevens
        $klant_id    = $_POST['klant_id'];
        $voornaam    = $_POST['voornaam'];
        $achternaam  = $_POST['achternaam'];
        $email       = $_POST['email'];
        $telefoon    = $_POST['telefoon'];
        $bedrijfnaam = $_POST['bedrijfnaam'];
         
        try {
            $stmt = $pdo->prepare("UPDATE klant SET voornaam = ?, achternaam = ?, email = ?, telefoon = ?, bedrijfnaam = ? WHERE klant_id = ?");
            $stmt->execute([$voornaam, $achternaam, $email, $telefoon, $bedrijfnaam, $klant_id]);
            $message = "Klant succesvol bijgewerkt!";
        } catch (PDOException $e) {
            $error = "Database fout: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete') {
        // Verwijder klant
        $klant_id = $_POST['klant_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM klant WHERE klant_id = ?");
            $stmt->execute([$klant_id]);
            $message = "Klant succesvol verwijderd!";
        } catch (PDOException $e) {
            $error = "Database fout: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'add') {
        // Voeg nieuwe klant toe
        $voornaam    = $_POST['voornaam'];
        $achternaam  = $_POST['achternaam'];
        $email       = $_POST['email'];
        $telefoon    = $_POST['telefoon'];
        $bedrijfnaam = $_POST['bedrijfnaam'];
        try {
            $stmt = $pdo->prepare("INSERT INTO klant (voornaam, achternaam, email, telefoon, bedrijfnaam) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$voornaam, $achternaam, $email, $telefoon, $bedrijfnaam]);
            $message = "Klant succesvol toegevoegd!";
        } catch (PDOException $e) {
            $error = "Database fout: " . $e->getMessage();
        }
    }
}

try {
    // Ophalen van de data
    $usersCount        = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $klantenCount      = $pdo->query("SELECT COUNT(*) FROM klant")->fetchColumn();
    $pendingHoursCount = $pdo->query("SELECT COUNT(*) FROM hours WHERE accord = 'Pending'")->fetchColumn();
    $projects          = $pdo->query("SELECT project_id, project_naam, klant_id, beschrijving, contract_uren FROM project")->fetchAll(PDO::FETCH_ASSOC);
    $hours             = $pdo->query("SELECT * FROM hours")->fetchAll(PDO::FETCH_ASSOC);
    $klanten           = $pdo->query("SELECT * FROM klant")->fetchAll(PDO::FETCH_ASSOC);
    $users             = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database fout: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Klantenbeheer</title>
  <style>
    /* Basis reset en body-styling */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      color: #333;
      line-height: 1.6;
    }
    
    /* Sidebar (oorspronkelijk uiterlijk) */
    .sidebar {
      background: #f8f9fa;
      height: 100vh;
      width: 250px;
      position: fixed;
      z-index: 1000;
      box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
      padding: 20px;
      margin-top: 1px !important;
      left: 10px;
    }
    .sidebar a {
      color: #333;
      text-decoration: none;
      display: block;
      margin-bottom: 10px;
    }
    
    /* Main content */
    .my-main {
      margin-left: 270px;
      padding: 20px;
    }
    
    /* Sectie container */
    .my-section {
      background: #fff;
      border: 1px solid #6d0f10;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      overflow: hidden;
    }
    .my-section h2 {
      background: #6d0f10;
      color: #fff;
      padding: 15px;
      font-size: 1.8rem;
    }
    
    /* Formulier elementen */
    .my-section .my-form {
      padding: 20px;
    }
    .my-form div {
      margin-bottom: 15px;
    }
    .my-form label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }
    .my-form input[type="text"],
    .my-form input[type="email"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
    }
    .my-form input:focus {
      border-color: #6d0f10;
      outline: none;
    }
    
    /* Knop styling */
    .my-btn {
      padding: 10px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1rem;
    }
    /* Klant Toevoegen: blauw */
    .my-btn-info {
      background: green;
      color: #fff;
    }
    /* Bewerk: oranje */
    .my-btn-warning {
      background: #FFA500;
      color: #fff;
    }
    .my-btn-success {
      background: #6d0f10;
      color: #fff;
    }
    .my-btn-danger {
      background: #d9534f;
      color: #fff;
    }
    /* Kleine marge tussen actieknoppen */
    .my-action-btns .my-btn {
      margin-right: 5px;
    }
    
    /* Tabel styling */
    .my-table {
      width: 100%;
      border-collapse: collapse;
    }
    .my-table th, .my-table td {
      padding: 12px 15px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }
    .my-table th {
      background: #6d0f10;
      color: #fff;
    }
    .my-table tr:hover {
      background: #f9f9f9;
    }
    
    /* Modal styling: verschijnt precies in het midden */
    .my-modal {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      width: 90%;
      max-width: 500px;
      display: none;
      z-index: 1055;
    }
    .my-modal-content {
      padding: 20px;
      position: relative;
    }
    .my-modal-content h2 {
      background: #6d0f10;
      color: #fff;
      margin: 0;
      padding: 15px;
      border-radius: 10px 10px 0 0;
      font-size: 1.8rem;
    }
    .my-close-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      background: #ccc;
      color: #333;
      border: none;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      text-align: center;
      line-height: 30px;
      cursor: pointer;
    }
    
    /* Overlay voor modal */
    .my-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.7);
      display: none;
      z-index: 1050;
    }
    
    /* Succes notificatie */
    .my-notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 10px 20px;
      background-color: #28a745;
      color: #fff;
      border-radius: 5px;
      box-shadow: 0 0 10px rgba(0,0,0,0.3);
      z-index: 10000;
    }
  </style>
</head>
<body>
  <!-- Sidebar (oorspronkelijk uiterlijk) -->
  <?php include 'admin-sidebar.php'; ?>

  <main class="my-main">
    <!-- Sectie: Nieuwe klant toevoegen -->
    <div class="add-customer-container my-section">
      <h2>Voeg nieuwe klant toe</h2>
      <?php if(isset($error)): ?>
          <p style="color: red;"><?= $error ?></p>
      <?php endif; ?>
      <form class="my-form" method="post">
        <input type="hidden" name="action" value="add">
        <div>
          <label for="bedrijfnaam">Bedrijfsnaam:</label>
          <input class="my-input" type="text" name="bedrijfnaam" id="bedrijfnaam" required>
        </div>
        <div>
          <label for="voornaam">Voornaam:</label>
          <input class="my-input" type="text" name="voornaam" id="voornaam" required>
        </div>
        <div>
          <label for="achternaam">Achternaam:</label>
          <input class="my-input" type="text" name="achternaam" id="achternaam" required>
        </div>
        <div>
          <label for="email">Email:</label>
          <input class="my-input" type="email" name="email" id="email" required>
        </div>
        <div>
          <label for="telefoon">Telefoon:</label>
          <input class="my-input" type="text" name="telefoon" id="telefoon" required>
        </div>
        <button type="submit" class="my-btn my-btn-info">Klant Toevoegen</button>
      </form>
    </div>

    <!-- Sectie: Klantenbeheer -->
    <section id="clients" class="clients-container my-section">
      <h2>Klantenbeheer</h2>
      <table class="my-table">
        <thead>
          <tr>
            <th>Bedrijfsnaam</th>
            <th>Contactpersoon</th>
            <th>Email</th>
            <th>Telefoon</th>
            <th>Acties</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($klanten as $klant): ?>
            <tr>
              <td><?= htmlspecialchars($klant['bedrijfnaam']) ?></td>
              <td><?= htmlspecialchars($klant['voornaam']) ?> <?= htmlspecialchars($klant['achternaam']) ?></td>
              <td><?= htmlspecialchars($klant['email']) ?></td>
              <td><?= htmlspecialchars($klant['telefoon']) ?></td>
              <td class="my-action-btns">
                <button class="edit-btn my-btn my-btn-warning"
                        data-klant_id="<?= $klant['klant_id'] ?>"
                        data-bedrijfnaam="<?= htmlspecialchars($klant['bedrijfnaam']) ?>"
                        data-voornaam="<?= htmlspecialchars($klant['voornaam']) ?>"
                        data-achternaam="<?= htmlspecialchars($klant['achternaam']) ?>"
                        data-email="<?= htmlspecialchars($klant['email']) ?>"
                        data-telefoon="<?= htmlspecialchars($klant['telefoon']) ?>">
                  Bewerk
                </button>
                <form method="post" style="display:inline;" onsubmit="return confirm('Weet je zeker dat je deze klant wilt verwijderen?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="klant_id" value="<?= $klant['klant_id'] ?>">
                  <button type="submit" class="my-btn my-btn-danger">Verwijder</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- Overlay voor modal -->
    <div id="myOverlay" class="my-overlay"></div>

    <!-- Modal: Klant bewerken -->
    <div id="myModal" class="my-modal">
      <div class="my-modal-content">
        <span id="myCloseModal" class="my-close-btn">&times;</span>
        <h2>Bewerk Klant</h2>
        <form class="my-form" method="post">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="klant_id" id="edit-klant_id">
          <div>
            <label for="edit-bedrijfnaam">Bedrijfsnaam:</label>
            <input class="my-input" type="text" name="bedrijfnaam" id="edit-bedrijfnaam" required>
          </div>
          <div>
            <label for="edit-voornaam">Voornaam:</label>
            <input class="my-input" type="text" name="voornaam" id="edit-voornaam" required>
          </div>
          <div>
            <label for="edit-achternaam">Achternaam:</label>
            <input class="my-input" type="text" name="achternaam" id="edit-achternaam" required>
          </div>
          <div>
            <label for="edit-email">Email:</label>
            <input class="my-input" type="email" name="email" id="edit-email" required>
          </div>
          <div>
            <label for="edit-telefoon">Telefoon:</label>
            <input class="my-input" type="text" name="telefoon" id="edit-telefoon" required>
          </div>
          <br>
          <button type="submit" class="my-btn my-btn-success">Opslaan</button>
        </form>
      </div>
    </div>
  </main>

  <script>
    // Modal functionaliteit met nieuwe classnamen
    var myModal = document.getElementById('myModal');
    var myCloseModal = document.getElementById('myCloseModal');
    var myOverlay = document.getElementById('myOverlay');

    function closeMyModal() {
      myModal.style.display = 'none';
      myOverlay.style.display = 'none';
    }

    myCloseModal.addEventListener('click', closeMyModal);
    window.addEventListener('click', function(event) {
      if (event.target === myOverlay) {
        closeMyModal();
      }
    });

    var editBtns = document.getElementsByClassName('edit-btn');
    for (var i = 0; i < editBtns.length; i++) {
      editBtns[i].addEventListener('click', function() {
        var klant_id    = this.getAttribute('data-klant_id');
        var bedrijfnaam = this.getAttribute('data-bedrijfnaam');
        var voornaam    = this.getAttribute('data-voornaam');
        var achternaam  = this.getAttribute('data-achternaam');
        var email       = this.getAttribute('data-email');
        var telefoon    = this.getAttribute('data-telefoon');

        document.getElementById('edit-klant_id').value = klant_id;
        document.getElementById('edit-bedrijfnaam').value = bedrijfnaam;
        document.getElementById('edit-voornaam').value = voornaam;
        document.getElementById('edit-achternaam').value = achternaam;
        document.getElementById('edit-email').value = email;
        document.getElementById('edit-telefoon').value = telefoon;

        myModal.style.display = 'block';
        myOverlay.style.display = 'block';
      });
    }
  </script>
  
  <?php if(isset($message)): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Succes notificatie
      var myNotification = document.createElement("div");
      myNotification.className = "my-notification";
      myNotification.innerText = "<?= $message ?>";
      document.body.appendChild(myNotification);
      
      setTimeout(function(){
        myNotification.style.transition = "opacity 0.5s ease";
        myNotification.style.opacity = "0";
        setTimeout(function(){
          myNotification.remove();
        }, 500);
      }, 3000);
    });
  </script>
  <?php endif; ?>
</body>
</html>
