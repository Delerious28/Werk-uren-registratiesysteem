<?php
session_start();
include "db/conn.php"; // Include your database connection

// Check if the user is logged in
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: inloggen.php");  // Redirect to login page if not logged in
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    // Check if the required fields are present
    if (!empty($_POST['hours']) && !empty($_POST['date'])) {
        $hours = $_POST['hours'];
        $date = $_POST['date'];
        $user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

        // Insert the data into the database
        $stmt = $conn->prepare("INSERT INTO work_hours (user_id, date, hours) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $date, $hours);

        if ($stmt->execute()) {
            echo "<p>Uren succesvol ingevoerd voor $date.</p>";
        } else {
            echo "<p>Er is een fout opgetreden bij het invoeren van de uren.</p>";
        }

        // Redirect back to the homepage after successful submission
        header("Location: index.php");
        exit();
    } else {
        echo "<p>Vul alle velden in!</p>";
    }
}
?>
