<?php
session_start();

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header("Location: inloggen.php");
    exit();
}

require_once 'db/conn.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? '';

if (empty($user_id)) {
    echo json_encode(['error' => 'Geen gebruikers-ID opgegeven']);
    exit();
}

try {
    // Haal de gegevens van de gebruiker op, inclusief enkele uren-gegevens
    $stmt = $pdo->prepare("SELECT u.user_id, u.name, u.achternaam, k.bedrijfnaam, 
                                  p.project_naam AS projectnaam, h.hours AS uren, 
                                  h.start_hours, h.eind_hours
                           FROM users u
                           JOIN hours h ON u.user_id = h.user_id
                           LEFT JOIN project p ON h.project_id = p.project_id
                           LEFT JOIN klant k ON p.klant_id = k.klant_id
                           WHERE u.user_id = :user_id
                           LIMIT 1");
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
