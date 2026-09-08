<?php
/**
 * POST /api/youth/register
 * Register new youth user
 * Body: { "email": "...", "password": "...", "first_name": "...", "last_name": "...", ... }
 * Returns: { "success": true, "token": "...", "user": {...} }
 */

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$body = getJsonBody();

// Required fields
$required = ['email', 'password', 'first_name', 'last_name', 'contact_number', 'address', 
             'birth_date', 'sex', 'civil_status', 'educational_status', 'employment_status'];

foreach ($required as $field) {
    if (empty($body[$field])) {
        jsonResponse(['error' => "Field '$field' is required"], 400);
    }
}

$pdo = db();

// Check if email already exists
$stmt = $pdo->prepare('SELECT id FROM youth_users WHERE email = ? LIMIT 1');
$stmt->execute([$body['email']]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'Email already registered'], 400);
}

// Calculate age from birth_date
$birthDate = $body['birth_date'];
$age = date_diff(date_create($birthDate), date_create('today'))->y;

// Hash password
$passwordHash = password_hash($body['password'], PASSWORD_BCRYPT);

// Insert user
$stmt = $pdo->prepare('
    INSERT INTO youth_users (
        email, password, first_name, middle_name, last_name, suffix,
        contact_number, address, birth_date, age, sex, civil_status,
        educational_status, employment_status, organization_name,
        youth_classification, programs_interested, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
');

$stmt->execute([
    $body['email'],
    $passwordHash,
    $body['first_name'],
    $body['middle_name'] ?? '',
    $body['last_name'],
    $body['suffix'] ?? '',
    $body['contact_number'],
    $body['address'],
    $birthDate,
    $age,
    $body['sex'],
    $body['civil_status'],
    $body['educational_status'],
    $body['employment_status'],
    $body['organization_name'] ?? '',
    isset($body['youth_classification']) ? json_encode($body['youth_classification']) : '[]',
    isset($body['programs_interested']) ? json_encode($body['programs_interested']) : '[]'
]);

$userId = (int)$pdo->lastInsertId();

// Generate API token
$token = createToken($userId);

// Get user data
$stmt = $pdo->prepare('SELECT * FROM youth_users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
unset($user['password']);

jsonResponse([
    'success' => true,
    'message' => 'Registration successful!',
    'token' => $token,
    'user' => $user
]);
