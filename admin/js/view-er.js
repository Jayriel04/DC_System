document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btnEditHealth');
    if (!btn) return;

    btn.addEventListener('click', function () {
        // Use Bootstrap's modal functionality if available
        if (typeof $ !== 'undefined' && typeof $.fn.modal === 'function') {
            $('#adminHealthModal').modal('show');
        } else if (typeof bootstrap !== 'undefined') {
            const myModal = new bootstrap.Modal(document.getElementById('adminHealthModal'));
            myModal.show();
        } else {
            console.error('Bootstrap modal JS not found.');
        }
    });
});