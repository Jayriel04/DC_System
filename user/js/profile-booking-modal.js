document.addEventListener('DOMContentLoaded', function () {
    const bookAppointmentBtn = document.getElementById('bookAppointmentBtn');
    const bookingModal = document.getElementById('bookAppointmentModal');
    const closeModalBtns = document.querySelectorAll('#bookAppointmentModal .close, #bookAppointmentModal [data-dismiss="modal"]');

    if (bookAppointmentBtn && bookingModal) {
        // Show the modal when the book appointment button is clicked
        bookAppointmentBtn.addEventListener('click', function (e) {
            e.preventDefault(); // Prevent default link behavior if it's an 'a' tag
            bookingModal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        });

        // Function to close the modal
        const closeModal = function() {
            if (bookingModal) {
                bookingModal.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
            }
        };

        // Hide the modal when any close button is clicked
        closeModalBtns.forEach(btn => btn.addEventListener('click', closeModal));

        // Hide the modal when clicking outside of the modal content
        window.addEventListener('click', function (event) {
            if (event.target === bookingModal) {
                closeModal();
            }
        });

        // Hide modal on 'Escape' key press
        document.addEventListener('keydown', function (event) {
            if (event.key === "Escape") {
                closeModal();
            }
        });
    }
});