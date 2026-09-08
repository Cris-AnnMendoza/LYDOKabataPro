<?php
/**
 * Submission Hash Chain — tamper-evident audit trail
 * Each record links to the previous hash, forming a chain.
 * Any modification to a past record breaks the chain.
 */

function blockchain_record(PDO $pdo, string $type, int $recordId, int $userId, string $action, array $data = []): string {
    // Get last hash in chain
    $last = $pdo->query('SELECT chain_hash FROM submission_chain ORDER BY id DESC LIMIT 1')->fetchColumn();
    $prevHash = $last ?: str_repeat('0', 64);

    // Hash the current data
    $dataStr  = json_encode(array_merge(['type'=>$type,'record_id'=>$recordId,'action'=>$action,'ts'=>time()], $data));
    $dataHash = hash('sha256', $dataStr);

    // Chain hash = hash of (data + prev)
    $chainHash = hash('sha256', $dataHash . $prevHash);

    $pdo->prepare('INSERT INTO submission_chain (record_type,record_id,user_id,action,data_hash,prev_hash,chain_hash,metadata) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$type, $recordId, $userId, $action, $dataHash, $prevHash, $chainHash, json_encode($data)]);

    return $chainHash;
}

function blockchain_verify(PDO $pdo): array {
    $records = $pdo->query('SELECT * FROM submission_chain ORDER BY id ASC')->fetchAll();
    $prevHash = str_repeat('0', 64);
    $broken = [];

    foreach ($records as $r) {
        $expectedChain = hash('sha256', $r['data_hash'] . $prevHash);
        if ($expectedChain !== $r['chain_hash'] || $r['prev_hash'] !== $prevHash) {
            $broken[] = $r['id'];
        }
        $prevHash = $r['chain_hash'];
    }

    return ['valid' => empty($broken), 'broken_ids' => $broken, 'total' => count($records)];
}

/**
 * Hash a file and record it in the submission chain.
 * Returns the SHA-256 hash of the file content.
 */
function blockchain_hash_file(PDO $pdo, string $filePath, string $docType, string $recordType, int $recordId, int $userId): string {
    if (!file_exists($filePath)) return '';
    $hash = hash_file('sha256', $filePath);
    blockchain_record($pdo, $recordType, $recordId, $userId, 'document_uploaded', [
        'doc_type'  => $docType,
        'file_hash' => $hash,
        'file_path' => basename($filePath),
    ]);
    return $hash;
}

/**
 * Verify a stored file against its recorded hash.
 * Returns true if file is intact, false if tampered or missing.
 */
function blockchain_verify_file(string $filePath, string $storedHash): array {
    if (!file_exists($filePath)) {
        return ['valid' => false, 'reason' => 'File not found'];
    }
    $currentHash = hash_file('sha256', $filePath);
    if ($currentHash !== $storedHash) {
        return ['valid' => false, 'reason' => 'Hash mismatch — file may have been tampered with'];
    }
    return ['valid' => true, 'reason' => 'File integrity verified'];
}
