<?php

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $teacherId = isset($_GET['teacher_id']) && $_GET['teacher_id'] !== '' ? (int)$_GET['teacher_id'] : null;

        echo json_encode([
            'teachers' => getTeachers($pdo),
            'schedules' => getTeacherSchedules($pdo, $teacherId),
        ], JSON_UNESCAPED_UNICODE);
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

    $action = isset($payload['action']) ? trim((string)$payload['action']) : 'create_schedule';

    if ($action === 'create_schedule') {
        $result = createTeacherSchedule($pdo, $payload);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'บันทึกตารางงานของอาจารย์เรียบร้อยแล้ว',
            'schedule' => $result,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'update_schedule') {
        $scheduleId = isset($payload['schedule_id']) ? (int)$payload['schedule_id'] : 0;
        $result = updateTeacherSchedule($pdo, $scheduleId, $payload);

        echo json_encode([
            'success' => true,
            'message' => 'แก้ไขตารางงานเรียบร้อยแล้ว',
            'schedule' => $result,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'delete_schedule') {
        $scheduleId = isset($payload['schedule_id']) ? (int)$payload['schedule_id'] : 0;
        deleteTeacherSchedule($pdo, $scheduleId);

        echo json_encode([
            'success' => true,
            'message' => 'ลบรายการตารางงานเรียบร้อยแล้ว',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new InvalidArgumentException('Action ไม่ถูกต้อง');
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
