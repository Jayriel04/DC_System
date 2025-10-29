document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('appointment_date');
    const calendarWrapper = document.getElementById('calendarWrapper');
    const monthYearEl = document.getElementById('monthYear');
    const daysContainer = document.getElementById('daysContainer');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    if (!dateInput || !calendarWrapper) return;

    // Make currentDate global so other scripts can access it
    window.currentDate = new Date();
    let selectedDate = null;

    const months = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        monthYearEl.textContent = `${months[month]} ${year}`;

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const prevLastDay = new Date(year, month, 0);

        let firstDayOfWeek = firstDay.getDay();
        firstDayOfWeek = firstDayOfWeek === 0 ? 6 : firstDayOfWeek - 1; // Monday is 0

        const lastDate = lastDay.getDate();
        const prevLastDate = prevLastDay.getDate();

        daysContainer.innerHTML = '';

        // Previous month days
        for (let i = firstDayOfWeek; i > 0; i--) {
            const day = document.createElement('div');
            day.className = 'day other-month';
            day.textContent = prevLastDate - i + 1;
            daysContainer.appendChild(day);
        }

        // Current month days
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        for (let i = 1; i <= lastDate; i++) {
            const day = document.createElement('div');
            day.className = 'day';
            day.textContent = i;

            const thisDate = new Date(year, month, i);
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;

            if (thisDate < today) {
                day.classList.add('disabled');
            } else {
                day.addEventListener('click', () => selectDate(dateStr, day));
            }

            if (today.getTime() === thisDate.getTime()) {
                day.classList.add('today');
            }

            if (selectedDate === dateStr) {
                day.classList.add('selected');
            }

            daysContainer.appendChild(day);
        }

        // Next month days to fill grid
        const totalCells = daysContainer.children.length;
        const remainingCells = 42 - totalCells;

        for (let i = 1; i <= remainingCells; i++) {
            const day = document.createElement('div');
            day.className = 'day other-month';
            day.textContent = i;
            daysContainer.appendChild(day);
        }
    }

    function selectDate(dateStr, dayElement) {
        selectedDate = dateStr;
        dateInput.value = dateStr;

        // Trigger change event for other scripts to pick up
        const event = new Event('change', { bubbles: true });
        dateInput.dispatchEvent(event);

        // Re-render to show selection and then hide
        renderCalendar();
        calendarWrapper.style.display = 'none';
    }

    prevBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    nextBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    dateInput.addEventListener('focus', () => {
        calendarWrapper.style.display = 'block';
        renderCalendar();
    });

    // Optional: Hide calendar if clicked outside
    document.addEventListener('click', (e) => {
        if (!calendarWrapper.contains(e.target) && e.target !== dateInput) {
            calendarWrapper.style.display = 'none';
        }
    });

    renderCalendar();
});