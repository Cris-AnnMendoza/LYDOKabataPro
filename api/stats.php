<?php
require_once __DIR__ . '/../shared/config.php';
header('Content-Type: application/json');

try {
    $pdo = db();

    $youth = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $programs = $pdo->query("SELECT COUNT(*) FROM volunteer_programs WHERE is_active = 1")->fetchColumn();
    $events = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $accredited = $pdo->query("SELECT COUNT(*) FROM organizations WHERE accreditation_status = 'accredited'")->fetchColumn();

    echo json_encode([
        'youth' => (int)$youth,
        'programs' => (int)$programs,
        'events' => (int)$events,
        'accredited' => (int)$accredited,
    ]);
} catch (Exception $e) {
    echo json_encode(['youth' => 0, 'programs' => 0, 'events' => 0, 'accredited' => 0]);
}
