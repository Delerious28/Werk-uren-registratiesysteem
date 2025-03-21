<?php
// Als er een live email-check wordt uitgevoerd, handel die dan direct af
if (isset($_GET['check_email']) && isset($_GET['email'])) {
    require __DIR__ . '/db/conn.php';
    $email = trim($_GET['email']);

    // Controleer in de tabel 'klant'
    $stmtKlant = $pdo->prepare("SELECT COUNT(*) FROM klant WHERE email = ?");
    $stmtKlant->execute([$email]);
    $countKlant = $stmtKlant->fetchColumn();

    // Controleer in de tabel 'users'
    $stmtUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmtUser->execute([$email]);
    $countUser = $stmtUser->fetchColumn();

    if ($countKlant > 0 || $countUser > 0) {
        $exists = true;
        $message = "Deze email is al in gebruik!";
    } else {
        $exists = false;
        $message = "Deze email is beschikbaar.";
    }
    header('Content-Type: application/json');
    echo json_encode(['exists' => $exists, 'message' => $message]);
    exit;
}

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
        // Update klantgegevens (wachtwoord blijft ongewijzigd)
        $klant_id    = $_POST['klant_id'];
        $voornaam    = $_POST['voornaam'];
        $achternaam  = $_POST['achternaam'];
        $email       = $_POST['email'];
        $telefoon    = $_POST['telefoon'];
        $bedrijfnaam = $_POST['bedrijfnaam'];

        // Controleer of de e-mail al in gebruik is in de tabel klant (anders dan deze klant) of in de tabel users
        $stmtEmailKlant = $pdo->prepare("SELECT COUNT(*) FROM klant WHERE email = ? AND klant_id != ?");
        $stmtEmailKlant->execute([$email, $klant_id]);
        $countKlant = $stmtEmailKlant->fetchColumn();

        $stmtEmailUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmtEmailUser->execute([$email]);
        $countUser = $stmtEmailUser->fetchColumn();

        if ($countKlant > 0 || $countUser > 0) {
            $error = "Deze email is al in gebruik!";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE klant SET voornaam = ?, achternaam = ?, email = ?, telefoon = ?, bedrijfnaam = ? WHERE klant_id = ?");
                $stmt->execute([$voornaam, $achternaam, $email, $telefoon, $bedrijfnaam, $klant_id]);
                $message = "Klant succesvol bijgewerkt!";
            } catch (PDOException $e) {
                $error = "Database fout: " . $e->getMessage();
            }
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
        $wachtwoord  = $_POST['wachtwoord'];

        // Controleer of de e-mail al in gebruik is in zowel de tabel klant als users
        $stmtEmailKlant = $pdo->prepare("SELECT COUNT(*) FROM klant WHERE email = ?");
        $stmtEmailKlant->execute([$email]);
        $countKlant = $stmtEmailKlant->fetchColumn();

        $stmtEmailUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmtEmailUser->execute([$email]);
        $countUser = $stmtEmailUser->fetchColumn();

        if ($countKlant > 0 || $countUser > 0) {
            $error = "Deze email is al in gebruik!";
        } else {
            try {
                // Hash het wachtwoord
                $hashedPassword = password_hash($wachtwoord, PASSWORD_DEFAULT);
                // Voeg de klant inclusief wachtwoord toe (let op: kolomnaam is 'password')
                $stmt = $pdo->prepare("INSERT INTO klant (voornaam, achternaam, email, telefoon, bedrijfnaam, password) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$voornaam, $achternaam, $email, $telefoon, $bedrijfnaam, $hashedPassword]);
                $message = "Klant succesvol toegevoegd!";
            } catch (PDOException $e) {
                $error = "Database fout: " . $e->getMessage();
            }
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
  <link rel="stylesheet" href="css/admin-klant.css">
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
          <!-- Feedback element -->
          <span id="emailFeedback" style="margin-left:10px;"></span>
        </div>
        <!-- Nieuw: Wachtwoord invoeren -->
        <div>
          <label for="wachtwoord">Wachtwoord:</label>
          <input class="my-input" type="password" name="wachtwoord" id="wachtwoord" required>
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

    <!-- Modal: Klant bewerken (zonder wachtwoord veld) -->
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
    // Live email check voor de 'nieuwe klant toevoegen'-form
    document.getElementById('email').addEventListener('input', function() {
      const email = this.value;
      const feedbackEl = document.getElementById('emailFeedback');
      const submitBtn = document.querySelector('form.my-form button[type="submit"]');

      if (email.length > 0) {
        fetch("<?php echo basename(__FILE__); ?>?check_email=1&email=" + encodeURIComponent(email))
          .then(response => response.json())
          .then(data => {
            if (data.exists) {
              feedbackEl.textContent = data.message;
              feedbackEl.style.color = "red";
              submitBtn.disabled = true;
            } else {
              feedbackEl.textContent = data.message;
              feedbackEl.style.color = "green";
              submitBtn.disabled = false;
            }
          })
          .catch(() => {
            feedbackEl.textContent = "";
            submitBtn.disabled = false;
          });
      } else {
        feedbackEl.textContent = "";
        submitBtn.disabled = false;
      }
    });

    // Modal functionaliteit
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
