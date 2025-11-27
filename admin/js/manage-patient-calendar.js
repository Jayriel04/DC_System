document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('edit_app_date');
    const timeSelect = document.getElementById('edit_start_time');
    const calendarWrapper = document.getElementById('editCalendarWrapper');

    if (!dateInput || !timeSelect || !calendarWrapper) {
        console.error('Calendar elements not found in the edit modal.');
        return;
    }

    // Initialize the interactive calendar for the edit modal
    const calendar = new InteractiveCalendar({
        element: calendarWrapper,
        dateInput: dateInput,
        timeSelect: timeSelect,
        navButtons: {
            prev: document.getElementById('editPrevBtn'),
            next: document.getElementById('editNextBtn'),
        },
        monthYearEl: document.getElementById('editMonthYear'),
        daysContainer: document.getElementById('editDaysContainer'),
    });

    // Initialize the availability fetcher for this calendar instance
    const availability = new CalendarAvailability({
        calendarInstance: calendar,
        availabilityUrl: 'manage-patient.php?get_month_availability=1',
        daysContainer: document.getElementById('editDaysContainer'),
    });

    // Initialize the time slot fetcher for this calendar instance
    const timeSlots = new CalendarTimeSlots({
        calendarInstance: calendar,
        timeSelect: timeSelect,
        timeSlotsUrl: 'manage-patient.php?get_calendar_times=1',
    });

    // Show/hide calendar on date input click
    dateInput.addEventListener('click', () => calendar.toggle());
});