document.addEventListener('DOMContentLoaded', function () {
    const editProfileBtn = document.getElementById('editProfileBtn');
    const profileModal = document.getElementById('editProfileModal');
    const closeModalBtns = document.querySelectorAll('#editProfileModal .close, #editProfileModal [data-dismiss="modal"]');

    if (editProfileBtn && profileModal) {
        // Show the modal when the edit button is clicked
        editProfileBtn.addEventListener('click', function () {
            profileModal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        });

        // Function to close the modal
        const closeModal = function() {
            if (profileModal) {
                profileModal.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
            }
        };

        // Hide the modal when any close button is clicked
        closeModalBtns.forEach(btn => btn.addEventListener('click', closeModal));

        // Hide the modal when clicking outside of the modal content
        window.addEventListener('click', function (event) {
            if (event.target === profileModal) {
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