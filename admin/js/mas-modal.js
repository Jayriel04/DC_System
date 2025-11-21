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
        const button = e.target.closest('.edit-schedule-btn');
        if (button && button.classList.contains('edit-schedule-btn')) {
            // Populate modal fields
            document.getElementById('edit_schedule_id').value = button.dataset.id;
            document.getElementById('edit_firstname').value = button.dataset.firstname;
            document.getElementById('edit_surname').value = button.dataset.surname;
            document.getElementById('edit_service').value = button.dataset.service;
            document.getElementById('edit_date').value = button.dataset.date;
            document.getElementById('edit_time').value = button.dataset.time;
            
            const statusSelect = document.getElementById('edit_status');
            const cancelReasonGroup = document.getElementById('cancel_reason_group');
            const cancelReasonTextarea = document.getElementById('edit_cancel_reason');

            // If edit button is clicked
                document.getElementById('edit_duration').value = button.dataset.duration;
                const currentStatus = button.dataset.status;
                statusSelect.value = currentStatus;

                if (currentStatus === 'Cancelled' || currentStatus === 'For Cancellation') {
                    cancelReasonGroup.style.display = 'block';
                    cancelReasonTextarea.value = button.dataset.cancelReason;
                } else {
                    cancelReasonGroup.style.display = 'none';
                    cancelReasonTextarea.value = '';
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