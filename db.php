<?php

require_once __DIR__ . '/config.php';

function getDbConnection(): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    ];

    return new PDO($dsn, DB_USER, DB_PASS, $options);
}

function ensureStudent(PDO $pdo, string $studentId, string $firstName, string $lastName): void
{
    $studentId = trim($studentId);
    $firstName = trim($firstName);
    $lastName = trim($lastName);

    $check = $pdo->prepare('SELECT student_id FROM students WHERE student_id = :student_id LIMIT 1');
    $check->execute([':student_id' => $studentId]);
    $row = $check->fetch();

    if ($row) {
        $update = $pdo->prepare('UPDATE students SET first_name = :first_name, last_name = :last_name WHERE student_id = :student_id');
        $update->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':student_id' => $studentId,
        ]);

        return;
    }

    $insert = $pdo->prepare('INSERT INTO students (student_id, first_name, last_name) VALUES (:student_id, :first_name, :last_name)');
    $insert->execute([
        ':student_id' => $studentId,
        ':first_name' => $firstName,
        ':last_name' => $lastName,
    ]);
}

function ensureTeacher(PDO $pdo, int $teacherId): void
{
    $stmt = $pdo->prepare('SELECT teacher_id FROM teachers WHERE teacher_id = :teacher_id LIMIT 1');
    $stmt->execute([':teacher_id' => $teacherId]);

    if (!$stmt->fetch()) {
        throw new InvalidArgumentException('ไม่พบอาจารย์ที่เลือก');
    }
}

function getTeachers(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT teacher_id, teacher_name FROM teachers ORDER BY teacher_name ASC');
    $teachers = [];

    foreach ($stmt as $row) {
        $teachers[] = [
            'teacher_id' => (int)$row['teacher_id'],
            'teacher_name' => (string)$row['teacher_name'],
        ];
    }

    return $teachers;
}

