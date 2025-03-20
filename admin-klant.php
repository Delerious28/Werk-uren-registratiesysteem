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
      
      .btn-info {
          background-color: #0d6efd !important;
      }
      
      .modal {
          position: fixed;
          top: 220px !important;
          left: 50% !important;
          transform: translateX(-50%);
          z-index: 1055;
          display: none;
          width: 40%;
          max-width: 500px;
          overflow-x: hidden;
          overflow-y: auto;
          outline: 0;
      }
      
      .modal-content {
          border: none;
          padding: 20px;
          height: auto;
          position: relative;
      }
      
      .modal-content form {
          max-width: 320px;
          margin: 0 auto;
          text-align: left;
      }
      
      h2 {
          margin: auto;
      }
      
      .overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0,0,0,0.7);
          z-index: 1050;
          display: none;
      }
      
      .btn-success {
          background-color: #6d0f10 !important;
          border-color: transparent !important;
          position: relative;
          left: 50%;
          transform: translateX(-50%);
          padding: 8px;
          margin-top: 0;
      }
      
      /* Zorg dat de sluit-knop zichtbaar is */
      .close-btn {
          display: block;
          position: absolute;
          top: 10px;
          right: 10px;
          font-size: 24px;
          cursor: pointer;
          background: #f0f0f0;
          border: 1px solid #ccc;
          border-radius: 50%;
          width: 30px;
          height: 30px;
          text-align: center;
          line-height: 30px;
      }
      
      .modal-content .form-control {
          max-width: 300px;
      }
      
      /* Styling voor de add customer container */
      .add-customer-container {
          margin: 20px auto;
          padding: 20px;
          background-color: #f9f9f9;
      }
      
      .add-customer-container form div {
          margin-bottom: 10px;
      }
      
      .add-customer-container label {
          display: block;
          margin-bottom: 5px;
      }
      
      .add-customer-container input[type="text"],
      .add-customer-container input[type="email"] {
          width: 100%;
          padding: 8px;
          box-sizing: border-box;
      }
      
      .add-customer-container button {
          padding: 10px 20px;
          background-color: #0d6efd;
          color: #fff;
          border: none;
          cursor: pointer;
      }
  </style>
