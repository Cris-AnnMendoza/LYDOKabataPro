<?php
require_once __DIR__ . '/../shared/config.php';
header('Content-Type: application/json');

try {
    $pdo = db();

    $youth = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    try {
        $programs = (int)$pdo->query("SELECT COUNT(*) FROM volunteer_programs")->fetchColumn();
    } catch (Exception $e) { $programs = 0; }

    try {
        $events = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    } catch (Exception $e) { $events = 0; }

    try {
        $accredited = (int)$pdo->query("SELECT COUNT(*) FROM organizations WHERE accreditation_status = 'accredited'")->fetchColumn();
    } catch (Exception $e) { $accredited = 0; }

    echo json_encode(compact('youth', 'programs', 'events', 'accredited'));
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'youth' => 0, 'programs' => 0, 'events' => 0, 'accredited' => 0]);
}
