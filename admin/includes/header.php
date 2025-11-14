<link rel="stylesheet" href="css/header.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<header class="admin-header">
    <?php
    // Determine the page title and description based on the current script
    $currentPage = basename($_SERVER['PHP_SELF']);
    $pageTitle = 'Dashboard'; // Default title
    $pageDescription = 'Welcome back, manage your dental practice'; // Default description
    
    switch ($currentPage) {
        case 'dashboard.php':
            $pageTitle = 'Dashboard';
            $pageDescription = 'Welcome back, manage your dental practice';
            break;
        case 'mac.php':
        case 'mas.php':
            $pageTitle = 'Appointments';
            $pageDescription = 'Manage consultation and service appointments';
            break;
        case 'manage-patient.php':
        case 'add-patient.php':
        case 'edit-patient-detail.php':
        case 'view-ph.php':
        case 'view-er.php':
            $pageTitle = 'Patients';
            $pageDescription = 'Manage your patient records and information';
            break;
        case 'manage-service.php':
        case 'add-service.php':
        case 'edit-service.php':
            $pageTitle = 'Services';
            $pageDescription = 'Manage your dental services';
            break;
        case 'manage-inventory.php':
            $pageTitle = 'Inventory';
            $pageDescription = 'Track and manage your product inventory';
            break;
        case 'calendar.php':
            $pageTitle = 'Calendar';
            $pageDescription = 'View and manage schedule availability';
            break;
        case 'profile.php':
        case 'change-password.php':
            $pageTitle = 'Settings';
            $pageDescription = 'Manage your administrator profile and settings';
            break;
    }

    $aid = $_SESSION['sturecmsaid'];
    $sql = "SELECT * from tbladmin where ID=:aid";

    $query = $dbh->prepare($sql);
    $query->bindParam(':aid', $aid, PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);

    $adminName = 'Admin'; // Default
    $adminEmail = '';
    if ($query->rowCount() > 0) {
        $row = $results[0];
        $adminName = $row->AdminName;
        $adminEmail = $row->Email;
    }
    ?>
    <div class="header-left">
        <div class="logo-container">
            <img src="images/Jf logo.png" alt="Logo" class="logo-img">
            <div class="logo-text-group">
                <span class="main">JF Dental Care</span>
                <span class="sub">Admin Panel</span>
            </div>
        </div>
        <div class="dashboard-info">
            <h1><?php echo htmlentities($pageTitle); ?></h1>
            <p><?php echo htmlentities($pageDescription); ?></p>
        </div>
    </div>

    <div class="header-right">
        <button class="icon-button notification-icon" aria-label="Notifications">
            <i class="fas fa-bell"></i>
            <span class="badge">3</span>
        </button>

        <div class="user-profile nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
                    <img class="user-avatar" src="images/faces/profile.png" alt="Profile image">
                    <div class="user-info">
                        <span class="name"><?php echo htmlentities($adminName); ?></span>
                        <span class="role">Administrator</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">

                <a class="dropdown-item" href="profile.php"><i class="dropdown-item-icon icon-user text-primary"></i> My Profile</a>
                <a class="dropdown-item" href="change-password.php"><i class="dropdown-item-icon icon-energy text-primary"></i> Setting</a>
                <a class="dropdown-item" href="logout.php"><i class="dropdown-item-icon icon-power text-primary"></i>Sign Out</a>
            </div>
        </div>
    </div>
</header>