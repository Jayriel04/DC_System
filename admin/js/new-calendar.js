document.addEventListener('DOMContentLoaded', function () {
    // delegate click events for edit buttons
    document.querySelector('.calendar-container').addEventListener('click', function(e) {
        if (e.target.matches('.edit-event-btn')) {
            const btn = e.target;
            const id = btn.getAttribute('data-id');
            const date = btn.getAttribute('data-date');
            const start = btn.getAttribute('data-start');
            const end = btn.getAttribute('data-end');
            
            document.getElementById('modal_event_id').value = id;
            document.getElementById('modal_date').value = date;
            document.getElementById('modal_start').value = start;
            document.getElementById('modal_end').value = end;

            // Show bootstrap modal
            const editModal = new bootstrap.Modal(document.getElementById('editEventModal'));
            editModal.show();
        }
    });
});