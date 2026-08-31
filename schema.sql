-- สร้างตารางนักศึกษา (students)
CREATE TABLE IF NOT EXISTS students (
    student_id VARCHAR(15) PRIMARY KEY COMMENT 'รหัสนักศึกษา',
    first_name VARCHAR(50) NOT NULL COMMENT 'ชื่อจริง',
    last_name VARCHAR(50) NOT NULL COMMENT 'นามสกุล'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO students (student_id, first_name, last_name)
SELECT '650101001', 'สมชาย', 'ใจดี'
WHERE NOT EXISTS (SELECT 1 FROM students WHERE student_id = '650101001');

INSERT INTO students (student_id, first_name, last_name)
SELECT '650101002', 'สุดา', 'ทองแท้'
WHERE NOT EXISTS (SELECT 1 FROM students WHERE student_id = '650101002');

INSERT INTO students (student_id, first_name, last_name)
SELECT '650101003', 'กิตติ', 'ศรีสวัสดิ์'
WHERE NOT EXISTS (SELECT 1 FROM students WHERE student_id = '650101003');

INSERT INTO students (student_id, first_name, last_name)
SELECT '650101004', 'ณัฐชา', 'แสนสุข'
WHERE NOT EXISTS (SELECT 1 FROM students WHERE student_id = '650101004');

INSERT INTO students (student_id, first_name, last_name)
SELECT '650101005', 'พชร', 'วัฒนาพงศ์'
WHERE NOT EXISTS (SELECT 1 FROM students WHERE student_id = '650101005');

-- สร้างตารางอาจารย์
CREATE TABLE IF NOT EXISTS teachers (
    teacher_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสอาจารย์',
    teacher_name VARCHAR(100) NOT NULL COMMENT 'ชื่ออาจารย์',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'เวลาที่เพิ่มอาจารย์'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางเวลาการทำงาน/การจองของอาจารย์
CREATE TABLE IF NOT EXISTS teacher_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสตารางงาน',
    teacher_id INT NOT NULL COMMENT 'อาจารย์ที่รับผิดชอบ',
    student_id VARCHAR(15) NULL COMMENT 'รหัสนักศึกษาที่จอง (ถ้ามี)',
    schedule_date DATE NOT NULL COMMENT 'วันที่ทำงาน',
    start_time TIME NOT NULL COMMENT 'เวลาเริ่มงาน',
    end_time TIME NOT NULL COMMENT 'เวลาสิ้นสุดงาน',
    round_num TINYINT NULL COMMENT 'รอบการนิเทศ (1 หรือ 2)',
    details TEXT NULL COMMENT 'รายละเอียดเพิ่มเติม',
    note VARCHAR(255) DEFAULT NULL COMMENT 'หมายเหตุ',
    is_booking TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=การจอง, 0=ตารางงานอาจารย์',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'เวลาที่เพิ่มตารางงาน',

    CONSTRAINT fk_teacher_schedule
        FOREIGN KEY (teacher_id)
        REFERENCES teachers(teacher_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_teacher_schedule_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO teachers (teacher_id, teacher_name)
SELECT 1, 'อาจารย์ประจำภาควิชา'
WHERE NOT EXISTS (SELECT 1 FROM teachers WHERE teacher_id = 1);
