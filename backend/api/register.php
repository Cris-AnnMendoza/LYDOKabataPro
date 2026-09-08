<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    // Also accept multipart/form-data (file uploads)
    $data = $_POST;
}

// ── Required field validation ──────────────────────────────
$required = ['first_name', 'last_name', 'gender', 'birthdate', 'age',
             'civil_status', 'contact_number', 'email', 'password',
             'barangay', 'youth_classification'];

foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Field '$field' is required."]);
        exit;
    }
}

// ── Sanitize ───────────────────────────────────────────────
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

$password = $data['password'];
if (strlen($password) < 8) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}

$pdo = getDB();

// ── Check duplicate email ──────────────────────────────────
$stmt = $pdo->prepare('SELECT id FROM youth_users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email is already registered. Please login.']);
    exit;
}

// ── Hash password ──────────────────────────────────────────
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// ── Handle file uploads ────────────────────────────────────
$uploadDir   = __DIR__ . '/../../uploads/';
$validId     = null;
$profilePic  = null;

if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

function saveUpload(string $key, string $dir): ?string {
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) return null;
    $ext      = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($ext, $allowed, true)) return null;
    if ($_FILES[$key]['size'] > 5 * 1024 * 1024) return null; // 5 MB max
    $filename = uniqid($key . '_', true) . '.' . $ext;
    move_uploaded_file($_FILES[$key]['tmp_name'], $dir . $filename);
    return $filename;
}

$validId    = saveUpload('valid_id',        $uploadDir);
$profilePic = saveUpload('profile_picture', $uploadDir);

// ── Classification / programs arrays → JSON ────────────────
$classification = is_array($data['youth_classification'])
    ? json_encode($data['youth_classification'])
    : $data['youth_classification'];

$programs = isset($data['programs_interested'])
    ? (is_array($data['programs_interested'])
        ? json_encode($data['programs_interested'])
        : $data['programs_interested'])
    : null;

// ── Insert ─────────────────────────────────────────────────
$sql = 'INSERT INTO youth_users (
    first_name, middle_name, last_name, suffix, gender, birthdate, age,
    civil_status, contact_number, email, password,
    house_number, street, barangay, municipality, province, zip_code,
    youth_classification, educational_status, school_name, course_or_grade,
    employment_status, organization_name, organization_type, organization_role,
    years_membership, skills, interests, programs_interested,
    volunteer_availability, valid_id, profile_picture
) VALUES (
    :first_name, :middle_name, :last_name, :suffix, :gender, :birthdate, :age,
    :civil_status, :contact_number, :email, :password,
    :house_number, :street, :barangay, :municipality, :province, :zip_code,
    :youth_classification, :educational_status, :school_name, :course_or_grade,
    :employment_status, :organization_name, :organization_type, :organization_role,
    :years_membership, :skills, :interests, :programs_interested,
    :volunteer_availability, :valid_id, :profile_picture
)';

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':first_name'           => trim($data['first_name']),
    ':middle_name'          => trim($data['middle_name'] ?? ''),
    ':last_name'            => trim($data['last_name']),
    ':suffix'               => trim($data['suffix'] ?? ''),
    ':gender'               => $data['gender'],
    ':birthdate'            => $data['birthdate'],
    ':age'                  => (int)$data['age'],
    ':civil_status'         => $data['civil_status'],
    ':contact_number'       => trim($data['contact_number']),
    ':email'                => $email,
    ':password'             => $hashedPassword,
    ':house_number'         => trim($data['house_number'] ?? ''),
    ':street'               => trim($data['street'] ?? ''),
    ':barangay'             => $data['barangay'],
    ':municipality'         => $data['municipality'] ?? 'Sta. Cruz',
    ':province'             => $data['province']     ?? 'Laguna',
    ':zip_code'             => $data['zip_code']     ?? '4009',
    ':youth_classification' => $classification,
    ':educational_status'   => $data['educational_status']   ?? null,
    ':school_name'          => $data['school_name']          ?? null,
    ':course_or_grade'      => $data['course_or_grade']      ?? null,
    ':employment_status'    => $data['employment_status']    ?? null,
    ':organization_name'    => $data['organization_name']    ?? null,
    ':organization_type'    => $data['organization_type']    ?? null,
    ':organization_role'    => $data['organization_role']    ?? null,
    ':years_membership'     => (int)($data['years_membership'] ?? 0),
    ':skills'               => $data['skills']               ?? null,
    ':interests'            => $data['interests']            ?? null,
    ':programs_interested'  => $programs,
    ':volunteer_availability' => $data['volunteer_availability'] ?? null,
    ':valid_id'             => $validId,
    ':profile_picture'      => $profilePic,
]);

$userId = $pdo->lastInsertId();

// ── Auto-login after registration ──────────────────────────
$_SESSION['user_id']   = $userId;
$_SESSION['user_email'] = $email;
$_SESSION['user_name'] = trim($data['first_name']) . ' ' . trim($data['last_name']);

http_response_code(201);
echo json_encode([
    'success' => true,
    'message' => 'Registration successful! Welcome to LYDO.',
    'user'    => [
        'id'    => $userId,
        'name'  => $_SESSION['user_name'],
        'email' => $email,
    ],
]);