</head>
<body>
  <?php include 'admin-sidebar.php'; ?>

  <main>
    <!-- Container voor het toevoegen van een nieuwe klant -->
    <div class="add-customer-container">
      <h2>Voeg nieuwe klant toe</h2>
      
      <?php if(isset($error)): ?>
          <p style="color: red;"><?= $error ?></p>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="action" value="add">
        <div>
          <label for="bedrijfnaam">Bedrijfsnaam:</label>
          <input type="text" name="bedrijfnaam" id="bedrijfnaam" required>
        </div>
        <div>
          <label for="voornaam">Voornaam:</label>
          <input type="text" name="voornaam" id="voornaam" required>
        </div>
        <div>
          <label for="achternaam">Achternaam:</label>
          <input type="text" name="achternaam" id="achternaam" required>
        </div>
        <div>
          <label for="email">Email:</label>
          <input type="email" name="email" id="email" required>
        </div>
        <div>
          <label for="telefoon">Telefoon:</label>
          <input type="text" name="telefoon" id="telefoon" required>
        </div>
        <button type="submit">Klant Toevoegen</button>
      </form>
    </div>

    <section id="clients">
      <h2>Klantenbeheer</h2>
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Bedrijfsnaam</th>
            <th>Contactpersoon</th>
            <th>Email</th>
            <th>Telefoon</th>
            <th>Bewerk</th>
            <th>Verwijder</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($klanten as $klant): ?>
            <tr>
              <td><?= htmlspecialchars($klant['bedrijfnaam']) ?></td>
              <td><?= htmlspecialchars($klant['voornaam']) ?> <?= htmlspecialchars($klant['achternaam']) ?></td>
              <td><?= htmlspecialchars($klant['email']) ?></td>
              <td><?= htmlspecialchars($klant['telefoon']) ?></td>
              <td>
                <button class="edit-btn btn btn-info"
                        data-klant_id="<?= $klant['klant_id'] ?>"
                        data-bedrijfnaam="<?= htmlspecialchars($klant['bedrijfnaam']) ?>"
                        data-voornaam="<?= htmlspecialchars($klant['voornaam']) ?>"
                        data-achternaam="<?= htmlspecialchars($klant['achternaam']) ?>"
                        data-email="<?= htmlspecialchars($klant['email']) ?>"
                        data-telefoon="<?= htmlspecialchars($klant['telefoon']) ?>">
                  Bewerk
                </button>
              </td>
              <td>
                <form method="post" onsubmit="return confirm('Weet je zeker dat je deze klant wilt verwijderen?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="klant_id" value="<?= $klant['klant_id'] ?>">
                  <button type="submit" class="btn btn-danger">Verwijder</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- Overlay voor donkere achtergrond -->
    <div id="overlay" class="overlay"></div>

    <!-- Modal Container voor bewerken -->
    <div id="editModal" class="modal">
      <div class="modal-content">
        <!-- Zichtbare sluit-knop -->
        <span id="closeModal" class="close-btn">&times;</span>
        <h2>Bewerk Klant</h2>
        <form method="post">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="klant_id" id="edit-klant_id">
          <div>
            <label for="edit-bedrijfnaam">Bedrijfsnaam:</label>
            <input type="text" name="bedrijfnaam" id="edit-bedrijfnaam" class="form-control" required>
          </div>
          <div>
            <label for="edit-voornaam">Voornaam:</label>
            <input type="text" name="voornaam" id="edit-voornaam" class="form-control" required>
          </div>
          <div>
            <label for="edit-achternaam">Achternaam:</label>
            <input type="text" name="achternaam" id="edit-achternaam" class="form-control" required>
          </div>
          <div>
            <label for="edit-email">Email:</label>
            <input type="email" name="email" id="edit-email" class="form-control" required>
          </div>
          <div>
            <label for="edit-telefoon">Telefoon:</label>
            <input type="text" name="telefoon" id="edit-telefoon" class="form-control" required>
          </div>
          <br>
          <button type="submit" class="btn btn-success">Opslaan</button>
        </form>
      </div>
    </div>
  </main>

  <script>
    // Elementen ophalen
    var modal = document.getElementById('editModal');
    var closeModal = document.getElementById('closeModal');
    var overlay = document.getElementById('overlay');

    // Functie om modal en overlay te sluiten
    function closeModalAndOverlay() {
      modal.style.display = 'none';
      overlay.style.display = 'none';
    }

    // Sluit modal bij klikken op de sluit-knop
    closeModal.addEventListener('click', closeModalAndOverlay);

    // Sluit modal als er buiten de modal wordt geklikt
    window.addEventListener('click', function(event) {
      if (event.target === overlay) {
        closeModalAndOverlay();
      }
    });

    // Voeg eventlisteners toe aan alle bewerkknoppen
    var editButtons = document.getElementsByClassName('edit-btn');
    for (var i = 0; i < editButtons.length; i++) {
      editButtons[i].addEventListener('click', function() {
        // Haal data-attributen op
        var klant_id    = this.getAttribute('data-klant_id');
        var bedrijfnaam = this.getAttribute('data-bedrijfnaam');
        var voornaam    = this.getAttribute('data-voornaam');
        var achternaam  = this.getAttribute('data-achternaam');
        var email       = this.getAttribute('data-email');
        var telefoon    = this.getAttribute('data-telefoon');

        // Vul de formuliervelden in
        document.getElementById('edit-klant_id').value = klant_id;
        document.getElementById('edit-bedrijfnaam').value = bedrijfnaam;
        document.getElementById('edit-voornaam').value = voornaam;
        document.getElementById('edit-achternaam').value = achternaam;
        document.getElementById('edit-email').value = email;
        document.getElementById('edit-telefoon').value = telefoon;

        // Toon de modal en overlay
        modal.style.display = 'block';
        overlay.style.display = 'block';
      });
    }
  </script>
</body>
</html>
<?php if(isset($message)): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
  // Maak het notificatie-element aan
  var notification = document.createElement("div");
  notification.innerText = "<?= $message ?>";
  notification.style.position = "fixed";
  notification.style.top = "20px";
  notification.style.right = "20px";
  notification.style.padding = "10px 20px";
  notification.style.backgroundColor = "#28a745"; // groen voor succes
  notification.style.color = "#fff";
  notification.style.borderRadius = "5px";
  notification.style.boxShadow = "0 0 10px rgba(0,0,0,0.3)";
  notification.style.zIndex = "10000";
  
  // Voeg het notificatie-element toe aan de body
  document.body.appendChild(notification);
  
  // Laat de notificatie na 3 seconden verdwijnen
  setTimeout(function(){
    notification.style.transition = "opacity 0.5s ease";
    notification.style.opacity = "0";
    setTimeout(function(){
      notification.remove();
    }, 500);
  }, 3000);
});
</script>
<?php endif; ?>
