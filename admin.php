<?php
require_once __DIR__ . '/db.php';

try {
    $pdo = getDbConnection();
    $teachers = getTeachers($pdo);
} catch (Throwable $e) {
    $teachers = [];
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการตารางอาจารย์</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="main-shell">
        <nav class="topbar navbar navbar-expand-lg py-3 px-4 mb-4">
            <div class="container-fluid">
                <span class="navbar-brand text-white fw-bold page-title mb-0">จัดการตารางอาจารย์</span>
                <div class="ms-auto d-flex gap-2">
                    <a href="index.php" class="btn btn-light">กลับไปปฏิทิน</a>
                </div>
            </div>
        </nav>

        <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editScheduleModalLabel">แก้ไขตารางงาน</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="editFormAlert" class="alert d-none" role="alert"></div>
                        <form id="editScheduleForm">
                            <input type="hidden" id="edit_schedule_id" value="">
                            <input type="hidden" id="edit_student_id" value="">
                            <input type="hidden" id="edit_first_name" value="">
                            <input type="hidden" id="edit_last_name" value="">
                            <input type="hidden" id="edit_round_num" value="">
                            <input type="hidden" id="edit_is_booking" value="">
                            <input type="hidden" id="edit_details" value="">

                            <div class="mb-3">
                                <label for="edit_teacher_id" class="form-label">อาจารย์</label>
                                <select class="form-select" id="edit_teacher_id" required>
                                    <?php if (!empty($teachers)): ?>
                                        <?php foreach ($teachers as $teacher): ?>
                                            <option value="<?php echo (int)$teacher['teacher_id']; ?>"><?php echo htmlspecialchars($teacher['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="edit_schedule_date" class="form-label">วันที่</label>
                                <input type="date" class="form-control" id="edit_schedule_date" required>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="edit_start_time" class="form-label">เวลาเริ่ม (24 ชม.)</label>
                                    <input type="text" class="form-control" id="edit_start_time" placeholder="09:00 หรือ 09.00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_end_time" class="form-label">เวลาสิ้นสุด (24 ชม.)</label>
                                    <input type="text" class="form-control" id="edit_end_time" placeholder="10:00 หรือ 10.00" required>
                                </div>
                            </div>

                            <div id="edit_student_block" class="mt-3 d-none">
                                <label class="form-label">นักเรียน</label>
                                <div class="border rounded p-3 bg-light">
                                    <div class="fw-semibold" id="edit_student_name_text">—</div>
                                </div>
                            </div>

                            <div id="edit_round_block" class="mt-3 d-none">
                                <label for="edit_round_num_select" class="form-label">รอบการนิเทศ</label>
                                <select class="form-select" id="edit_round_num_select">
                                    <option value="1">รอบที่ 1 (สีส้ม)</option>
                                    <option value="2">รอบที่ 2 (สีเขียว)</option>
                                </select>
                            </div>

                            <div class="mt-3">
                                <label for="edit_note" class="form-label">หมายเหตุ</label>
                                <textarea class="form-control" id="edit_note" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary" form="editScheduleForm">บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($dbError)): ?>
            <div class="alert alert-danger">ไม่สามารถโหลดข้อมูลได้: <?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card p-3 p-md-4">
                    <h4 class="fw-bold mb-3">เพิ่มตารางงานอาจารย์</h4>
                    <div id="formAlert" class="alert d-none" role="alert"></div>

                    <form id="teacherScheduleForm">
                        <input type="hidden" id="schedule_id" value="">

                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">อาจารย์</label>
                            <select class="form-select" id="teacher_id" required>
                                <?php if (!empty($teachers)): ?>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo (int)$teacher['teacher_id']; ?>"><?php echo htmlspecialchars($teacher['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="schedule_date" class="form-label">เลือกวันที่จากปฏิทิน</label>
                            <input type="date" class="form-control" id="schedule_date" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="start_time" class="form-label">เวลาเริ่ม (24 ชม.)</label>
                                <input type="text" class="form-control" id="start_time" placeholder="09:00 หรือ 09.00" pattern="^([01]\d|2[0-3])([:.])[0-5]\d$" required>
                            </div>
                            <div class="col-md-6">
                                <label for="end_time" class="form-label">เวลาสิ้นสุด (24 ชม.)</label>
                                <input type="text" class="form-control" id="end_time" placeholder="10:00 หรือ 10.00" pattern="^([01]\d|2[0-3])([:.])[0-5]\d$" required>
                            </div>
                        </div>

                        <div class="mt-3 mb-3">
                            <label for="note" class="form-label">หมายเหตุ / ระบุเวลาหยุด/ช่วงงาน</label>
                            <textarea class="form-control" id="note" rows="3" placeholder="เช่น อยู่ในห้องสำนักงาน, เปิดให้คุยเรื่องโปรเจกต์, ฯลฯ"></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" id="cancelEditBtn" class="btn btn-outline-secondary d-none">ยกเลิก</button>
                            <button type="reset" class="btn btn-outline-secondary">ล้าง</button>
                            <button type="submit" class="btn btn-primary" id="submitButton">บันทึกตารางงาน</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card p-3 p-md-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0">กรองงานทั้งหมด</h4>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="filter_start_date" class="form-label">จากวันที่</label>
                            <input type="date" class="form-control" id="filter_start_date">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_end_date" class="form-label">ถึงวันที่</label>
                            <input type="date" class="form-control" id="filter_end_date">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_type" class="form-label">ประเภท</label>
                            <select class="form-select" id="filter_type">
                                <option value="all">ทั้งหมด</option>
                                <option value="booking">การนิเทศ</option>
                                <option value="teacher">จากอาจารย์</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_search" class="form-label">ค้นหา</label>
                            <input type="text" class="form-control" id="filter_search" placeholder="ชื่อ / อาจารย์ / หมายเหตุ">
                        </div>
                    </div>
                    <div class="mt-3 d-flex justify-content-end">
                        <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary btn-sm">ล้างฟิลเตอร์</button>
                    </div>
                </div>

                <div class="card p-3 p-md-4">
                    <h4 class="fw-bold mb-3">รายการตารางงาน</h4>
                    <div class="table-responsive">
                        <table class="table align-middle table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>วันที่</th>
                                    <th>เวลา</th>
                                    <th>ประเภท</th>
                                    <th>ชื่อ</th>
                                    <th>หมายเหตุ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleTableBody">
                                <tr>
                                    <td colspan="6" class="text-muted text-center">กำลังโหลด...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const scheduleForm = document.getElementById('teacherScheduleForm');
        const editScheduleForm = document.getElementById('editScheduleForm');
        const alertBox = document.getElementById('formAlert');
        const scheduleIdInput = document.getElementById('schedule_id');
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');
        const scheduleDateInput = document.getElementById('schedule_date');
        const scheduleTableBody = document.getElementById('scheduleTableBody');
        const submitButton = document.getElementById('submitButton');
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        const filterStartDateInput = document.getElementById('filter_start_date');
        const filterEndDateInput = document.getElementById('filter_end_date');
        const filterTypeSelect = document.getElementById('filter_type');
        const filterSearchInput = document.getElementById('filter_search');
        const resetFiltersBtn = document.getElementById('resetFiltersBtn');
        const editScheduleModal = document.getElementById('editScheduleModal');
        const editFormAlert = document.getElementById('editFormAlert');
        let allSchedulesState = [];

        const setEditAlert = (message, type = 'danger') => {
            editFormAlert.className = 'alert d-none';
            editFormAlert.textContent = '';
            editFormAlert.classList.remove('d-none');
            editFormAlert.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
            editFormAlert.textContent = message;
        };

        const getBangkokDateString = () => {
            const now = new Date();
            const formatter = new Intl.DateTimeFormat('en-CA', {
                timeZone: 'Asia/Bangkok',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });

            const parts = formatter.formatToParts(now).reduce((acc, part) => {
                if (part.type !== 'literal') {
                    acc[part.type] = part.value;
                }
                return acc;
            }, {});

            return `${parts.year}-${parts.month}-${parts.day}`;
        };

        const normalizeTimeValue = (value) => {
            if (!value) return '';

            const trimmed = value.trim();
            const normalized = trimmed.replace('.', ':');
            const parts = normalized.split(':');

            if (parts.length < 2 || parts.length > 3) return '';

            let hours = parseInt(parts[0], 10);
            let minutes = parseInt(parts[1], 10);

            if (isNaN(hours) || isNaN(minutes)) return '';
            if (hours < 0 || hours > 23 || minutes < 0 || minutes > 59) return '';

            const paddedHours = String(hours).padStart(2, '0');
            const paddedMinutes = String(minutes).padStart(2, '0');

            return `${paddedHours}:${paddedMinutes}`;
        };

        const setDefaultScheduleDate = () => {
            scheduleDateInput.value = getBangkokDateString();
        };

        const setFormMode = (isEditing) => {
            submitButton.textContent = isEditing ? 'บันทึกการแก้ไข' : 'บันทึกตารางงาน';
            cancelEditBtn.classList.toggle('d-none', !isEditing);
        };

        const setAlert = (message, type = 'danger') => {
            alertBox.className = 'alert d-none';
            alertBox.textContent = '';
            alertBox.classList.remove('d-none');
            alertBox.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
            alertBox.textContent = message;
        };

        const resetForm = () => {
            scheduleForm.reset();
            scheduleIdInput.value = '';
            startTimeInput.value = '';
            endTimeInput.value = '';
            setFormMode(false);
            setDefaultScheduleDate();
            applyAllFilters();
        };

        const renderScheduleRows = (schedules, tableBody) => {
            if (!schedules.length) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-muted text-center">ไม่มีข้อมูล</td></tr>';
                return;
            }

            tableBody.innerHTML = schedules.map((item) => {
                const date = item.schedule_date || '-';
                const start = item.start_time || '-';
                const end = item.end_time || '-';
                const note = item.note || item.details || '—';
                const isBooking = Number(item.is_booking) === 1;
                const studentName = item.first_name || item.last_name
                    ? `${item.first_name || ''} ${item.last_name || ''}`.trim()
                    : '—';
                const typeLabel = isBooking ? 'การนิเทศ' : 'จากอาจารย์';
                const rowStyle = isBooking
                    ? 'background: linear-gradient(90deg, #fffbeb 0%, rgba(255, 251, 235, 0) 100%); border-left: 4px solid #fbbf24;'
                    : 'background: linear-gradient(90deg, #fef2f2 0%, rgba(254, 242, 242, 0) 100%); border-left: 4px solid #ef4444;';
                const nameCell = isBooking
                    ? `<span class="fw-semibold">${studentName}</span>`
                    : '<span class="text-muted">ภาระงานอาจารย์</span>';

                return `
                    <tr style="${rowStyle}">
                        <td>${date}</td>
                        <td>${start} - ${end}</td>
                        <td>
                            <span class="badge ${isBooking ? 'text-bg-warning' : 'text-bg-danger'}">
                                ${typeLabel}
                            </span>
                        </td>
                        <td>${nameCell}</td>
                        <td>${note}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary edit-schedule-btn" data-id="${item.schedule_id}">แก้ไข</button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-schedule-btn" data-id="${item.schedule_id}">ลบ</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        };

        const applyAllFilters = () => {
            const searchValue = (filterSearchInput.value || '').trim().toLowerCase();
            const fromDate = filterStartDateInput.value;
            const toDate = filterEndDateInput.value;
            const selectedType = filterTypeSelect.value;
            const defaultDate = getBangkokDateString();
            const selectedScheduleDate = scheduleDateInput.value || defaultDate;
            const effectiveFrom = fromDate || selectedScheduleDate;
            const effectiveTo = toDate || fromDate || selectedScheduleDate;

            const filtered = allSchedulesState.filter((item) => {
                const itemDate = item.schedule_date || '';
                const matchesFrom = itemDate >= effectiveFrom;
                const matchesTo = itemDate <= effectiveTo;
                const matchesType = selectedType === 'all'
                    || (selectedType === 'booking' && Number(item.is_booking) === 1)
                    || (selectedType === 'teacher' && Number(item.is_booking) !== 1);

                const searchable = [
                    item.teacher_name,
                    item.first_name,
                    item.last_name,
                    item.note,
                    item.details,
                    item.schedule_date,
                    item.start_time,
                    item.end_time,
                    item.student_id,
                ].join(' ').toLowerCase();

                const matchesSearch = !searchValue || searchable.includes(searchValue);

                return matchesFrom && matchesTo && matchesType && matchesSearch;
            });

            renderScheduleRows(filtered, scheduleTableBody);
        };

        const loadSchedules = async () => {
            try {
                const response = await fetch('api/teachers.php');
                if (!response.ok) {
                    throw new Error('ไม่สามารถโหลดรายการตารางงานได้');
                }

                const data = await response.json();
                const schedules = Array.isArray(data && data.schedules) ? data.schedules : [];
                allSchedulesState = schedules;

                const selectedDate = scheduleDateInput.value || getBangkokDateString();
                if (!scheduleDateInput.value) {
                    scheduleDateInput.value = selectedDate;
                }

                const filteredSchedules = schedules.filter((item) => (item.schedule_date || '') === selectedDate);

                if (!filteredSchedules.length) {
                    scheduleTableBody.innerHTML = `<tr><td colspan="6" class="text-muted text-center">ยังไม่มีตารางงานสำหรับวันที่ ${selectedDate}</td></tr>`;
                } else {
                    scheduleTableBody.innerHTML = filteredSchedules.map((item) => {
                        const date = item.schedule_date || '-';
                        const start = item.start_time || '-';
                        const end = item.end_time || '-';
                        const note = item.note || item.details || '—';
                        const isBooking = Number(item.is_booking) === 1;
                        const studentName = item.first_name || item.last_name
                            ? `${item.first_name || ''} ${item.last_name || ''}`.trim()
                            : '—';
                        const typeLabel = isBooking ? 'การนิเทศ' : 'จากอาจารย์';
                        const rowStyle = isBooking
                            ? 'background-color: #fff7ed; border-left: 4px solid #f59e0b;'
                            : 'background-color: #fef2f2; border-left: 4px solid #dc2626;';
                        const nameCell = isBooking
                            ? `<span class="fw-semibold">${studentName}</span>`
                            : '<span class="text-muted">ภาระงานอาจารย์</span>';

                        return `
                            <tr style="${rowStyle}">
                                <td>${date}</td>
                                <td>${start} - ${end}</td>
                                <td>
                                    <span class="badge ${isBooking ? 'text-bg-warning' : 'text-bg-danger'}">
                                        ${typeLabel}
                                    </span>
                                </td>
                                <td>${nameCell}</td>
                                <td>${note}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-schedule-btn" data-id="${item.schedule_id}">แก้ไข</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-schedule-btn" data-id="${item.schedule_id}">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                }

                applyAllFilters();
            } catch (error) {
                scheduleTableBody.innerHTML = '<tr><td colspan="6" class="text-danger text-center">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
            }
        };

        const populateScheduleForm = (schedule) => {
            if (!schedule) return;

            const isBooking = Number(schedule.is_booking) === 1;
            const studentName = schedule.first_name || schedule.last_name
                ? `${schedule.first_name || ''} ${schedule.last_name || ''}`.trim()
                : '—';
            const studentId = schedule.student_id ? String(schedule.student_id).trim() : '';

            document.getElementById('edit_teacher_id').value = schedule.teacher_id || '';
            document.getElementById('edit_schedule_date').value = schedule.schedule_date || '';
            document.getElementById('edit_start_time').value = schedule.start_time || '';
            document.getElementById('edit_end_time').value = schedule.end_time || '';
            document.getElementById('edit_note').value = schedule.note || schedule.details || '';
            document.getElementById('edit_schedule_id').value = String(schedule.schedule_id);
            document.getElementById('edit_student_id').value = studentId;
            document.getElementById('edit_first_name').value = schedule.first_name || '';
            document.getElementById('edit_last_name').value = schedule.last_name || '';
            document.getElementById('edit_round_num').value = schedule.round_num ?? '';
            document.getElementById('edit_round_num_select').value = schedule.round_num ?? '1';
            document.getElementById('edit_is_booking').value = isBooking ? '1' : '0';
            document.getElementById('edit_details').value = schedule.details || '';
            document.getElementById('edit_student_name_text').textContent = isBooking ? studentName : '—';
            document.getElementById('edit_student_block').classList.toggle('d-none', !isBooking);
            document.getElementById('edit_round_block').classList.toggle('d-none', !isBooking);

            const modal = bootstrap.Modal.getOrCreateInstance(editScheduleModal);
            modal.show();
        };

        const handleEditSchedule = async (scheduleId) => {
            try {
                const response = await fetch('api/teachers.php');
                const data = await response.json();
                const schedules = Array.isArray(data && data.schedules) ? data.schedules : [];
                const schedule = schedules.find((item) => String(item.schedule_id) === String(scheduleId));
                populateScheduleForm(schedule);
            } catch (error) {
                setAlert('ไม่สามารถโหลดข้อมูลเพื่อแก้ไขได้');
            }
        };

        const handleDeleteSchedule = async (scheduleId) => {
            const confirmed = window.confirm('ต้องการลบรายการนี้หรือไม่?');
            if (!confirmed) {
                return;
            }

            try {
                const response = await fetch('api/teachers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ action: 'delete_schedule', schedule_id: scheduleId })
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'ลบรายการไม่สำเร็จ');
                }

                if (scheduleIdInput.value === String(scheduleId)) {
                    resetForm();
                }

                setAlert(result.message || 'ลบรายการเรียบร้อยแล้ว', 'success');
                await loadSchedules();
            } catch (error) {
                setAlert(error.message || 'เกิดข้อผิดพลาดในการลบ');
            }
        };

        filterStartDateInput.addEventListener('change', applyAllFilters);
        filterEndDateInput.addEventListener('change', applyAllFilters);
        filterTypeSelect.addEventListener('change', applyAllFilters);
        filterSearchInput.addEventListener('input', applyAllFilters);

        resetFiltersBtn.addEventListener('click', () => {
            filterStartDateInput.value = '';
            filterEndDateInput.value = '';
            filterTypeSelect.value = 'all';
            filterSearchInput.value = '';
            applyAllFilters();
        });

        scheduleDateInput.addEventListener('change', () => {
            loadSchedules();
        });

        cancelEditBtn.addEventListener('click', () => {
            resetForm();
            setAlert('ยกเลิกการแก้ไขแล้ว', 'success');
        });

        document.addEventListener('click', (event) => {
            const editButton = event.target.closest('.edit-schedule-btn');
            const deleteButton = event.target.closest('.delete-schedule-btn');

            if (editButton) {
                handleEditSchedule(editButton.dataset.id);
                return;
            }

            if (deleteButton) {
                handleDeleteSchedule(deleteButton.dataset.id);
            }
        });

        editScheduleForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            editFormAlert.classList.add('d-none');

            const scheduleId = document.getElementById('edit_schedule_id').value ? Number(document.getElementById('edit_schedule_id').value) : null;
            const startTime = normalizeTimeValue(document.getElementById('edit_start_time').value);
            const endTime = normalizeTimeValue(document.getElementById('edit_end_time').value);

            const editStudentId = document.getElementById('edit_student_id').value.trim();
            const payload = {
                action: 'update_schedule',
                schedule_id: scheduleId,
                teacher_id: Number(document.getElementById('edit_teacher_id').value),
                student_id: editStudentId !== '' ? editStudentId : null,
                first_name: document.getElementById('edit_first_name').value || '',
                last_name: document.getElementById('edit_last_name').value || '',
                round_num: document.getElementById('edit_round_num_select').value ? Number(document.getElementById('edit_round_num_select').value) : null,
                is_booking: Number(document.getElementById('edit_is_booking').value || 0),
                schedule_date: document.getElementById('edit_schedule_date').value,
                start_time: startTime,
                end_time: endTime,
                details: document.getElementById('edit_details').value || '',
                note: document.getElementById('edit_note').value.trim(),
            };

            if (!payload.teacher_id) {
                setEditAlert('กรุณาเลือกอาจารย์');
                return;
            }

            if (!payload.schedule_date || !payload.start_time || !payload.end_time) {
                setEditAlert('กรุณากรอกวันที่และเวลาให้ครบถ้วน');
                return;
            }

            try {
                const response = await fetch('api/teachers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึก');
                }

                const modal = bootstrap.Modal.getInstance(editScheduleModal);
                if (modal) {
                    modal.hide();
                }

                setAlert(result.message || 'บันทึกตารางงานเรียบร้อยแล้ว', 'success');
                await loadSchedules();
            } catch (error) {
                setEditAlert(error.message || 'เกิดข้อผิดพลาด');
            }
        });

        editScheduleModal.addEventListener('hidden.bs.modal', () => {
            editFormAlert.classList.add('d-none');
            document.getElementById('edit_schedule_id').value = '';
            document.getElementById('edit_student_id').value = '';
            document.getElementById('edit_first_name').value = '';
            document.getElementById('edit_last_name').value = '';
            document.getElementById('edit_round_num').value = '';
            document.getElementById('edit_round_num_select').value = '1';
            document.getElementById('edit_is_booking').value = '';
            document.getElementById('edit_details').value = '';
        });

        scheduleForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const scheduleId = scheduleIdInput.value ? Number(scheduleIdInput.value) : null;
            const startTime = normalizeTimeValue(startTimeInput.value);
            const endTime = normalizeTimeValue(endTimeInput.value);

            const payload = {
                action: scheduleId ? 'update_schedule' : 'create_schedule',
                schedule_id: scheduleId,
                teacher_id: Number(document.getElementById('teacher_id').value),
                schedule_date: document.getElementById('schedule_date').value,
                start_time: startTime,
                end_time: endTime,
                note: document.getElementById('note').value.trim(),
            };

            if (!payload.teacher_id) {
                setAlert('กรุณาเลือกอาจารย์');
                return;
            }

            if (!payload.schedule_date || !payload.start_time || !payload.end_time) {
                setAlert('กรุณากรอกวันที่และเวลาให้ครบถ้วน');
                return;
            }

            try {
                const response = await fetch('api/teachers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึก');
                }

                setAlert(result.message || 'บันทึกตารางงานเรียบร้อยแล้ว', 'success');
                resetForm();
                await loadSchedules();
            } catch (error) {
                setAlert(error.message || 'เกิดข้อผิดพลาด');
            }
        });

        startTimeInput.value = '';
        endTimeInput.value = '';
        editFormAlert.classList.add('d-none');
        setDefaultScheduleDate();
        setFormMode(false);
        loadSchedules();
    </script>
</body>
</html>
