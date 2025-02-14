<?php
session_start();
require('../fpdf/fpdf.php');
include "../db/conn.php";

// --- Process approval button POST request ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['approve'])) {
    $user_id = $_POST['user_id'];
    $filter  = $_POST['filter'];

    if ($filter === 'vandaag') {
        // Approve only today's record
        $stmt = $pdo->prepare("UPDATE hours 
            SET accord = 'Approved' 
            WHERE user_id = :user_id 
              AND accord = 'pending'
              AND DATE(date) = CURDATE()");
        $stmt->execute([':user_id' => $user_id]);
        $_SESSION['message'] = "Uren van vandaag zijn geapproved";
    } elseif ($filter === 'week') {
        // Get the current week/year from hidden fields or fallback to current
        $year = isset($_POST['year']) ? (int)$_POST['year'] : (int) date("Y");
        $week = isset($_POST['week']) ? (int)$_POST['week'] : (int) date("W");
        // Calculate start and end date for the ISO week.
        $dto = new DateTime();
        $dto->setISODate($year, $week);
        $start_date = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $end_date = $dto->format('Y-m-d');

        $stmt = $pdo->prepare("UPDATE hours 
            SET accord = 'Approved' 
            WHERE user_id = :user_id 
              AND accord = 'pending'
              AND date BETWEEN :start_date AND :end_date");
        $stmt->execute([
            ':user_id'    => $user_id,
            ':start_date' => $start_date,
            ':end_date'   => $end_date
        ]);
        $_SESSION['message'] = "Uren van deze week (week $week) zijn geapproved";
    } elseif ($filter === 'maand') {
        // Get the month and year from hidden fields or fallback to current
        $year  = isset($_POST['year']) ? (int)$_POST['year'] : (int) date("Y");
        $month = isset($_POST['month']) ? (int)$_POST['month'] : (int) date("n");

        $stmt = $pdo->prepare("UPDATE hours 
            SET accord = 'Approved' 
            WHERE user_id = :user_id 
              AND accord = 'pending'
              AND MONTH(date) = :month 
              AND YEAR(date) = :year");
        $stmt->execute([
            ':user_id' => $user_id,
            ':month'   => $month,
            ':year'    => $year
        ]);
        $monthName = date('F', mktime(0, 0, 0, $month, 10));
        $_SESSION['message'] = "Uren van deze maand ($monthName) zijn geapproved";
    }

    // Redirect to avoid form resubmission.
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// --- Process hours update request (inline editing) ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['hours'])) {
    // Retrieve posted values
    $user_id = $_POST['user_id'];
    $filter  = $_POST['filter'];
    $hours   = (float) $_POST['hours'];

    // Determine the date based on the filter
    if ($filter === "vandaag") {
        $date = date("Y-m-d");
    } elseif ($filter === "week") {
        // Expect a day code, e.g. "Ma", "Di", etc.
        $day = $_POST['day'];
        // Get year and week from GET (or use current values)
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date("Y");
        $week = isset($_GET['week']) ? (int) $_GET['week'] : (int) date("W");
        // Mapping: Monday = 1, Tuesday = 2, etc.
        $dayNumbers = ["Ma" => 1, "Di" => 2, "Wo" => 3, "Do" => 4, "Vr" => 5];
        $weekday = isset($dayNumbers[$day]) ? $dayNumbers[$day] : 1;
        $dateObj = new DateTime();
        $dateObj->setISODate($year, $week, $weekday);
        $date = $dateObj->format("Y-m-d");
    } else {
        // For other filters (like maand) you can adjust as needed.
        $date = date("Y-m-d");
    }

    // Update the hours record. First, try an update if no row was found insert.
    $stmt = $pdo->prepare("
    INSERT INTO hours (user_id, date, hours) 
    VALUES (:user_id, :date, :hours) 
    ON DUPLICATE KEY UPDATE hours = :hours
");
    $stmt->execute([
        ':user_id' => $user_id,
        ':date'    => $date,
        ':hours'   => $hours
    ]);


    // Redirect to avoid form resubmission.
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../inloggen.php");
    exit();
}

$user_id   = htmlspecialchars($_SESSION['user_id'], ENT_QUOTES, 'UTF-8');
$user_name = htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8');

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'week';
$month  = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year   = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$week   = isset($_GET['week']) ? (int)$_GET['week'] : date('W');

if ($filter === 'maand') {
    $sql = "
        SELECT u.user_id, u.name, COALESCE(SUM(h.hours), 0) AS totaal
        FROM users u
        LEFT JOIN hours h ON u.user_id = h.user_id AND MONTH(h.date) = :month AND YEAR(h.date) = :year
        WHERE u.role = 'user'
        GROUP BY u.user_id, u.name
        ORDER BY u.name ASC
    ";
    $params = [':month' => $month, ':year' => $year];
} elseif ($filter === 'vandaag') {
    $sql = "
        SELECT u.user_id, u.name, COALESCE(SUM(h.hours), 0) AS totaal
        FROM users u
        LEFT JOIN hours h ON u.user_id = h.user_id AND DATE(h.date) = CURDATE()
        WHERE u.role = 'user'
        GROUP BY u.user_id, u.name
        ORDER BY u.name ASC
    ";
    $params = [];
} else {
    // Week filter
    $sql = "
        SELECT u.user_id, u.name,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 2 THEN h.hours ELSE 0 END), 0) AS Ma,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 3 THEN h.hours ELSE 0 END), 0) AS Di,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 4 THEN h.hours ELSE 0 END), 0) AS Wo,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 5 THEN h.hours ELSE 0 END), 0) AS Do,
            COALESCE(SUM(CASE WHEN DAYOFWEEK(h.date) = 6 THEN h.hours ELSE 0 END), 0) AS Vr
        FROM users u
        LEFT JOIN hours h ON u.user_id = h.user_id 
          AND YEARWEEK(h.date, 1) = YEARWEEK(STR_TO_DATE(CONCAT(:year, '-', :week, ' Monday'), '%x-%v %W'), 1)
        WHERE u.role = 'user'
        GROUP BY u.user_id, u.name
        ORDER BY u.name ASC
    ";
    $params = [':year' => $year, ':week' => $week];
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error retrieving data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/admin-index.css">
</head>
<body>
<div class="container">

    <?php if(isset($_SESSION['message'])): ?>
        <div class="accorderen-berichten">
            <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php include 'admin-header.php'; ?>

    <div class="content">
        <div class="dateNfilter-header <?= 'filter-' . $filter ?>">
            <?php if ($filter === 'week'): ?>
                <div class="name">Week activiteiten</div>
                <div class="huidige-week-weergave">Week <?= $week ?></div>
            <?php elseif ($filter === 'maand'): ?>
                <div class="name">Maand activiteiten</div>
                <div class="admin-maand-navigatie">
                    <div class="month-th"></div>
                    <div class="month-th">
                        <a class="arrow-left-month" href="?filter=maand&month=<?= $month-1 ?>">
                            <img src="../img/links-pijl.png" alt="links" class="linkere-pijl-maanden">
                        </a>
                        <div class="maand-text">
                            <?= date('F', mktime(0, 0, 0, $month, 10)) ?>
                        </div>
                        <a class="arrow-right-month" href="?filter=maand&month=<?= $month+1 ?>">
                            <img src="../img/rechts-pijl.png" alt="rechts" class="rechtere-pijl-maanden">
                        </a>
                    </div>
                    <div class="month-th"></div>
                </div>
            <?php else: ?>
                <div class="name">Vandaag activiteiten</div>
            <?php endif; ?>
            <form method="GET" action="" class="filter-form">
                <select name="filter" id="filter" onchange="this.form.submit()">
                    <option value="vandaag" <?= $filter === 'vandaag' ? 'selected' : '' ?>>Vandaag</option>
                    <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Week</option>
                    <option value="maand" <?= $filter === 'maand' ? 'selected' : '' ?>>Maand</option>
                </select>
            </form>
        </div>

        <?php if ($filter === 'week'): ?>
            <div class="week-tr">
                <?php
                $prev_week = $week - 1;
                $next_week = $week + 1;
                $prev_year = $year;
                $next_year = $year;
                if ($prev_week < 1) { $prev_week = 52; $prev_year--; }
                if ($next_week > 52) { $next_week = 1; $next_year++; }
                ?>
                <div class="linkere-pijl-th">
                    <a href="?filter=week&year=<?= $prev_year ?>&week=<?= $prev_week ?>">
                        <img src="../img/links-pijl.png">
                    </a>
                </div>
                <div class="week-th">Ma</div>
                <div class="week-th">Di</div>
                <div class="week-th">Wo</div>
                <div class="week-th">Do</div>
                <div class="week-th">Vr</div>
                <div class="rechtere-pijl-th">
                    <a href="?filter=week&year=<?= $next_year ?>&week=<?= $next_week ?>">
                        <img src="../img/rechts-pijl.png">
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <table class="tabel-content" data-filter="<?= $filter ?>">
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr data-user-id="<?= htmlspecialchars($row["user_id"]) ?>">
                    <td class="naamNuser-icon">
                        <img src="../img/user-icon.png" alt="icon" class="user-icon">
                        <?= htmlspecialchars($row["name"]) ?>
                    </td>
                    <?php if ($filter === 'week'):
                        $total = $row["Ma"] + $row["Di"] + $row["Wo"] + $row["Do"] + $row["Vr"];
                        ?>
                        <td class="uren-row editable"><?= htmlspecialchars($row["Ma"]) ?></td>
                        <td class="uren-row editable"><?= htmlspecialchars($row["Di"]) ?></td>
                        <td class="uren-row editable"><?= htmlspecialchars($row["Wo"]) ?></td>
                        <td class="uren-row editable"><?= htmlspecialchars($row["Do"]) ?></td>
                        <td class="uren-row editable"><?= htmlspecialchars($row["Vr"]) ?></td>
                        <td class="totaal-week-end"><strong><?= htmlspecialchars($total) ?> Totaal</strong></td>
                        <td class="action-icons">
                            <button class="edit-btn" title="Wijzigen">✏️</button>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="approve" value="1">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($row["user_id"]) ?>">
                                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
                                <input type="hidden" name="week" value="<?= htmlspecialchars($week) ?>">
                                <button type="submit">
                                    <img class="action-pngs" src="../img/checkmark.png" title="Accorderen">
                                </button>
                            </form>
                        </td>
                    <?php else: ?>
                        <td class="editable"><?= htmlspecialchars($row["totaal"]) ?> Totaal</td>
                        <td class="action-icons">
                            <?php if ($filter !== 'maand'): ?>
                                <button class="edit-btn" title="Wijzigen">✏️</button>
                            <?php endif; ?>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="approve" value="1">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($row["user_id"]) ?>">
                                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                <?php if ($filter === 'maand'): ?>
                                    <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
                                    <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
                                <?php endif; ?>
                                <button type="submit">
                                    <img class="action-pngs" src="../img/checkmark.png" title="Accorderen">
                                </button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Hidden form to submit inline hour changes (updates are processed in this same file) -->
