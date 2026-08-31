<?php

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(getCalendarEvents($pdo), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $input = file_get_contents('php://input');
    $payload = $input !== '' ? json_decode($input, true) : $_POST;

    if (!is_array($payload)) {
        throw new InvalidArgumentException('ข้อมูลส่งเข้ามาไม่ถูกต้อง');
    }

    $result = createBooking($pdo, $payload);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'บันทึกการจองเรียบร้อยแล้ว',
        'booking' => $result,
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
