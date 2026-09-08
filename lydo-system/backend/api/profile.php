<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT id, first_name, middle_name, last_name, suffix, gender,
    birthdate, age, civil_status, contact_number, email,
    house_number, street, barangay, municipality, province, zip_code,
    youth_classification, educational_status, school_name, course_or_grade,
    employment_status, organization_name, organization_type, organization_role,
    years_membership, skills, interests, programs_interested,
    volunteer_availability, profile_picture, created_at
    FROM youth_users WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

// Decode JSON fields
foreach (['youth_classification', 'programs_interested'] as $field) {
    if ($user[$field]) {
        $decoded = json_decode($user[$field], true);
        $user[$field] = $decoded ?? $user[$field];
    }
}

echo json_encode(['success' => true, 'user' => $user]);
