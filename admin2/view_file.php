<?php
/**
 * Secure file viewer for uploaded assistance documents.
 * Only accessible by logged-in admins.
 */
require_once 'config.php';
requireLogin();

$pdo      = db();
$docId    = (int)($_GET['doc_id'] ?? 0);
$inline   = isset($_GET['inline']); // view inline vs download

if (!$docId) {
    http_response_code(400);
    die('Invalid request.');
}

// Load document record
$stmt = $pdo->prepare('SELECT d.*, r.submitted_by FROM assistance_documents d JOIN assistance_requests r ON r.id = d.request_id WHERE d.id = ? LIMIT 1');
$stmt->execute([$docId]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    die('Document not found.');
}

// Build file path
$filePath = __DIR__ . '/../shared/uploads/assistance/' . $doc['filename'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found on server. It may have been deleted.');
}

// Determine MIME type
$ext  = strtolower(pathinfo($doc['filename'], PATHINFO_EXTENSION));
$mime = match($ext) {
    'pdf'        => 'application/pdf',
    'jpg','jpeg' => 'image/jpeg',
    'png'        => 'image/png',
    'doc'        => 'application/msword',
    'docx'       => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    default      => 'application/octet-stream',
};

// Serve the file
$disposition = $inline ? 'inline' : 'attachment';
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . $doc['original_name'] . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=3600');
readfile($filePath);
exit;
