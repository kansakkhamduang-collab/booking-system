<?php

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDbConnection();
    echo json_encode(getStudents($pdo), JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