<form id="hiddenUpdateForm" method="POST" action="" style="display:none;">
    <input type="hidden" name="user_id" value="">
    <input type="hidden" name="filter" value="">
    <input type="hidden" name="day" value="">
    <input type="hidden" name="hours" value="">
</form>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var filter = "<?= $filter ?>";

        // 1. Attach click listeners to "Wijzigen" buttons to mark their row as active.
        document.querySelectorAll(".edit-btn").forEach(function(button) {
            button.addEventListener("click", function(e) {
                e.preventDefault();
                // Remove "active-edit" from any row.
                document.querySelectorAll("tr.active-edit").forEach(function(row) {
                    row.classList.remove("active-edit");
                });
                // Mark the row containing this button as active.
                var row = button.closest("tr");
                row.classList.add("active-edit");
            });
        });

        // 2. Attach inline editing to hour cells—but only if their row is active.
        var editableCells = [];
        if (filter === "week") {
            editableCells = document.querySelectorAll("td.uren-row.editable");
        } else if (filter === "vandaag") {
            editableCells = document.querySelectorAll('table[data-filter="vandaag"] tbody tr td.editable');
        }
        // For week view, map cell index (relative to row) to day code.
        // Cell 0 is name; 1: Ma, 2: Di, 3: Wo, 4: Do, 5: Vr.
        var dayMapping = {1: "Ma", 2: "Di", 3: "Wo", 4: "Do", 5: "Vr"};

        editableCells.forEach(function(cell) {
            cell.style.cursor = "pointer";
            cell.addEventListener("click", function(e) {
                // Allow editing only if this cell's row is active.
                if (!cell.parentNode.classList.contains("active-edit")) return;
                // Prevent multiple inputs in one cell.
                if (cell.querySelector("input")) return;

                var originalValue = cell.textContent.replace(" Totaal", "").trim();
                cell.innerHTML = "";
                var input = document.createElement("input");
                input.type = "number";
                input.min = 0;
                input.max = 24;
                input.value = originalValue;
                input.className = "inline-edit";
                cell.appendChild(input);
                input.focus();

                // Flag to track if Enter was pressed (updates confirmed)
                let updateConfirmed = false;

                input.addEventListener("keydown", function(ev) {
                    if (ev.key === "Enter") {
                        ev.preventDefault();
                        updateConfirmed = true;
                        updateValue(cell, input.value);
                        input.blur();
                    }
                });

                input.addEventListener("blur", function() {
                    // If Enter was not pressed, revert to original value.
                    if (!updateConfirmed) {
                        cell.textContent = originalValue;
                    }
                    // Remove active-edit class from the row.
                    cell.parentNode.classList.remove("active-edit");
                });
            });
        });

        function updateValue(cell, newValue) {
            var row = cell.parentNode;
            var userId = row.getAttribute("data-user-id");
            var day = "";

            // If filter is "week", determine the day from the row
            if (filter === "week") {
                var cells = Array.from(row.children);
                var cellIndex = cells.indexOf(cell);
                day = dayMapping[cellIndex] || "";
            }

            // Immediately update the cell with the new value entered by the user
            cell.textContent = newValue;

            // Submit the hidden form to update the database
            var form = document.getElementById("hiddenUpdateForm");
            form.user_id.value = userId;
            form.filter.value = filter;
            form.day.value = day;
            form.hours.value = newValue; // The new value entered by the user
            form.submit();
        }
    });
</script>
<script src="../js/admin-berichten.js">
</script>

</body>
</html>
