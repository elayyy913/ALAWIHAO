(() => {
    const maternalList = document.getElementById('maternalScheduleList');
    const infantList = document.getElementById('infantScheduleList');
    if (!maternalList || !infantList) return;

    const esc = value => String(value ?? '');
    const setText = (element, value) => {
        element.textContent = esc(value);
        return element;
    };

    function formatDate(date, time = '') {
        const parsedDate = new Date(date + 'T00:00:00');
        const formattedDate = Number.isNaN(parsedDate.getTime())
            ? date
            : new Intl.DateTimeFormat('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                timeZone: 'Asia/Manila'
            }).format(parsedDate);
        if (!time) return formattedDate;

        const parsedTime = new Date('1970-01-01T' + time);
        const formattedTime = Number.isNaN(parsedTime.getTime())
            ? time
            : new Intl.DateTimeFormat('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
                timeZone: 'Asia/Manila'
            }).format(parsedTime);
        return formattedDate + ' · ' + formattedTime;
    }

    function createActionForm(schedule, category, variant) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = variant === 'admin' ? '../process_verification.php' : '';
        form.className = 'sched-actions';

        const scheduleId = document.createElement('input');
        scheduleId.type = 'hidden';
        scheduleId.name = 'schedule_id';
        scheduleId.value = esc(schedule.id);
        form.appendChild(scheduleId);

        const redirect = document.createElement('input');
        redirect.type = 'hidden';
        redirect.name = 'redirect_to';
        redirect.value = variant === 'admin' ? 'admin_dashboard.php' : 'admin/super_admin_dashboard.php';
        form.appendChild(redirect);

        const isMaternal = category === 'Maternal';
        const doneButton = document.createElement('button');
        doneButton.type = 'submit';
        doneButton.name = isMaternal ? 'mark_done_maternal' : 'mark_done_infant';
        doneButton.className = variant === 'admin' ? 'btn-done' : 'btn-mark-done';
        doneButton.textContent = 'Mark Done';
        doneButton.setAttribute('formnovalidate', '');
        form.appendChild(doneButton);

        const rescheduleGroup = document.createElement('div');
        rescheduleGroup.className = variant === 'admin' ? 'resched-container' : 'resched-group';
        if (variant === 'admin') rescheduleGroup.style.marginTop = '0';

        const dateInput = document.createElement('input');
        dateInput.type = 'date';
        dateInput.name = 'new_date';
        dateInput.required = true;
        dateInput.className = variant === 'admin' ? 'resched-input' : 'date-input';
        rescheduleGroup.appendChild(dateInput);

        const rescheduleButton = document.createElement('button');
        rescheduleButton.type = 'submit';
        rescheduleButton.name = isMaternal ? 'reschedule_maternal' : 'reschedule_infant';
        rescheduleButton.className = 'btn-resched';
        rescheduleButton.textContent = 'Resched';
        rescheduleGroup.appendChild(rescheduleButton);
        form.appendChild(rescheduleGroup);
        return form;
    }

    function createScheduleCard(schedule, category, variant) {
        const isMaternal = category === 'Maternal';
        const card = document.createElement('div');
        card.className = variant === 'admin'
            ? 'sched-item' + (isMaternal ? '' : ' child-sched')
            : 'schedule-item ' + (isMaternal ? 'maternal-border' : 'infant-border');

        const header = document.createElement('div');
        header.className = variant === 'admin' ? 'schedule-meta' : 'sched-header-row';
        const date = document.createElement('strong');
        date.className = variant === 'admin' ? 'schedule-date' : 'sched-date';
        setText(date, formatDate(schedule.schedule_date, schedule.schedule_time));
        header.appendChild(date);

        if (variant === 'admin') {
            const categoryLabel = document.createElement('span');
            categoryLabel.className = 'schedule-category' + (isMaternal ? '' : ' child');
            setText(categoryLabel, isMaternal ? 'Maternal' : 'Infant');
            header.appendChild(categoryLabel);
        } else {
            const status = document.createElement('span');
            status.className = 'status-badge ' + esc(schedule.status).toLowerCase().replace(/\s+/g, '-');
            setText(status, schedule.status);
            header.appendChild(status);
        }
        card.appendChild(header);

        if (variant === 'admin') {
            const status = document.createElement('span');
            status.className = 'status-badge';
            setText(status, schedule.status);
            card.appendChild(status);
        }

        const patientName = document.createElement('strong');
        patientName.className = variant === 'admin' ? 'schedule-service' : 'sched-patient-name';
        setText(patientName, schedule.patient_name || (isMaternal ? 'Patient' : 'Child'));
        card.appendChild(patientName);

        const service = document.createElement('span');
        service.className = variant === 'admin' ? '' : 'sched-type';
        if (variant === 'admin') service.style.fontSize = '0.8rem';
        setText(service, (isMaternal ? 'Service: ' : 'Vaccine: ') + (schedule.service_type || (isMaternal ? 'Check-up' : 'General Vaccine')));
        card.appendChild(service);
        card.appendChild(createActionForm(schedule, category, variant));
        return card;
    }

    function renderList(list, schedules, category, variant) {
        if (list.querySelector('input[type="date"]:focus') ||
            Array.from(list.querySelectorAll('input[type="date"]')).some(input => input.value !== '')) {
            return;
        }

        const fragment = document.createDocumentFragment();
        if (schedules.length) {
            schedules.forEach(schedule => fragment.appendChild(createScheduleCard(schedule, category, variant)));
        } else {
            const empty = document.createElement(variant === 'admin' ? 'p' : 'div');
            empty.className = variant === 'admin' ? 'no-data' : 'dashboard-empty-state';
            if (variant === 'admin') empty.style.margin = '0';
            setText(empty, category === 'Maternal'
                ? 'No upcoming maternal appointments.'
                : 'No upcoming infant appointments.');
            fragment.appendChild(empty);
        }
        list.replaceChildren(fragment);
    }

    async function refreshSchedules() {
        try {
            const response = await fetch('dashboard_schedule_data.php', {
                credentials: 'same-origin',
                cache: 'no-store'
            });
            const data = await response.json();
            if (!response.ok || data.error) {
                throw new Error(data.error || 'Unable to refresh dashboard schedules.');
            }

            const variant = maternalList.dataset.variant;
            renderList(maternalList, data.maternal, 'Maternal', variant);
            renderList(infantList, data.infant, 'Child', variant);

            const maternalCount = document.getElementById('todayMaternalScheduleCount');
            const infantCount = document.getElementById('todayInfantScheduleCount');
            const todayCount = document.getElementById('todayScheduleCount');
            if (maternalCount) maternalCount.textContent = data.maternal.length;
            if (infantCount) infantCount.textContent = data.infant.length;
            if (todayCount) {
                todayCount.textContent = Number(data.today_maternal_count) + Number(data.today_infant_count);
            }
        } catch (error) {
            console.error('Dashboard schedule refresh failed:', error);
        }
    }

    refreshSchedules();
    window.setInterval(refreshSchedules, 5000);
})();
