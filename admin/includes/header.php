<link rel="stylesheet" href="css/header.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<header class="admin-header">
    <?php
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
            <h1>Dashboard</h1>
            <p>Welcome back, manage your dental practice</p>
        </div>
    </div>

    <div class="header-right">
        <button class="icon-button notification-icon" aria-label="Notifications">
            <i class="fas fa-bell"></i>
            <span class="badge">3</span>
        </button>

        <div class="user-profile nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
                    <img class="user-avatar" src="images/faces/face8.jpg" alt="Profile image">
                    <div class="user-info">
                        <span class="name"><?php echo htmlentities($adminName); ?></span>
                        <span class="role">Administrator</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                <div class="dropdown-header text-center">
                    <img class="img-md rounded-circle" src="images/faces/face8.jpg" alt="Profile image">
                    <p class="mb-1 mt-3"><?php echo htmlentities($adminName); ?></p>
                    <p class="font-weight-light text-muted mb-0"><?php echo htmlentities($adminEmail); ?></p>
                </div>
                <a class="dropdown-item" href="profile.php"><i class="dropdown-item-icon icon-user text-primary"></i> My Profile</a>
                <a class="dropdown-item" href="change-password.php"><i class="dropdown-item-icon icon-energy text-primary"></i> Setting</a>
                <a class="dropdown-item" href="logout.php"><i class="dropdown-item-icon icon-power text-primary"></i>Sign Out</a>
            </div>
        </div>
    </div>
</header>