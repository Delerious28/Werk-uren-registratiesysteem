<?php
session_start();

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header("Location: inloggen.php");
    exit();
}

require_once 'db/conn.php';

// Verkrijg de data (bijvoorbeeld via een JSON-request)
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? '';

// Controleren of een user_id is opgegeven
if (empty($user_id)) {
    echo json_encode(['error' => 'Geen gebruikers-ID opgegeven']);
    exit();
}

try {
    // Haal alle kolommen op uit de users-tabel voor de opgegeven user_id
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $user_id]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        echo json_encode($userData);
    } else {
        echo json_encode(['error' => 'Gebruiker niet gevonden']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Fout bij het ophalen van gegevens: ' . $e->getMessage()]);
}
?>
