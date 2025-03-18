<?php
session_start();
include "db/conn.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Niet ingelogd"]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Haal alle projecten van de gebruiker op met gewerkte uren
$query = "
    SELECT 
        p.project_id, 
        p.project_naam, 
        p.contract_uren, 
        k.bedrijfnaam, 
        (SELECT COALESCE(SUM(h.hours), 0) FROM hours h WHERE h.user_id = :user_id AND h.project_id = p.project_id) AS total_hours
    FROM project_users pu
    JOIN project p ON pu.project_id = p.project_id
    JOIN klant k ON p.klant_id = k.klant_id
    WHERE pu.user_id = :user_id
";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Voeg voortgangspercentage toe
foreach ($projects as &$project) {
    $contractUren = $project['contract_uren'] ?? 0;
    $gewerkteUren = $project['total_hours'] ?? 0;  // Gebruik de alias 'total_hours' hier

    // Debug-log om waarden te controleren
    error_log("Project: {$project['project_naam']} | Contract uren: $contractUren | Gewerkte uren: $gewerkteUren");

    // Voorkom deling door nul
    if ($contractUren > 0) {
        $percentage = round(($gewerkteUren / $contractUren) * 100);
        $project['progressPercentage'] = min($percentage, 100) . "%";
        $project['remainingHours'] = $contractUren - $gewerkteUren;
    } else {
        $project['progressPercentage'] = "0%";
        $project['remainingHours'] = $contractUren;
    }
}

echo json_encode(["status" => "success", "projects" => $projects]);
?>
