document.addEventListener('DOMContentLoaded', function () {
    const daysContainer = document.getElementById('daysContainer');
    let currentYear, currentMonth;

    // Function to fetch availability and update calendar UI
    async function updateCalendarAvailability(year, month) {
        currentYear = year;
        currentMonth = month;

        try {
            const response = await fetch(`profile.php?get_month_availability=1&year=${year}&month=${month}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const data = await response.json();
            const availableDates = data.available || [];

            // Wait for the calendar to be rendered by interactive-calendar.js
            // We use a short delay and check for the presence of day elements
            setTimeout(() => {
                const dayElements = daysContainer.querySelectorAll('.day');
                dayElements.forEach(dayEl => {
                    // Skip empty day slots or days from other months
                    if (dayEl.textContent === '' || dayEl.classList.contains('other-month')) return; 

                    const day = parseInt(dayEl.textContent, 10);
                    const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

                    dayEl.classList.remove('available', 'unavailable'); // Reset classes

                    // Check if the date is in the past. If so, it should already have the 'disabled' class.
                    // We will also add 'unavailable' to ensure it gets the red styling if it's not already styled.
                    if (dayEl.classList.contains('disabled')) {
                        dayEl.classList.add('unavailable');
                        return; // Don't process past dates further
                    }

                    if (availableDates.includes(dateStr)) {
                        dayEl.classList.add('available');
                    } else {
                        dayEl.classList.add('unavailable');
                    }
                });
            }, 100); // A small delay to ensure DOM is updated

        } catch (error) {
            console.error('Error fetching month availability:', error);
        }
    }

    // Listen for custom event from interactive-calendar.js
    document.addEventListener('calendarUpdated', function (e) {
        const { year, month } = e.detail;
        updateCalendarAvailability(year, month);
    });
});