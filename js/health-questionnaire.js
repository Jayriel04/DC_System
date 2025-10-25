document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('healthModal');
    if (modal) {
        modal.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox') {
                var formGroup = e.target.closest('.form-group');
                if (formGroup) formGroup.classList.toggle('highlight', e.target.checked);
            }
        });
    }
});