document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('medicalHistoryModal');
    if (!modal) return;

    const openBtn = document.getElementById('btnEditHealth');
    const closeBtn = modal.querySelector('.close');

    function openModal() {
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    if (openBtn) {
        openBtn.addEventListener('click', openModal);
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    window.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
});