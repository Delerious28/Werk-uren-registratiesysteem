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

// Verwerk formulierinzendingen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['field']) && isset($_POST['value'])) {
    $field = filter_input(INPUT_POST, 'field', FILTER_SANITIZE_STRING);
    $value = filter_input(INPUT_POST, 'value', FILTER_SANITIZE_STRING);

    if (!$field || !$value) {
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

    try {
        $sql = "UPDATE $table SET $column = :value WHERE $idColumn = :id";
        error_log("SQL Query: " . $sql);
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['value' => $value, 'id' => $_SESSION['user_id']]);

        echo json_encode(['status' => 'success', 'message' => 'Veld bijgewerkt']);
    } catch (PDOException $e) {
        error_log("Update query mislukt: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Kon veld niet bijwerken: ' . $e->getMessage()]);
    }
    exit();
}

// SSE-endpoint
if (isset($_GET['action']) && $_GET['action'] === 'sse') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');

    $lastId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? $_SERVER['HTTP_LAST_EVENT_ID'] : 0;

    while (true) {
        try {
            $sql = "SELECT Bedrijfnaam, telefoon, adres, stad, postcode, provincie, land FROM chiefs WHERE chief_id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $data = json_encode($row);
                echo "id: " . ++$lastId . "\n";
                echo "data: " . $data . "\n\n";
            }
        } catch (PDOException $e) {
            error_log("SSE query mislukt: " . $e->getMessage());
        }

        ob_flush();
        flush();
        sleep(5);
    }
    exit();
}

// Haal gegevens op uit de chiefs tabel
try {
    $sql = "SELECT telefoon, adres, stad, postcode, provincie, land, Bedrijfnaam FROM chiefs WHERE chief_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        die("Geen gegevens gevonden voor chiefs");
    }

    $telefoon = $row['telefoon'];
    $adres = $row['adres'];
    $stad = $row['stad'];
    $postcode = $row['postcode'];
    $provincie = $row['provincie'];
    $land = $row['land'];
    $bedrijfsnaam = $row['Bedrijfnaam'];
} catch (PDOException $e) {
    error_log("Chiefs query mislukt: " . $e->getMessage());
    die("Er is iets mis met de query voor chiefs: " . $e->getMessage());
}

// Haal klantgegevens op
try {
    $klant_sql = "SELECT klant_id, klant_voornaam, klant_achternaam, email, telefoon FROM klant WHERE klant_id = :id";
    $klant_stmt = $pdo->prepare($klant_sql);
    $klant_stmt->execute(['id' => $_SESSION['user_id']]);
    $klant_row = $klant_stmt->fetch(PDO::FETCH_ASSOC);

    if ($klant_row) {
        $klant_id = $klant_row['klant_id'];
        $klant_voornaam = $klant_row['klant_voornaam'];
        $klant_achternaam = $klant_row['klant_achternaam'];
        $klant_email = $klant_row['email'];
        $klant_telefoon = $klant_row['telefoon'];
    } else {
        $klant_id = null;
        $klant_voornaam = 'Onbekend';
        $klant_achternaam = 'Onbekend';
        $klant_email = 'Onbekend';
        $klant_telefoon = 'Onbekend';
    }
} catch (PDOException $e) {
    error_log("Klant query mislukt: " . $e->getMessage());
    die("Er is iets mis met de query voor klant: " . $e->getMessage());
}

// Haal contactgegevens op
try {
    $contact_sql = "SELECT voornaam, achternaam, email, telefoon FROM contact WHERE contact_id = :id";
    $contact_stmt = $pdo->prepare($contact_sql);
    $contact_stmt->execute(['id' => $_SESSION['user_id']]);
    $contact_row = $contact_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contact_row) {
        die("Geen contactgegevens gevonden");
    }

    $contact_voornaam = $contact_row['voornaam'];
    $contact_achternaam = $contact_row['achternaam'];
    $contact_email = $contact_row['email'];
    $contact_telefoon = $contact_row['telefoon'];
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
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('.container');
    const notificationContainer = document.getElementById('notification-container');

    // Event delegation voor toggle buttons
    container.addEventListener('click', function (event) {
        if (event.target.classList.contains('toggle-button')) {
            const targetId = event.target.dataset.target;
            console.log("Toggle button clicked, target:", targetId);
            toggleContainer(targetId);
        }
    });

    // Event delegation voor edit buttons
    container.addEventListener('click', function (event) {
        if (event.target.classList.contains('edit-button')) {
            const fieldName = event.target.dataset.field;
            const currentValue = event.target.dataset.value;
            console.log("Edit button clicked, field:", fieldName, "value:", currentValue);
            editField(fieldName, currentValue);
        }
    });

    // Zorg ervoor dat de bedrijf-container zichtbaar is bij het laden van de pagina
    toggleContainer('bedrijfContainer');
    console.log("Page loaded, showing bedrijfContainer");
});

