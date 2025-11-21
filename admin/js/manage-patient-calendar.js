document.addEventListener("DOMContentLoaded", function () {
    const editModal = document.getElementById('editPatientModal');
    if (!editModal) return;

    const dateInput = editModal.querySelector("#edit_app_date");
    const timeSelect = editModal.querySelector("#edit_start_time");
    const calendarWrapper = editModal.querySelector("#editCalendarWrapper");

    if (!dateInput || !timeSelect || !calendarWrapper) {
        console.error("One or more calendar elements are missing in the edit modal.");
        return;
    }

    // --- Calendar Initialization ---
    const calendar = new InteractiveCalendar({
        target: calendarWrapper,
        date_input: dateInput,
        time_select: timeSelect,
        month_header: editModal.querySelector('#editMonthYear'),
        prev_btn: editModal.querySelector('#editPrevBtn'),
        next_btn: editModal.querySelector('#editNextBtn'),
        days_container: editModal.querySelector('#editDaysContainer'),
    });

    // --- Event Listeners ---

    // Toggle calendar visibility when date input is clicked
    dateInput.addEventListener("click", function () {
        calendarWrapper.style.display = (calendarWrapper.style.display === "block") ? "none" : "block";
        if (calendarWrapper.style.display === "block") {
            fetchMonthAvailability();
        }
    });

    // Fetch times when the date is changed by the calendar
    dateInput.addEventListener("change", function () {
        fetchAvailableTimes(this.value);
    });

    // Fetch availability when calendar month changes
    calendarWrapper.addEventListener('month-changed', fetchMonthAvailability);

    // --- AJAX Functions ---

    function fetchMonthAvailability() {
        const month = calendar.getCurrentMonth();
        const year = calendar.getCurrentYear();

        fetch(`manage-patient.php?get_month_availability=1&month=${month}&year=${year}`)
            .then(response => {
                if (!response.ok) throw new Error("Network response was not ok");
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    console.error("Error fetching availability:", data.error);
                    return;
                }
                calendar.setAvailableDates(data.available || []);
                calendar.render(); // Re-render to show available dates
            })
            .catch(error => {
                console.error("Error fetching month availability:", error);
            });
    }

    function fetchAvailableTimes(selectedDate) {
        if (!selectedDate) {
            timeSelect.innerHTML = "<option value=''>-- Select a date first --</option>";
            return;
        }

        timeSelect.innerHTML = "<option value=''>-- Loading times --</option>";
        timeSelect.disabled = true;

        fetch(`manage-patient.php?get_calendar_times=1&date=${encodeURIComponent(selectedDate)}`)
            .then(response => {
                if (!response.ok) throw new Error("Network response was not ok");
                return response.json();
            })
            .then(data => {
                timeSelect.innerHTML = ""; // Clear loading message
                if (data.error) {
                    console.error("Error fetching times:", data.error);
                    timeSelect.innerHTML = "<option value=''>-- Error loading --</option>";
                } else if (Array.isArray(data) && data.length > 0) {
                    timeSelect.innerHTML = "<option value=''>-- Select a time --</option>";
                    data.forEach(slot => {
                        const option = document.createElement("option");
                        option.value = slot.start;
                        option.textContent = slot.label;
                        timeSelect.appendChild(option);
                    });
                } else {
                    timeSelect.innerHTML = "<option value=''>-- No available times --</option>";
                }
            })
            .catch(error => {
                console.error("Error fetching available times:", error);
                timeSelect.innerHTML = "<option value=''>-- Error loading times --</option>";
            })
            .finally(() => {
                timeSelect.disabled = false;
            });
    }
});