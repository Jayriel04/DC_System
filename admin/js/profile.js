function openModal() {
    document.getElementById('editModal').classList.add('active');
}

function closeModal() {
    document.getElementById('editModal').classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function() {
    const editButton = document.getElementById('editProfileBtn');
    if (editButton) {
        editButton.addEventListener('click', openModal);
    }

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this || e.target.classList.contains('close-btn') || e.target.classList.contains('btn-cancel')) {
            closeModal();
        }
    });
});