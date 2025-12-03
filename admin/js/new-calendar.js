document.addEventListener('DOMContentLoaded', function () {
    var editModal = new bootstrap.Modal(document.getElementById('editEventModal'));
    var addModal = new bootstrap.Modal(document.getElementById('addScheduleModal'));

    document.querySelectorAll('.edit-event-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            var dataset = this.dataset;
            document.getElementById('modal_event_id').value = dataset.id;
            document.getElementById('modal_date').value = dataset.date;
            document.getElementById('modal_start').value = dataset.start;
            document.getElementById('modal_end').value = dataset.end;
            editModal.show();
        });
    });

    document.getElementById('addScheduleBtn').addEventListener('click', function(e) {
        e.preventDefault();
        addModal.show();
    });
});