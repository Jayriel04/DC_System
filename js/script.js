document.addEventListener('DOMContentLoaded', function () {
    // Smooth scroll for navigation links
    jQuery(document).ready(function ($) {
        $(".scroll").click(function (event) {
            event.preventDefault();
            $('html,body').animate({ scrollTop: $(this.hash).offset().top }, 900);
        });
    });

    // Initialize the responsive slider
    $(function () {
        $("#slider").responsiveSlides({
            auto: true,
            nav: true,
            speed: 500,
            namespace: "callbacks",
            pager: true,
        });
    });

    // Logic for the booking modal's time slots
    const dateInputModal = document.getElementById('appointment_date');
    const timeSelectModal = document.getElementById('appointment_time');

    function populateTimes(date) {
        if (!timeSelectModal) return;
        timeSelectModal.innerHTML = '<option value="">-- Loading times --</option>';
        if (!date) {
            timeSelectModal.innerHTML = '<option value="">-- Select a date first --</option>';
            return;
        }

        const url = `index.php?get_calendar_times=1&date=${encodeURIComponent(date)}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                timeSelectModal.innerHTML = '<option value="">-- Select a time --</option>';
                if (data.length > 0) {
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.start;
                        option.textContent = item.label;
                        timeSelectModal.appendChild(option);
                    });
                } else {
                    timeSelectModal.innerHTML = '<option value="">-- No available times --</option>';
                }
            })
            .catch(err => {
                timeSelectModal.innerHTML = '<option value="">-- Error loading times --</option>';
            });
    }

    if (dateInputModal) {
        dateInputModal.addEventListener('change', function () {
            populateTimes(this.value);
        });
    }

    // Logic for calendar day availability colors
    const calendarWrapper = document.getElementById('calendarWrapper');
    if (calendarWrapper) {
        const applyAvailabilityStyles = (month, year) => {
            const url = `index.php?get_month_availability=1&month=${month}&year=${year}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error('Error fetching availability:', data.error);
                        return;
                    }

                    const availableDates = data.available || [];
                    const days = calendarWrapper.querySelectorAll('.day:not(.other-month)');

                    days.forEach(day => {
                        const dayNumber = day.textContent;
                        if (!dayNumber) return;

                        const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(dayNumber).padStart(2, '0')}`;

                        day.classList.remove('available', 'fully-booked');

                        if (availableDates.includes(dateStr)) {
                            day.classList.add('available');
                        } else {
                            if (day.classList.contains('disabled')) return;
                            day.classList.add('fully-booked');
                        }
                    });
                })
                .catch(error => console.error('Failed to fetch month availability:', error));
        };

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'style' && calendarWrapper.style.display === 'block') {
                    // Access the global currentDate object from interactive-calendar.js
                    if (typeof window.currentDate !== 'undefined') {
                        const month = window.currentDate.getMonth() + 1;
                        const year = window.currentDate.getFullYear();
                        applyAvailabilityStyles(month, year);
                    }
                }
            });
        });

        observer.observe(calendarWrapper, { attributes: true });

        document.getElementById('prevBtn').addEventListener('click', () => {
            // Use a short delay to allow the calendar to update its date first
            setTimeout(() => {
                if (typeof window.currentDate !== 'undefined') {
                    applyAvailabilityStyles(window.currentDate.getMonth() + 1, window.currentDate.getFullYear());
                }
            }, 100);
        });
        document.getElementById('nextBtn').addEventListener('click', () => {
            setTimeout(() => {
                if (typeof window.currentDate !== 'undefined') {
                    applyAvailabilityStyles(window.currentDate.getMonth() + 1, window.currentDate.getFullYear());
                }
            }, 100);
        });
    }
});

// Scroll to hash on page load
window.addEventListener('load', function () {
    if (window.location.hash) {
        setTimeout(function () {
            try {
                var el = document.querySelector(window.location.hash);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    var id = window.location.hash.substring(1);
                    var byId = document.getElementById(id);
                    if (byId) byId.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } catch (e) {
                // ignore errors
            }
        }, 10);
    }
});