document.addEventListener('DOMContentLoaded', function () {
    const editMedicalBtn = document.getElementById('editMedicalHistoryBtn');
    const medicalModal = document.getElementById('medicalHistoryModal');
    // Select all elements that can close THIS modal (the 'x' and the 'Cancel' button)
    const closeModalBtns = medicalModal ? medicalModal.querySelectorAll('.close, [data-dismiss="modal"]') : [];

    if (editMedicalBtn && medicalModal) {
        // Show the modal when the edit button is clicked
        editMedicalBtn.addEventListener('click', function () {
            medicalModal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        });

        // Function to close the modal
        const closeModal = function() {
            if (medicalModal) {
                medicalModal.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
            }
        };

        // Add click event to all close buttons (like 'x' and 'Cancel')
        closeModalBtns.forEach(function(btn) {
            btn.addEventListener('click', closeModal);
        });

        // Hide the modal when clicking outside of the modal content
        window.addEventListener('click', function (event) {
            if (event.target === medicalModal) {
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