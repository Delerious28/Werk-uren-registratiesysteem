<?php
session_start();
include "db/conn.php"; // Database Connectie.

// Check als de gebruiker ingelogd is:
if (!isset($_SESSION['user_id'])) {
    echo "<p>Er is een probleem met uw inloggegevens. Log opnieuw in.</p>";
    header("Location: inloggen.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Haal de gebruiker's id op uit de sessie.
$user_name = $_SESSION['user']; // Haal de gebruiker's naam op uit de sessie.

// Initialiseer de succesberichtvariabele
$success_message = '';
$fail_message = '';

// Formulierinzending afhandelen
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (!empty($_POST['hours']) && !empty($_POST['date'])) {
        $hours = $_POST['hours'];
        $date = $_POST['date'];

        if ($user_id) {
            $hours = intval($hours);

            // Controleer of er al uren zijn ingevoerd voor deze dag
            $stmt = $pdo->prepare("SELECT * FROM hours WHERE user_id = ? AND date = ?");
            $stmt->execute([$user_id, $date]);
            $existingEntry = $stmt->fetch();

            if ($existingEntry) {
                $fail_message = "U heeft de uren voor $date al ingevoerd!";
                header('Refresh: 3');
            } else {
                // Gegevens invoeren in de database als er nog geen record bestaat
                $stmt = $pdo->prepare("INSERT INTO hours (user_id, date, hours) VALUES (?, ?, ?)");
                if ($stmt->execute([$user_id, $date, $hours])) {
                    $success_message = "Uren succesvol ingevoerd voor $date.";
                    header('Refresh: 3');
                } else {
                    echo "<p>Er is een fout opgetreden bij het invoeren van de uren.</p>";
                }
            }
        }
    } else {
        echo "<p>Vul alle velden in!</p>";
    }
}

// Verkrijg de huidige week
$current_date = new DateTime();
$current_week_start = (clone $current_date)->modify('Monday this week');

// Verkrijg de vorige week
$previous_week_start = (clone $current_week_start)->modify('-1 week');

// Verkrijg de volgende week (maar zet deze vast op de huidige week)
$next_week_start = (clone $current_week_start)->modify('+1 week');

// Verkrijg de laatste geselecteerde week (initieel op huidige week)
$selected_week_start = $current_week_start;

// Datum van de week, maand en jaar voor "div class="datum" verkrijgen.
$weekNum = $selected_week_start->format("W"); // Weeknummer
$month = $selected_week_start->format("F");   // Maand (volledig)
$year = $selected_week_start->format("Y");    // Jaar


// Verkrijg alle ingevoerde uren van de gebruiker voor de geselecteerde week
$stmt = $pdo->prepare("SELECT date, hours FROM hours WHERE user_id = ? AND date BETWEEN ? AND ?");
$week_start = $selected_week_start->format('Y-m-d');
$week_end = (clone $selected_week_start)->modify('+4 days')->format('Y-m-d');
$stmt->execute([$user_id, $week_start, $week_end]);
$hours_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Maak een associatieve array met datum => uren
$hours_map = [];
foreach ($hours_data as $row) {
    $hours_map[$row['date']] = $row['hours'];
}

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
</head>
<body>

<?php include "header.php"; ?>

<main>
    <div class="content-container">
        <div class="datum">
            <span id="week-text"></span>
            <form action="uitloggen.php" method="POST">
                <button type="submit" class="uitlog-btn">Uitloggen</button>
            </form>
        </div>

        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($fail_message): ?>
            <div class="fail-message">
                <?php echo htmlspecialchars($fail_message); ?>
            </div>
        <?php endif; ?>

        <!-- Week navigatie knoppen -->
        <div class="week-container">
            <button id="previous-week" class="nav-week-btn">
                <img src="img/links-pijl.png" alt="pijl" class="nav-pijl">
            </button>

            <?php
            $weekdagen = ["Maandag", "Dinsdag", "Woensdag", "Donderdag", "Vrijdag"];
            foreach ($weekdagen as $dag) {
                echo "<div><button class='dag'>$dag</button></div>";
            }
            ?>

            <button id="next-week" class="nav-week-btn">
                <img src="img/rechts-pijl.png" alt="pijl" class="nav-pijl">
            </button>
        </div>

        <div class="date-ctn">
            <form id="day-form" action="index.php" method="POST">
                <div class="uren-form">
                    <div id="selected-day"></div>
<!--                    <div class="input-icon-div">-->
<!--                    <input type="number" name="hours" min="0" max="24" required placeholder="Uren">-->
<!--                        <img src="img/uren-icon.png" alt="uren icon" class="uren-icon">-->
<!--                    </div>-->
                    <input type="hidden" name="date" id="date-input">
                    <button type="submit" id="indien-btn" class="indienen-btn">Indienen</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script src="js/main.js"></script>

<script>
    let selectedWeekStartDate = new Date('<?php echo $selected_week_start->format('Y-m-d'); ?>');

    let hoursData = <?php echo json_encode($hours_map); ?>;

</script>

</body>
</html>