function getTeacherSchedules(PDO $pdo, ?int $teacherId = null): array
{
    $sql = 'SELECT ts.schedule_id, ts.teacher_id, t.teacher_name, ts.student_id, s.first_name, s.last_name, ts.schedule_date, ts.start_time, ts.end_time, ts.round_num, ts.details, ts.note, ts.is_booking
            FROM teacher_schedules ts
            INNER JOIN teachers t ON t.teacher_id = ts.teacher_id
            LEFT JOIN students s ON s.student_id = ts.student_id';

    $params = [];
    if ($teacherId !== null) {
        $sql .= ' WHERE ts.teacher_id = :teacher_id';
        $params[':teacher_id'] = $teacherId;
    }

    $sql .= ' ORDER BY ts.schedule_date ASC, ts.start_time ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function hasTeacherScheduleConflict(PDO $pdo, int $teacherId, DateTimeImmutable $startDateTime, DateTimeImmutable $endDateTime): bool
{
    $stmt = $pdo->prepare('SELECT schedule_id
        FROM teacher_schedules
        WHERE teacher_id = :teacher_id
          AND schedule_date = :schedule_date
          AND start_time < :new_end_time
          AND end_time > :new_start_time
        LIMIT 1');

    $stmt->execute([
        ':teacher_id' => $teacherId,
        ':schedule_date' => $startDateTime->format('Y-m-d'),
        ':new_start_time' => $startDateTime->format('H:i:s'),
        ':new_end_time' => $endDateTime->format('H:i:s'),
    ]);

    return (bool)$stmt->fetch();
}

function createTeacherSchedule(PDO $pdo, array $data): array
{
    $teacherId = (int)($data['teacher_id'] ?? 0);
    $scheduleDate = trim((string)($data['schedule_date'] ?? ''));
    $startTime = trim((string)($data['start_time'] ?? ''));
    $endTime = trim((string)($data['end_time'] ?? ''));
    $note = trim((string)($data['note'] ?? ''));
    $studentId = isset($data['student_id']) && trim((string)$data['student_id']) !== '' ? trim((string)$data['student_id']) : null;
    $roundNum = isset($data['round_num']) ? (int)$data['round_num'] : null;
    $details = isset($data['details']) ? trim((string)$data['details']) : '';
    $isBooking = isset($data['is_booking']) ? (int)$data['is_booking'] : 0;

    if ($teacherId <= 0 || $scheduleDate === '' || $startTime === '' || $endTime === '') {
        throw new InvalidArgumentException('กรุณากรอกข้อมูลตารางงานของอาจารย์ให้ครบถ้วน');
    }

    if ($studentId !== null) {
        ensureStudent($pdo, $studentId, $data['first_name'] ?? '', $data['last_name'] ?? '');
    }

    ensureTeacher($pdo, $teacherId);

    $start = DateTimeImmutable::createFromFormat('Y-m-d H:i', $scheduleDate . ' ' . $startTime, new DateTimeZone('Asia/Bangkok'));
    $end = DateTimeImmutable::createFromFormat('Y-m-d H:i', $scheduleDate . ' ' . $endTime, new DateTimeZone('Asia/Bangkok'));

    if ($start === false || $end === false) {
        throw new InvalidArgumentException('รูปแบบวันที่หรือเวลาไม่ถูกต้อง');
    }

    if ($end <= $start) {
        throw new InvalidArgumentException('เวลาเริ่มต้องน้อยกว่าเวลาสิ้นสุด');
    }

    if ($studentId !== null && !in_array($roundNum, [1, 2], true)) {
        throw new InvalidArgumentException('รอบการนิเทศต้องเป็น 1 หรือ 2 เท่านั้น');
    }

    $insert = $pdo->prepare('INSERT INTO teacher_schedules (teacher_id, student_id, schedule_date, start_time, end_time, round_num, details, note, is_booking)
        VALUES (:teacher_id, :student_id, :schedule_date, :start_time, :end_time, :round_num, :details, :note, :is_booking)');

    $insert->execute([
        ':teacher_id' => $teacherId,
        ':student_id' => $studentId,
        ':schedule_date' => $scheduleDate,
        ':start_time' => $startTime,
        ':end_time' => $endTime,
        ':round_num' => $studentId !== null ? $roundNum : null,
        ':details' => $details === '' ? null : $details,
        ':note' => $note === '' ? null : $note,
        ':is_booking' => $studentId !== null ? 1 : $isBooking,
    ]);

    return [
        'schedule_id' => (int)$pdo->lastInsertId(),
        'teacher_id' => $teacherId,
        'student_id' => $studentId,
        'schedule_date' => $scheduleDate,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'round_num' => $studentId !== null ? $roundNum : null,
        'details' => $details === '' ? null : $details,
        'note' => $note === '' ? null : $note,
    ];
}

function updateTeacherSchedule(PDO $pdo, int $scheduleId, array $data): array
{
    $scheduleId = (int)$scheduleId;
    if ($scheduleId <= 0) {
        throw new InvalidArgumentException('ไม่พบรายการที่ต้องการแก้ไข');
    }

    $teacherId = (int)($data['teacher_id'] ?? 0);
    $scheduleDate = trim((string)($data['schedule_date'] ?? ''));
    $startTime = trim((string)($data['start_time'] ?? ''));
    $endTime = trim((string)($data['end_time'] ?? ''));
    $note = trim((string)($data['note'] ?? ''));
    $studentId = isset($data['student_id']) && trim((string)$data['student_id']) !== '' ? trim((string)$data['student_id']) : null;
    $roundNum = isset($data['round_num']) ? (int)$data['round_num'] : null;
    $details = isset($data['details']) ? trim((string)$data['details']) : '';
    $isBooking = isset($data['is_booking']) ? (int)$data['is_booking'] : 0;

    $currentSchedule = $pdo->prepare('SELECT student_id, round_num, is_booking FROM teacher_schedules WHERE schedule_id = :schedule_id LIMIT 1');
    $currentSchedule->execute([':schedule_id' => $scheduleId]);
    $currentRow = $currentSchedule->fetch();

    if ($currentRow) {
        if ($studentId === null && !empty($currentRow['student_id'])) {
            $studentId = (string)$currentRow['student_id'];
        }

        if ($roundNum === null && !empty($currentRow['round_num'])) {
            $roundNum = (int)$currentRow['round_num'];
        }

        if ($studentId !== null && $isBooking === 0 && (int)$currentRow['is_booking'] === 1) {
            $isBooking = 1;
        }
    }

    if ($teacherId <= 0 || $scheduleDate === '' || $startTime === '' || $endTime === '') {
        throw new InvalidArgumentException('กรุณากรอกข้อมูลให้ครบถ้วน');
    }

    $start = DateTimeImmutable::createFromFormat('Y-m-d H:i', $scheduleDate . ' ' . $startTime, new DateTimeZone('Asia/Bangkok'));
    $end = DateTimeImmutable::createFromFormat('Y-m-d H:i', $scheduleDate . ' ' . $endTime, new DateTimeZone('Asia/Bangkok'));

    if ($start === false || $end === false) {
        throw new InvalidArgumentException('รูปแบบวันที่หรือเวลาไม่ถูกต้อง');
    }

    if ($end <= $start) {
        throw new InvalidArgumentException('เวลาเริ่มต้องน้อยกว่าเวลาสิ้นสุด');
    }

    $checkConflict = $pdo->prepare('SELECT schedule_id FROM teacher_schedules
        WHERE teacher_id = :teacher_id
          AND schedule_date = :schedule_date
          AND start_time < :new_end_time
          AND end_time > :new_start_time
          AND schedule_id != :schedule_id
        LIMIT 1');

    $checkConflict->execute([
        ':teacher_id' => $teacherId,
        ':schedule_date' => $scheduleDate,
        ':new_start_time' => $startTime . ':00',
        ':new_end_time' => $endTime . ':00',
        ':schedule_id' => $scheduleId,
    ]);

    if ($checkConflict->fetch()) {
        throw new RuntimeException('ช่วงเวลานี้ทับกับตารางงานที่มีอยู่ของอาจารย์ กรุณาเลือกเวลาอื่น');
    }

    if ($studentId !== null) {
        ensureStudent($pdo, $studentId, $data['first_name'] ?? '', $data['last_name'] ?? '');
        if (!in_array($roundNum, [1, 2], true)) {
            throw new InvalidArgumentException('รอบการนิเทศต้องเป็น 1 หรือ 2 เท่านั้น');
        }
    }

    ensureTeacher($pdo, $teacherId);

    $stmt = $pdo->prepare('UPDATE teacher_schedules
        SET teacher_id = :teacher_id,
            student_id = :student_id,
            schedule_date = :schedule_date,
            start_time = :start_time,
            end_time = :end_time,
            round_num = :round_num,
            details = :details,
            note = :note,
            is_booking = :is_booking
        WHERE schedule_id = :schedule_id');

    $stmt->execute([
        ':teacher_id' => $teacherId,
        ':student_id' => $studentId,
        ':schedule_date' => $scheduleDate,
        ':start_time' => $startTime,
        ':end_time' => $endTime,
        ':round_num' => $studentId !== null ? $roundNum : null,
        ':details' => $details === '' ? null : $details,
        ':note' => $note === '' ? null : $note,
        ':is_booking' => $studentId !== null ? 1 : $isBooking,
        ':schedule_id' => $scheduleId,
    ]);

    return [
        'schedule_id' => $scheduleId,
        'teacher_id' => $teacherId,
        'student_id' => $studentId,
        'schedule_date' => $scheduleDate,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'round_num' => $studentId !== null ? $roundNum : null,
        'details' => $details === '' ? null : $details,
        'note' => $note === '' ? null : $note,
    ];
}

function deleteTeacherSchedule(PDO $pdo, int $scheduleId): void
{
    $scheduleId = (int)$scheduleId;
    if ($scheduleId <= 0) {
        throw new InvalidArgumentException('ไม่พบรายการที่ต้องการลบ');
    }

    $stmt = $pdo->prepare('DELETE FROM teacher_schedules WHERE schedule_id = :schedule_id LIMIT 1');
    $stmt->execute([':schedule_id' => $scheduleId]);

    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('ไม่สามารถลบรายการนี้ได้');
    }
}

function createBooking(PDO $pdo, array $data): array
{
    $teacherId = (int)($data['teacher_id'] ?? 1);
    $studentId = trim((string)($data['student_id'] ?? ''));
    $firstName = trim((string)($data['first_name'] ?? ''));
    $lastName = trim((string)($data['last_name'] ?? ''));
    $bookingDate = trim((string)($data['booking_date'] ?? ''));
    $startTime = trim((string)($data['start_time'] ?? ''));
    $endTime = trim((string)($data['end_time'] ?? ''));
    $roundNum = (int)($data['round_num'] ?? 0);
    $details = trim((string)($data['details'] ?? ''));

    if ($studentId === '' || $firstName === '' || $lastName === '' || $bookingDate === '' || $startTime === '' || $endTime === '') {
        throw new InvalidArgumentException('กรุณากรอกข้อมูลให้ครบถ้วน');
    }

    if ($teacherId <= 0) {
        throw new InvalidArgumentException('กรุณาเลือกอาจารย์ก่อนทำการจอง');
    }

    if (!in_array($roundNum, [1, 2], true)) {
        throw new InvalidArgumentException('รอบการนิเทศต้องเป็น 1 หรือ 2 เท่านั้น');
    }

    $startDateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i', $bookingDate . ' ' . $startTime, new DateTimeZone('Asia/Bangkok'));
    $endDateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i', $bookingDate . ' ' . $endTime, new DateTimeZone('Asia/Bangkok'));

    if ($startDateTime === false || $endDateTime === false) {
        throw new InvalidArgumentException('รูปแบบวันที่หรือเวลาไม่ถูกต้อง');
    }

    if ($endDateTime <= $startDateTime) {
        throw new InvalidArgumentException('เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด');
    }

    $pdo->beginTransaction();

    try {
        ensureTeacher($pdo, $teacherId);
        ensureStudent($pdo, $studentId, $firstName, $lastName);

        if (hasTeacherScheduleConflict($pdo, $teacherId, $startDateTime, $endDateTime)) {
            throw new RuntimeException('ช่วงเวลานี้ทับกับตารางงานของอาจารย์ กรุณาเลือกเวลาอื่น');
        }

        $check = $pdo->prepare('SELECT schedule_id FROM teacher_schedules WHERE teacher_id = :teacher_id AND student_id IS NOT NULL AND round_num = :round_num AND schedule_date = :schedule_date AND start_time < :new_end_time AND end_time > :new_start_time LIMIT 1');
        $check->execute([
            ':teacher_id' => $teacherId,
            ':round_num' => $roundNum,
            ':schedule_date' => $bookingDate,
            ':new_start_time' => $startTime . ':00',
            ':new_end_time' => $endTime . ':00',
        ]);

        if ($check->fetch()) {
            throw new RuntimeException('ช่วงเวลานี้มีนักศึกษาคนอื่นจองไว้แล้ว');
        }

        $insert = $pdo->prepare('INSERT INTO teacher_schedules (teacher_id, student_id, schedule_date, start_time, end_time, round_num, details, is_booking) VALUES (:teacher_id, :student_id, :schedule_date, :start_time, :end_time, :round_num, :details, :is_booking)');
        $insert->execute([
            ':teacher_id' => $teacherId,
            ':student_id' => $studentId,
            ':schedule_date' => $bookingDate,
            ':start_time' => $startTime,
            ':end_time' => $endTime,
            ':round_num' => $roundNum,
            ':details' => $details === '' ? null : $details,
            ':is_booking' => 1,
        ]);

        $scheduleId = (int)$pdo->lastInsertId();
        $pdo->commit();

        return [
            'booking_id' => $scheduleId,
            'teacher_id' => $teacherId,
            'student_id' => $studentId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'start_time' => $startDateTime->format('Y-m-d H:i:s'),
            'end_time' => $endDateTime->format('Y-m-d H:i:s'),
            'round_num' => $roundNum,
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getStudents(PDO $pdo): array
{
    $sql = 'SELECT student_id, first_name, last_name
            FROM students
            ORDER BY first_name ASC, last_name ASC, student_id ASC';

    $stmt = $pdo->query($sql);
    $students = [];

    foreach ($stmt as $row) {
        $fullName = trim((string)$row['first_name'] . ' ' . (string)$row['last_name']);

        $students[] = [
            'student_id' => (string)$row['student_id'],
            'first_name' => (string)$row['first_name'],
            'last_name' => (string)$row['last_name'],
            'full_name' => $fullName,
        ];
    }

    return $students;
}

function getCalendarEvents(PDO $pdo): array
{
    $sql = 'SELECT ts.schedule_id, ts.teacher_id, ts.schedule_date, ts.start_time, ts.end_time, ts.round_num, ts.details, ts.note, ts.is_booking,
                   s.student_id, s.first_name, s.last_name,
                   CONCAT(ts.schedule_date, " ", ts.start_time) AS start_datetime,
                   CONCAT(ts.schedule_date, " ", ts.end_time) AS end_datetime
            FROM teacher_schedules ts
            LEFT JOIN students s ON s.student_id = ts.student_id
            ORDER BY ts.schedule_date ASC, ts.start_time ASC';

    $stmt = $pdo->query($sql);
    $events = [];

    foreach ($stmt as $row) {
        $studentName = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));
        $isBooking = (int)$row['is_booking'] === 1;
        $start = new DateTimeImmutable($row['start_datetime']);
        $end = new DateTimeImmutable($row['end_datetime']);
        $timeLabel = $start->format('H:i') . ' - ' . $end->format('H:i');

        $title = $isBooking
            ? ($studentName !== '' ? $studentName . ' · ' . $timeLabel : 'จอง · ' . $timeLabel)
            : ('ภาระงานอาจารย์ · ' . $timeLabel);

        $backgroundColor = $isBooking
            ? ((int)$row['round_num'] === 1 ? '#f59e0b' : '#22c55e')
            : '#dc2626';

        $events[] = [
            'id' => (int)$row['schedule_id'],
            'title' => $title,
            'start' => $row['start_datetime'],
            'end' => $row['end_datetime'],
            'backgroundColor' => $backgroundColor,
            'borderColor' => $backgroundColor,
            'textColor' => '#111827',
            'extendedProps' => [
                'teacher_id' => (int)$row['teacher_id'],
                'student_id' => $row['student_id'] ?? '',
                'details' => $row['details'] ?? '',
                'note' => $row['note'] ?? '',
                'round_num' => $isBooking ? (int)$row['round_num'] : 0,
                'student_name' => $studentName,
                'time_label' => $timeLabel,
                'is_workload' => !$isBooking,
                'is_booking' => $isBooking,
            ],
        ];
    }

    return $events;
}