async function saveField(fieldName) {
    const newValue = document.getElementById(`edit${fieldName}`).value;
    console.log("Saving field:", fieldName, "new value:", newValue);

    // Bepaal het ID van de klant of de andere entiteit (chief/contact)
    const entityId = document.getElementById(fieldName).dataset.id;

    try {
        const response = await fetch('profiel.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `field=${encodeURIComponent(fieldName)}&value=${encodeURIComponent(newValue)}&id=${encodeURIComponent(entityId)}`,
        });

        console.log("Fetch response:", response);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log("JSON response:", data);

        if (data.status === 'success') {
            document.getElementById(fieldName).textContent = newValue;
            console.log("Field updated successfully");
            showNotification('Veld succesvol bijgewerkt!', 'success'); // Succesbericht
        } else {
            showNotification('Er is een fout opgetreden bij het bijwerken van het veld.', 'error'); // Foutbericht
            console.error("Field update failed:", data.message);
        }
    } catch (error) {
        console.error('Er is een probleem met de serverrespons:', error);
        showNotification('Er is een probleem met de serverrespons.', 'error'); // Foutbericht
    }
}

function editField(fieldName, currentValue) {
    const fieldElement = document.getElementById(fieldName);
    fieldElement.innerHTML = `
        <input type="text" id="edit${fieldName}" value="${currentValue}">
        <button onclick="saveField('${fieldName}')">Opslaan</button>
    `;
    console.log("Field made editable:", fieldName);
}

function toggleContainer(containerId) {
    const containers = document.querySelectorAll('.container-section');
    containers.forEach(container => {
        container.style.display = container.id === containerId ? 'flex' : 'none';
    });
    console.log("Toggled container:", containerId);
}

function showNotification(message, type) {
    const notificationContainer = document.getElementById('notification-container');
    notificationContainer.textContent = message;
    notificationContainer.className = `notification ${type}`;
    notificationContainer.style.display = 'block';

    // Verberg de notificatie na 3 seconden
    setTimeout(() => {
        notificationContainer.classList.add('hide'); // Voeg de 'hide' class toe
    }, 3000); // Verberg de melding na 3 seconden

    // Zorg ervoor dat de notificatie na de animatie verdwijnt
    setTimeout(() => {
        notificationContainer.style.display = 'none';
        notificationContainer.classList.remove('hide'); // Verwijder de 'hide' class voor de volgende keer
    }, 3300); // 3300 ms omdat de animatie 300ms duurt
}

</script>

</head>
<body>
<style>
    /* CSS-stijlen blijven hetzelfde */
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: white;
        overflow: hidden;
        background-image: url('img/achtergrond.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        height: 100vh;
        font-size: 20px;
    }

    .container {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid rgb(0, 0, 0);
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        width: 900px;
        text-align: center;
        position: relative;
        height: 520px;
    }

    .buttons {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
        gap: 20px;
    }

    .buttons button {
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        background-color: #5f0101;
        color: white;
        cursor: pointer;
        font-size: 18px;
        transition: transform 0.3s ease, background-color 0.3s ease;
    }

    .buttons button:hover {
        transform: scale(1.05);
        background-color: #d00b0b;
    }

    .container-section {
        display: none;
        opacity: ;
        transition: opacity 0.5s ease-in-out;
        width: 100%;
    }

    .fade-in {
        opacity: 1;
    }

    .columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        width: 100%;
        text-align: center;
        margin-top: 20px;
    }

    .column {
        padding: 10px;
        background-color: transparent;
        border-radius: 8px;
    }

    .column h3 {
        margin-bottom: 10px;
        color: #333;
        font-size: 20px;
    }

    .column p {
        margin: -8px;
        font-size: 18px;
        color: #666;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .column p img {
        margin-right: 10px;
        width: 16px;
        height: 16px;
        cursor: pointer;
    }

    #klantContainer {
        position: relative;
        left: 25%;
    }
    .notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 5px;
    color: white;
    display: none;
    z-index: 1000;
}

.notification {
    animation: scaleUp 0.3s ease-out forwards;
}

@keyframes scaleUp {
    0% {
        transform: scale(0);
    }
    100% {
        transform: scale(1);
    }
}

.notification.hide {
    animation: scaleDown 0.3s ease-in forwards;
}

@keyframes scaleDown {
    0% {
        transform: scale(1);
    }
    100% {
        transform: scale(0);
    }
}

.notification.success {
    background-color: #4CAF50; /* Groen voor succes */
}

