<aside class="sidebar" id="sidebar">
    <ul class="nav">
        <li class="nav-item" data-page="dashboard.php">
            <a class="nav-link" href="dashboard.php">
                <span class="nav-icon"><i class="fas fa-th-large"></i></span>
                <span class="menu-title">Dashboard</span>
            </a>
        </li>

        <li class="nav-item has-submenu" data-page="all-appointment.php new-appointment.php add-appointment.php mac.php mas.php">
            <a class="nav-link" href="#">
                <span class="nav-icon"><i class="far fa-calendar-alt"></i></span>
                <span class="menu-title">Appointments</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </a>
            <div class="submenu">
                <ul class="submenu-item">
                    <li><a class="nav-link" href="mac.php"><i class="fas fa-notes-medical"></i>Consultation</a></li>
                    <li><a class="nav-link" href="mas.php"><i class="fas fa-briefcase-medical"></i>Services</a></li>
                </ul>
            </div>
        </li>

        <li class="nav-item" data-page="manage-patient.php add-patient.php edit-patient-detail.php">
            <a class="nav-link" href="manage-patient.php">
                <span class="nav-icon"><i class="fas fa-user-injured"></i></span>
                <span class="menu-title">Patients</span>
            </a>
        </li>

        <li class="nav-item" data-page="manage-service.php">
            <a class="nav-link" href="manage-service.php">
                <span class="nav-icon"><i class="fas fa-heartbeat"></i></span>
                <span class="menu-title">Services</span>
            </a>
        </li>
        <li class="nav-item" data-page="manage-inventory.php">
            <a class="nav-link" href="manage-inventory.php">
                <span class="nav-icon"><i class="fas fa-pills"></i></span>
                <span class="menu-title">Inventory</span>
            </a>
        </li>

        <li class="nav-item" data-page="calendar.php">
            <a class="nav-link" href="calendar.php">
                <span class="nav-icon"><i class="fas fa-calendar"></i></span>
                <span class="menu-title">Calendar</span>
            </a>
        </li>

        <li class="nav-item" data-page="manage-reviews.php">
            <a class="nav-link" href="manage-reviews.php">
                <span class="nav-icon"><i class="fas fa-star"></i></span>
                <span class="menu-title">Feedback</span>
            </a>
        </li>
    </ul>
</aside>

<style>
    .sidebar .nav-item .submenu {
        display: none;
        padding-left: 2.5rem; /* Indent submenu items */
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-in-out;
    }

    .sidebar .nav-item.active .submenu {
        display: block;
        max-height: 200px; /* Adjust as needed */
    }

    .sidebar .nav-item .submenu-item {
        list-style: none;
        padding: 0;
    }

    .sidebar .nav-item .submenu-item li a {
        padding: 0.5rem 0;
        display: block;
        color: #554; /* Lighter color for submenu items */
        font-size: 0.875rem;
        position: relative;
    }

    .sidebar .nav-item .submenu-item li a i {
        width: 20px;
        text-align: center;
        margin-right: 0.5rem;
        color: #8898aa;
    }

    .sidebar .nav-item .submenu-item li a:hover {
        color: #fff;
    }

    .sidebar .nav-item .nav-link .menu-arrow {
        margin-left: auto;
        transition: transform 0.3s ease;
    }

    .sidebar .nav-item.active > .nav-link .menu-arrow {
        transform: rotate(180deg);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = "<?php echo basename($_SERVER['PHP_SELF']); ?>";
        const navItems = document.querySelectorAll('.sidebar .nav-item');

        // Function to open a submenu
        function openSubmenu(submenu) {
            if (submenu) {
                submenu.style.maxHeight = submenu.scrollHeight + "px";
            }
        }

        // Function to close a submenu
        function closeSubmenu(submenu) {
            if (submenu) {
                submenu.style.maxHeight = '0';
            }
        }

        // Set active state for the current page
        navItems.forEach(item => {
            const pages = item.getAttribute('data-page');
            if (pages && pages.split(' ').includes(currentPage)) {
                item.classList.add('active');
                // If the active item is in a submenu, also open the submenu
                if (item.closest('.submenu')) {
                    const parentLi = item.closest('.has-submenu');
                    if (parentLi) {
                        parentLi.classList.add('active');
                        openSubmenu(parentLi.querySelector('.submenu'));
                    }
                } else if (item.classList.contains('has-submenu')) {
                    openSubmenu(item.querySelector('.submenu'));
                }
            }
        });

        // Dropdown functionality
        const submenuLinks = document.querySelectorAll('.sidebar .nav-item.has-submenu > a');
        submenuLinks.forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                const parentLi = this.closest('.has-submenu');
                parentLi.classList.toggle('active');
            });
        });
    });
</script>