<?php
/**
 * GET /api/youth/profile - Get user profile
 * PUT /api/youth/profile - Update user profile
 * Headers: Authorization: Bearer {token}
 */

require_once __DIR__ . '/bootstrap.php';

$user = requireAuth();
$pdo = db();

// GET - Return profile
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get fresh user data
    $stmt = $pdo->prepare('SELECT * FROM youth_users WHERE id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();
    
    unset($userData['password']);
    
    jsonResponse(['success' => true, 'user' => $userData]);
}

// PUT - Update profile
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $body = getJsonBody();
    
    // Allowed fields to update
    $allowedFields = [
        'first_name', 'middle_name', 'last_name', 'suffix',
        'contact_number', 'address', 'birth_date', 'age', 'sex',
        'civil_status', 'educational_status', 'employment_status',
        'organization_name', 'youth_classification', 'programs_interested'
    ];
    
    $updates = [];
    $params = [];
    
    foreach ($allowedFields as $field) {
        if (isset($body[$field])) {
            $updates[] = "$field = ?";
            $params[] = is_array($body[$field]) ? json_encode($body[$field]) : $body[$field];
        }
    }
    
    if (empty($updates)) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }
    
    $params[] = $user['id'];
    $sql = 'UPDATE youth_users SET ' . implode(', ', $updates) . ' WHERE id = ?';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Get updated user
    $stmt = $pdo->prepare('SELECT * FROM youth_users WHERE id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();
    unset($userData['password']);
    
    jsonResponse(['success' => true, 'user' => $userData]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