.notification.error {
    background-color: #F44336; /* Rood voor fout */
}



</style>
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
        <div class="columns">
            <div class="column">
                <h3>Bedrijfsnaam</h3>
                <p>
                <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                    data-field="Bedrijfnaam" data-value="<?php echo htmlspecialchars($bedrijfsnaam); ?>"
                    class="edit-button">
                <span id="Bedrijfnaam"><?php echo htmlspecialchars($bedrijfsnaam); ?></span>
            </p>
                <h3>Telefoon</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                        data-field="telefoon" data-value="<?php echo htmlspecialchars($telefoon); ?>"
                        class="edit-button">
                    <span id="telefoon"><?php echo htmlspecialchars($telefoon); ?></span>
                </p>
                <h3>Adres</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                        data-field="adres" data-value="<?php echo htmlspecialchars($adres); ?>"
                        class="edit-button">
                    <span id="adres"><?php echo htmlspecialchars($adres); ?></span>
                </p>
                <h3>Stad</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                        data-field="stad" data-value="<?php echo htmlspecialchars($stad); ?>"
                        class="edit-button">
                    <span id="stad"><?php echo htmlspecialchars($stad); ?></span>
                </p>
                <h3>Postcode</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                        data-field="postcode" data-value="<?php echo htmlspecialchars($postcode); ?>"
                        class="edit-button">
                    <span id="postcode"><?php echo htmlspecialchars($postcode); ?></span>
                </p>
                <h3>Provincie</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                        data-field="provincie" data-value="<?php echo htmlspecialchars($provincie); ?>"
                        class="edit-button">
                    <span id="provincie"><?php echo htmlspecialchars($provincie); ?></span>
                </p>
                <h3>Land</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                        data-field="land" data-value="<?php echo htmlspecialchars($land); ?>"
                        class="edit-button">
                    <span id="land"><?php echo htmlspecialchars($land); ?></span>
                </p>
            </div>

            <div class="column">
                <h3>Contactpersoon</h3>
                <h3>Voornaam</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                        data-field="contact_voornaam"
                        data-value="<?php echo htmlspecialchars($contact_voornaam); ?>" class="edit-button">
                    <span id="contact_voornaam"><?php echo htmlspecialchars($contact_voornaam); ?></span>
                </p>
                <h3>Achternaam</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                        data-field="contact_achternaam"
                        data-value="<?php echo htmlspecialchars($contact_achternaam); ?>" class="edit-button">
                    <span id="contact_achternaam"><?php echo htmlspecialchars($contact_achternaam); ?></span>
                </p>
                <h3>Email</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                        data-field="contact_email"
                        data-value="<?php echo htmlspecialchars($contact_email); ?>" class="edit-button">
                    <span id="contact_email"><?php echo htmlspecialchars($contact_email); ?></span>
                </p>
                <h3>Telefoon</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                        data-field="contact_telefoon"
                        data-value="<?php echo htmlspecialchars($contact_telefoon); ?>" class="edit-button">
                    <span id="contact_telefoon"><?php echo htmlspecialchars($contact_telefoon); ?></span>
                </p>
            </div>
        </div>
    </div>

    <div id="klantContainer" class="container-section" style="display: none;">
    <div class="columns">
        <div class="column">
            <h3>Klantgegevens</h3>

            <div>
                <h3>Voornaam</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                         data-field="klant_voornaam" data-value="<?php echo htmlspecialchars($klant_voornaam); ?>" class="edit-button">
                    <span id="klant_voornaam"><?php echo htmlspecialchars($klant_voornaam); ?></span>
                </p>
            </div>

            <div>
                <h3>Achternaam</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                         data-field="klant_achternaam" data-value="<?php echo htmlspecialchars($klant_achternaam); ?>"
                         class="edit-button">
                    <span id="klant_achternaam"><?php echo htmlspecialchars($klant_achternaam); ?></span>
                </p>
            </div>

            <div>
                <h3>Email</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                         data-field="klant_email" data-value="<?php echo htmlspecialchars($klant_email); ?>"
                         class="edit-button">
                    <span id="klant_email"><?php echo htmlspecialchars($klant_email); ?></span>
                </p>
            </div>

            <div>
                <h3>Telefoon</h3>
                <p>
                    <img src="img/pen-svgrepo-com.svg" alt="edit" width="16" height="16"
                         data-field="klant_telefoon" data-value="<?php echo htmlspecialchars($klant_telefoon); ?>"
                         class="edit-button">
                    <span id="klant_telefoon"><?php echo htmlspecialchars($klant_telefoon); ?></span>
                </p>
            </div>
        </div>
    </div>
</div>
</div>
</div>
</body>
</html>