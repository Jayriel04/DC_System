document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('editScheduleModal');
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
        if (e.target.classList.contains('edit-schedule-btn')) {
            const button = e.target;
            // Populate modal fields
            document.getElementById('edit_schedule_id').value = button.dataset.id;
            document.getElementById('edit_firstname').value = button.dataset.firstname;
            document.getElementById('edit_surname').value = button.dataset.surname;
            document.getElementById('edit_service').value = button.dataset.service;
            document.getElementById('edit_date').value = button.dataset.date;
            document.getElementById('edit_time').value = button.dataset.time;
            document.getElementById('edit_duration').value = button.dataset.duration;
            
            const statusSelect = document.getElementById('edit_status');
            // Clear previous selections and select the correct option
            Array.from(statusSelect.options).forEach(option => {
                option.selected = (option.value === button.dataset.status);
            });

            openModal();
        }
    });

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
});