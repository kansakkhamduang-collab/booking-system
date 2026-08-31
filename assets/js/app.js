document.addEventListener('DOMContentLoaded', function () {
    const calendarElement = document.getElementById('calendar');
    const bookingModalElement = document.getElementById('bookingModal');
    const bookingModal = new bootstrap.Modal(bookingModalElement);
    const eventDetailModalElement = document.getElementById('eventDetailModal');
    const eventDetailModal = new bootstrap.Modal(eventDetailModalElement);
    const bookingForm = document.getElementById('bookingForm');
    const alertBox = document.getElementById('formAlert');
    const studentSelect = document.getElementById('student_select');
    const startTimeSelect = document.getElementById('start_time');
    const endTimeSelect = document.getElementById('end_time');
    const teacherIdInput = document.getElementById('teacher_id');

    const detailStudentName = document.getElementById('detailStudentName');
    const detailStudentId = document.getElementById('detailStudentId');
    const detailRound = document.getElementById('detailRound');
    const detailTime = document.getElementById('detailTime');
    const detailDescription = document.getElementById('detailDescription');

    const buildTimeOptions = (selectElement, defaultValue, startAt = '09:00', endAt = '10:00') => {
        selectElement.innerHTML = '<option value="">-- เลือกเวลา --</option>';

        for (let hour = 0; hour < 24; hour += 1) {
            for (let minute = 0; minute < 60; minute += 30) {
                const value = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                if (value === defaultValue) {
                    option.selected = true;
                }
                selectElement.appendChild(option);
            }
        }

        if (defaultValue) {
            selectElement.value = defaultValue;
        }

        if (!defaultValue && startAt) {
            selectElement.value = startAt;
        }

        if (defaultValue === '' && endAt) {
            selectElement.value = endAt;
        }
    };

    const setAlert = (message, type = 'danger') => {
        alertBox.className = 'alert d-none';
        alertBox.textContent = '';
        alertBox.classList.remove('d-none');
        alertBox.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
        alertBox.textContent = message;
    };

    const updateStudentFields = (selectedOption) => {
        if (!selectedOption || !selectedOption.value) {
            document.getElementById('student_id').value = '';
            document.getElementById('student_name_display').value = '';
            document.getElementById('first_name').value = '';
            document.getElementById('last_name').value = '';
            return;
        }

        const firstName = selectedOption.dataset.firstName || '';
        const lastName = selectedOption.dataset.lastName || '';

        document.getElementById('student_id').value = selectedOption.value;
        document.getElementById('student_name_display').value = `${firstName} ${lastName}`.trim();
        document.getElementById('first_name').value = firstName;
        document.getElementById('last_name').value = lastName;
    };

    const setDefaultTeacher = () => {
        teacherIdInput.value = '1';
        const teacherNameDisplay = document.getElementById('teacher_name_display');
        if (teacherNameDisplay) {
            teacherNameDisplay.value = 'อาจารย์ประจำภาควิชา';
        }
    };

    const loadStudents = async () => {
        try {
            const response = await fetch('api/students.php');
            if (!response.ok) {
                throw new Error('ไม่สามารถโหลดรายชื่อนักศึกษาได้');
            }

            const students = await response.json();
            studentSelect.innerHTML = '<option value="">-- เลือกนักศึกษา --</option>';

            students.forEach((student) => {
                const option = document.createElement('option');
                option.value = student.student_id;
                option.dataset.firstName = student.first_name;
                option.dataset.lastName = student.last_name;
                option.textContent = `${student.full_name} (${student.student_id})`;
                studentSelect.appendChild(option);
            });

            if (students.length === 0) {
                const warning = document.createElement('option');
                warning.value = '';
                warning.disabled = true;
                warning.textContent = 'ยังไม่มีข้อมูลนักศึกษาในระบบ';
                studentSelect.appendChild(warning);
            }
        } catch (error) {
            setAlert(error.message || 'เกิดข้อผิดพลาดในการโหลดรายชื่อนักศึกษา');
        }
    };

    const showEventDetails = (event) => {
        const isWorkload = Boolean(event.extendedProps.is_workload);
        const round = Number(event.extendedProps.round_num || 1);
        const start = event.start ? new Date(event.start) : null;
        const end = event.end ? new Date(event.end) : null;

        const formatDateTime = (dateValue) => {
            if (!dateValue) return 'ไม่ระบุ';
            const date = new Date(dateValue);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear() + 543;
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${day}/${month}/${year} ${hours}:${minutes}`;
        };

        if (isWorkload) {
            detailStudentName.textContent = 'ภาระงานอาจารย์';
            detailStudentId.textContent = `อาจารย์ ID ${event.extendedProps.teacher_id || 'ไม่ระบุ'}`;
            detailRound.textContent = 'ภาระงาน';
            detailTime.textContent = `${formatDateTime(start)} - ${formatDateTime(end)}`;
            detailDescription.textContent = event.extendedProps.note || event.extendedProps.details || 'ไม่มีรายละเอียดเพิ่มเติม';
            eventDetailModal.show();
            return;
        }

        const studentName = `${event.title.replace(/^รอบ\s+\d+\s+·\s*/, '')}`.trim();

        detailStudentName.textContent = studentName || 'ไม่ระบุ';
        detailStudentId.textContent = event.extendedProps.student_id || 'ไม่ระบุ';
        detailRound.textContent = `รอบ ${round}`;
        detailTime.textContent = `${formatDateTime(start)} - ${formatDateTime(end)}`;
        detailDescription.textContent = event.extendedProps.details || 'ไม่มีรายละเอียดเพิ่มเติม';
        eventDetailModal.show();
    };

    const resetForm = () => {
        bookingForm.reset();
        document.getElementById('booking_date').value = new Date().toISOString().split('T')[0];
        buildTimeOptions(startTimeSelect, '09:00');
        buildTimeOptions(endTimeSelect, '10:00');
        document.getElementById('round_num').value = '1';
        studentSelect.value = '';
        updateStudentFields(null);
        setDefaultTeacher();
    };

    const calendar = new FullCalendar.Calendar(calendarElement, {
        initialView: 'dayGridMonth',
        locale: 'th',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'วันนี้',
            month: 'เดือน',
            week: 'สัปดาห์',
            day: 'วัน',
            prev: 'ก่อนหน้า',
            next: 'ถัดไป'
        },
        events: 'api/bookings.php',
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        selectable: true,
        eventDisplay: 'block',
        dateClick: function (info) {
            document.getElementById('booking_date').value = info.dateStr;
            buildTimeOptions(startTimeSelect, '09:00');
            buildTimeOptions(endTimeSelect, '10:00');
            alertBox.classList.add('d-none');
            bookingModal.show();
        },
        eventDidMount: function (info) {
            const isWorkload = Boolean(info.event.extendedProps.is_workload);
            const round = Number(info.event.extendedProps.round_num || 1);
            const color = isWorkload ? '#dc2626' : (round === 1 ? '#f59e0b' : '#22c55e');
            const borderColor = isWorkload ? '#b91c1c' : (round === 1 ? '#d97706' : '#16a34a');

            info.el.style.backgroundColor = color;
            info.el.style.borderColor = color;
            info.el.style.borderLeft = '4px solid ' + borderColor;
            info.el.style.borderRadius = '8px';
            info.el.style.color = isWorkload ? '#ffffff' : '#111827';
            info.el.style.fontWeight = '700';
            info.el.style.padding = '4px 8px';
            info.el.style.boxShadow = 'none';
        },
        eventClick: function (info) {
            showEventDetails(info.event);
        }
    });

    calendar.render();
    loadStudents();
    setDefaultTeacher();
    resetForm();

    studentSelect.addEventListener('change', function () {
        updateStudentFields(this.options[this.selectedIndex]);
    });

    bookingModalElement.addEventListener('show.bs.modal', function () {
        studentSelect.focus();
    });

    bookingForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const payload = {
            teacher_id: document.getElementById('teacher_id').value || 1,
            student_id: document.getElementById('student_id').value.trim(),
            first_name: document.getElementById('first_name').value.trim(),
            last_name: document.getElementById('last_name').value.trim(),
            booking_date: document.getElementById('booking_date').value,
            start_time: document.getElementById('start_time').value,
            end_time: document.getElementById('end_time').value,
            round_num: document.getElementById('round_num').value,
            details: document.getElementById('details').value.trim()
        };

        if (!payload.student_id || !payload.first_name || !payload.last_name || !payload.booking_date || !payload.start_time || !payload.end_time) {
            setAlert('กรุณาเลือกนักศึกษาก่อนทำการจอง');
            return;
        }

        try {
            const response = await fetch('api/bookings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'เกิดข้อผิดพลาดในการจองคิว');
            }

            bookingModal.hide();
            calendar.refetchEvents();
            setAlert(result.message, 'success');
            bookingForm.reset();
            resetForm();
        } catch (error) {
            setAlert(error.message || 'เกิดข้อผิดพลาด');
        }
    });
});
