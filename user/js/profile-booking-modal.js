document.addEventListener("DOMContentLoaded", function() {
    const bookAppointmentBtn = document.getElementById("bookAppointmentBtn");
    const bookingModal = document.getElementById("bookAppointmentModal");
    const dateInput = document.getElementById("appointment_date");
    const timeSelect = document.getElementById("appointment_time_modal");
    const calendarWrapper = document.getElementById("calendarWrapper");

    console.log("Elements found:", {
        bookAppointmentBtn: bookAppointmentBtn ? "yes" : "no",
        bookingModal: bookingModal ? "yes" : "no",
        dateInput: dateInput ? "yes" : "no",
        timeSelect: timeSelect ? "yes" : "no",
        calendarWrapper: calendarWrapper ? "yes" : "no"
    });

    if (bookAppointmentBtn && bookingModal) {
        
        bookAppointmentBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Opening modal');
            // Use flex centering to ensure modal content is visible and centered
            bookingModal.style.display = 'flex';
            bookingModal.style.alignItems = 'center';
            bookingModal.style.justifyContent = 'center';
            bookingModal.style.padding = '20px';
            // Ensure body doesn't scroll when modal open
            document.body.style.overflow = 'hidden';
            // also add a show class so any CSS targeting .modal.show works
            bookingModal.classList.add('show');
        });

        const closeModal = function() {
            console.log('Closing modal');
            bookingModal.style.display = 'none';
            bookingModal.classList.remove('show');
            document.body.style.overflow = '';
            if (calendarWrapper) {
                calendarWrapper.style.display = 'none';
            }
        };

        // Attach close handlers to buttons inside this modal
        console.log('bookingModal close buttons count:', bookingModal.querySelectorAll('.close, [data-dismiss="modal"]').length);
        const closeButtons = bookingModal.querySelectorAll('.close, [data-dismiss="modal"]');
        closeButtons.forEach(function(btn) {
            console.log('attaching close handler to', btn);
            btn.addEventListener('click', function(e) {
                console.log('bookingModal close button clicked', btn, e);
                closeModal();
            });
        });

        window.addEventListener("click", function(e) {
            if (e.target === bookingModal) {
                closeModal();
            }
        });

        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                closeModal();
            }
        });
    }

    if (dateInput) {
        dateInput.addEventListener("change", function() {
            const selectedDate = this.value;
            console.log("Date changed:", selectedDate);

            if (!timeSelect) return;

            if (!selectedDate) {
                timeSelect.innerHTML = "<option value=\"\">-- Select a date first --</option>";
                return;
            }

            timeSelect.innerHTML = "<option value=\"\">-- Loading times --</option>";
            
            fetch("profile.php?get_calendar_times=1&date=" + encodeURIComponent(selectedDate))
                .then(function(response) {
                    if (!response.ok) throw new Error("Network response was not ok");
                    return response.json();
                })
                .then(function(data) {
                    console.log("Time slots received:", data);
                    timeSelect.innerHTML = "<option value=\"\">-- Select a time --</option>";
                    
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(function(slot) {
                            const option = document.createElement("option");
                            option.value = slot.start;
                            option.textContent = slot.label;
                            timeSelect.appendChild(option);
                        });
                    } else {
                        timeSelect.innerHTML = "<option value=\"\">-- No available times --</option>";
                    }
                })
                .catch(function(error) {
                    console.error("Error:", error);
                    timeSelect.innerHTML = "<option value=\"\">-- Error loading times --</option>";
                });
        });
    }

    const bookingForm = bookingModal ? bookingModal.querySelector("form") : null;
    if (bookingForm) {
        bookingForm.addEventListener("submit", function(e) {
            if (!dateInput.value || !timeSelect.value) {
                e.preventDefault();
                alert("Please select both a date and time for your appointment.");
            }
        });
    }
});
