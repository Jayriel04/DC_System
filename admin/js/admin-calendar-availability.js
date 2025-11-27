document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('edit_appointment_date');
    
    if (!dateInput) return;

    // Create calendar structure
    const calendarWrapper = document.createElement('div');
    calendarWrapper.id = 'adminCalendarWrapper';
    calendarWrapper.className = 'admin-calendar-wrapper';
    
    calendarWrapper.innerHTML = `
        <div class="admin-calendar-header">
            <h3 id="adminMonthYear">Month Year</h3>
            <div>
                <button type="button" id="adminPrevBtn" class="admin-calendar-nav-btn">←</button>
                <button type="button" id="adminNextBtn" class="admin-calendar-nav-btn">→</button>
            </div>
        </div>
        <div class="admin-calendar-weekdays">
            <div class="admin-calendar-weekday">Mon</div>
            <div class="admin-calendar-weekday">Tue</div>
            <div class="admin-calendar-weekday">Wed</div>
            <div class="admin-calendar-weekday">Thu</div>
            <div class="admin-calendar-weekday">Fri</div>
            <div class="admin-calendar-weekday">Sat</div>
            <div class="admin-calendar-weekday">Sun</div>
        </div>
        <div class="admin-calendar-days" id="adminDaysContainer"></div>
        <div class="admin-calendar-legend">
            <div class="admin-calendar-legend-item">
                <div class="admin-calendar-legend-dot available"></div>
                <span>Available</span>
            </div>
            <div class="admin-calendar-legend-item">
                <div class="admin-calendar-legend-dot unavailable"></div>
                <span>Unavailable</span>
            </div>
        </div>
    `;

    // Insert calendar after date input
    const formGroup = dateInput.closest('.form-group');
    if (formGroup) {
        formGroup.appendChild(calendarWrapper);
    }

    // Calendar variables
    let currentDate = new Date();
    let selectedDate = null;
    let availableDates = [];

    const months = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

    const monthYearEl = document.getElementById('adminMonthYear');
    const daysContainer = document.getElementById('adminDaysContainer');
    const prevBtn = document.getElementById('adminPrevBtn');
    const nextBtn = document.getElementById('adminNextBtn');

    // Function to fetch availability data
    async function fetchAvailability(year, month) {
        try {
            // Using manage-patient.php with get_month_availability parameter
            const response = await fetch(`manage-patient.php?get_month_availability=1&year=${year}&month=${month}`);
            if (!response.ok) {
                console.error('Network response was not ok');
                availableDates = [];
                return;
            }
            const data = await response.json();
            availableDates = data.available || [];
        } catch (error) {
            console.error('Error fetching availability:', error);
            availableDates = [];
        }
    }

    // Function to render calendar
    async function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        monthYearEl.textContent = `${months[month]} ${year}`;

        // Fetch availability data
        await fetchAvailability(year, month + 1);

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const prevLastDay = new Date(year, month, 0);

        let firstDayOfWeek = firstDay.getDay();
        firstDayOfWeek = firstDayOfWeek === 0 ? 6 : firstDayOfWeek - 1;

        const lastDate = lastDay.getDate();
        const prevLastDate = prevLastDay.getDate();

        daysContainer.innerHTML = '';

        // Previous month days
        for (let i = firstDayOfWeek; i > 0; i--) {
            const day = document.createElement('div');
            day.className = 'admin-calendar-day other-month';
            day.textContent = prevLastDate - i + 1;
            daysContainer.appendChild(day);
        }

        // Current month days
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        for (let i = 1; i <= lastDate; i++) {
            const day = document.createElement('div');
            day.className = 'admin-calendar-day';
            day.textContent = i;

            const thisDate = new Date(year, month, i);
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;

            // Check if date is in the past
            if (thisDate < today) {
                day.classList.add('disabled');
            } else {
                // Add click event to all non-past dates (both available and unavailable)
                day.addEventListener('click', (e) => {
                    selectDate(dateStr, day);
                });
            }

            // Mark today
            if (today.getTime() === thisDate.getTime()) {
                day.classList.add('today');
            }

            // Mark selected date
            if (selectedDate === dateStr) {
                day.classList.add('selected');
            }

            // Add availability classes for visual indication
            if (!day.classList.contains('disabled')) {
                if (availableDates.includes(dateStr)) {
                    day.classList.add('available');
                } else {
                    day.classList.add('unavailable');
                }
            }

            daysContainer.appendChild(day);
        }

        // Fill remaining cells
        const totalCells = daysContainer.children.length;
        const remainingCells = 42 - totalCells;

        for (let i = 1; i <= remainingCells; i++) {
            const day = document.createElement('div');
            day.className = 'admin-calendar-day other-month';
            day.textContent = i;
            daysContainer.appendChild(day);
        }
    }

    // Function to select date
    function selectDate(dateStr, dayElement) {
        selectedDate = dateStr;
        dateInput.value = dateStr;

        // Trigger change event
        const event = new Event('change', { bubbles: true });
        dateInput.dispatchEvent(event);

        // Re-render and hide
        renderCalendar();
        calendarWrapper.classList.remove('show');
    }

    // Event listeners
    prevBtn.addEventListener('click', (e) => {
        e.preventDefault();
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    nextBtn.addEventListener('click', (e) => {
        e.preventDefault();
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    dateInput.addEventListener('focus', () => {
        calendarWrapper.classList.add('show');
        renderCalendar();
    });

    // Hide calendar when clicking outside
    document.addEventListener('click', (e) => {
        if (!calendarWrapper.contains(e.target) && e.target !== dateInput) {
            calendarWrapper.classList.remove('show');
        }
    });

    // Initial render
    renderCalendar();
});
