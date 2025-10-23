document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('serviceModal');
    const addBtn = document.getElementById('addServiceBtn');
    const closeBtns = document.querySelectorAll('.close-btn, .btn-secondary');

    // Function to open the modal for adding a new service
    if (addBtn) {
        addBtn.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('modalTitle').textContent = 'Add New Service';
            document.getElementById('serviceForm').action = 'add-service.php'; // Point form to add script
            document.getElementById('serviceForm').reset();
            document.getElementById('serviceId').value = ''; // Clear any existing ID
            modal.classList.add('active');
        });
    }

    // Function to open the modal for editing an existing service
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const service = {
                id: this.dataset.id,
                name: this.dataset.name,
                description: this.dataset.description,
            };

            document.getElementById('modalTitle').textContent = 'Edit Service';
            document.getElementById('serviceForm').action = 'edit-service.php?editid=' + service.id; // Point form to edit script
            
            // Populate the form
            document.getElementById('serviceId').value = service.id;
            document.getElementById('serviceName').value = service.name;
            document.getElementById('serviceDescription').value = service.description;

            modal.classList.add('active');
        });
    });

    // Function to close the modal
    function closeModal() {
        modal.classList.remove('active');
    }

    closeBtns.forEach(btn => {
        btn.addEventListener('click', closeModal);
    });

    window.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
});