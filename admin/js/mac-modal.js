document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('editAppointmentModal');
    if (!modal) return;

    const closeBtn = modal.querySelector('.close-button');
    const cancelBtn = modal.querySelector('.btn-cancel');

    function openModal() {
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    // Event delegation for edit buttons
    document.getElementById('appointment-table-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-appointment-btn')) {
            const button = e.target;
            // Populate modal fields
            document.getElementById('edit_appointment_id').value = button.dataset.id;
            document.getElementById('edit_firstname').value = button.dataset.firstname;
            document.getElementById('edit_surname').value = button.dataset.surname;
            document.getElementById('edit_date').value = button.dataset.date;
            document.getElementById('edit_start_time').value = button.dataset.startTime;
            document.getElementById('edit_end_time').value = button.dataset.endTime;
            
            const statusSelect = document.getElementById('edit_status');
            // Clear previous selections
            Array.from(statusSelect.options).forEach(option => option.selected = false);
            // Select the correct option
            const currentStatus = button.dataset.status;
            const optionToSelect = Array.from(statusSelect.options).find(option => option.value === currentStatus);
            if (optionToSelect) {
                optionToSelect.selected = true;
            }

            openModal();
        }
    });

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
});