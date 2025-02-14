<?php
session_start();
include "db/conn.php"; // Database Connectie.

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    echo "<p>Er is een probleem met uw inloggegevens. Log opnieuw in.</p>";
    header("Location: inloggen.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user'];

$noInput         = '';
$success_message = '';
$fail_message    = '';

// Verwerk formulier indien ingediend
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (!empty($_POST['hours']) && !empty($_POST['date'])) {
        $hours = $_POST['hours'];
        $date  = $_POST['date'];

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
                // Voeg de nieuwe uren toe
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
        $noInput = "Vul 1 of meer uren in!";
        header('Refresh: 3');
    }
}

// Bepaal de huidige week (start op maandag)
$current_date       = new DateTime();
$current_week_start = (clone $current_date)->modify('Monday this week');

// Voor deze versie gebruiken we de huidige week als standaard geselecteerde week
$selected_week_start = $current_week_start;

$weekNum = $selected_week_start->format("W"); // Weeknummer
$month   = $selected_week_start->format("F"); // Volledige maandnaam
$year    = $selected_week_start->format("Y"); // Jaar

// Haal ALLE ingevoerde uren op voor de gebruiker, zodat deze later ook voor andere weken beschikbaar zijn
$stmt = $pdo->prepare("SELECT date, hours FROM hours WHERE user_id = ?");
$stmt->execute([$user_id]);
$hours_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Maak een associatieve array: datum => ingevoerde uren
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

        <?php if ($noInput): ?>
            <div class="fail-message">
                <?php echo htmlspecialchars($noInput); ?>
            </div>
        <?php endif; ?>

        <!-- Navigatie voor de weken en de dagen -->
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
                    <input type="hidden" name="date" id="date-input">
                    <button type="submit" id="indien-btn" class="indienen-btn">Indienen</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    // Standaard geselecteerde week (PHP)
    let selectedWeekStartDate = new Date('<?php echo $selected_week_start->format('Y-m-d'); ?>');

    // Global hoursData-object met alle ingevoerde uren (datum => uren)
    let hoursData = <?php echo json_encode($hours_map); ?>;
</script>
<script src="js/main.js"></script>
</body>
</html>
