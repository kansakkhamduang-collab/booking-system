<?php
require_once __DIR__ . '/db.php';

$dbError = null;
try {
    $pdo = getDbConnection();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจองคิวนิเทศออนไลน์</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="main-shell">
        <nav class="topbar navbar navbar-expand-lg py-3 px-4 mb-4">
            <div class="container-fluid">
                <span class="navbar-brand text-dark fw-bold page-title mb-0">ระบบจองคิวนิเทศออนไลน์</span>
                <div class="ms-auto d-flex gap-2">
                    <button class="btn btn-light" type="button" data-bs-toggle="modal" data-bs-target="#bookingModal">
                        + จองคิว
                    </button>
                </div>
            </div>
        </nav>

        <?php if ($dbError): ?>
            <div class="alert alert-danger" role="alert">
                ไม่สามารถเชื่อมต่อฐานข้อมูลได้: <?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?>
                <br>
                กรุณาแก้ไขค่าใน <strong>config.php</strong> และสร้างตารางตามคำสั่งใน <strong>schema.sql</strong>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-12">
                <div class="card p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h4 class="mb-0 fw-bold">ปฏิทินการจอง</h4>
                        </div>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <span class="badge rounded-pill" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #78350f;">รอบ 1</span>
                            <span class="badge rounded-pill" style="background: linear-gradient(135deg, #34d399 0%, #10b981 100%); color: #065f46;">รอบ 2</span>
                            <span class="badge rounded-pill" style="background: linear-gradient(135deg, #f87171 0%, #ef4444 100%); color: #7c2d12;">ภาระงานอาจารย์</span>
                        </div>
                    </div>
                    <div id="calendar" class="calendar-wrap"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="bookingModalLabel">จองคิวนิเทศ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="formAlert" class="alert d-none" role="alert"></div>
                    <form id="bookingForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="student_select" class="form-label">เลือกนักศึกษา</label>
                                <select class="form-select" id="student_select" required>
                                    <option value="">-- เลือกนักศึกษา --</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="student_id" class="form-label">รหัสนักศึกษา</label>
                                <input type="text" class="form-control" id="student_id" name="student_id" maxlength="15" readonly>
                            </div>
                            <div class="col-md-3">
                                <label for="student_name_display" class="form-label">ชื่อ-นามสกุล</label>
                                <input type="text" class="form-control" id="student_name_display" readonly>
                            </div>
                            <input type="hidden" id="first_name" name="first_name">
                            <input type="hidden" id="last_name" name="last_name">
                            <input type="hidden" id="teacher_id" name="teacher_id" value="1">
                            <div class="col-md-4">
                                <label for="booking_date" class="form-label">วันที่จอง</label>
                                <input type="date" class="form-control" id="booking_date" name="booking_date" required>
                            </div>
                            <div class="col-md-4">
                                <label for="start_time" class="form-label">วัน-เวลาเริ่มต้น</label>
                                <select class="form-select" id="start_time" name="start_time" required>
                                    <option value="">-- เลือกเวลา --</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="end_time" class="form-label">วัน-เวลาสิ้นสุด</label>
                                <select class="form-select" id="end_time" name="end_time" required>
                                    <option value="">-- เลือกเวลา --</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="round_num" class="form-label">รอบการนิเทศ</label>
                                <select class="form-select" id="round_num" name="round_num" required>
                                    <option value="1">รอบ 1</option>
                                    <option value="2">รอบ 2</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="details" class="form-label">รายละเอียดเพิ่มเติม</label>
                                <textarea class="form-control" id="details" name="details" rows="4" placeholder=""></textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4 gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-primary">บันทึกการจอง</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventDetailModalLabel">รายละเอียดการจอง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="small text-muted mb-1">นักศึกษา</div>
                        <div id="detailStudentName" class="fw-bold"></div>
                    </div>
                    <div class="mb-3">
                        <div class="small text-muted mb-1">รหัสนักศึกษา</div>
                        <div id="detailStudentId"></div>
                    </div>
                    <div class="mb-3">
                        <div class="small text-muted mb-1">รอบการนิเทศ</div>
                        <div id="detailRound"></div>
                    </div>
                    <div class="mb-3">
                        <div class="small text-muted mb-1">เวลา</div>
                        <div id="detailTime"></div>
                    </div>
                    <div class="mb-0">
                        <div class="small text-muted mb-1">รายละเอียด</div>
                        <div id="detailDescription" class="text-body"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/th.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
